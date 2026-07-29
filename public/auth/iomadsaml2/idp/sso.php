<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file handles the login process when Moodle is acting as an IDP.
 *
 * @package    auth_iomadsaml2
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/auth/iomadsaml2/setup.php');

require_login(null, false);
$relaystate = optional_param('RelayState', '', PARAM_RAW);

if (isguestuser()) {
    // Guest user not allowed here.
    throw new iomadsaml2_exception('guest_error', get_string('moodleidpguest_error', 'auth_iomadsaml2'));
}

// Get the request data.
$requestparam = required_param('SAMLRequest', PARAM_RAW);
$request = gzinflate(base64_decode($requestparam));
$domxml = new DOMDocument();
$domxml->loadXML($request);
$xpath = new DOMXPath($domxml);

// Load profile fields into attributes.
$authplugin = get_auth_plugin('iomadsaml2');
$userfields = array_merge($authplugin->userfields, $authplugin->get_custom_user_profile_fields());
profile_load_data($USER);
// Add username as `uid` as many services look for `uid` by default.
$attributes = ['uid' => $USER->username];
foreach ($userfields as $field) {
    $attributes[$field] = $USER->$field ? $USER->$field : '';
}

// Get data from input request.
$id = $xpath->evaluate('normalize-space(/*/@ID)');
$destination = htmlspecialchars($xpath->evaluate('normalize-space(/*/@AssertionConsumerServiceURL)'));
$sp = $xpath->evaluate('normalize-space(/*/*[local-name() = "Issuer"])');

// Confirm we know about this SP.
$knownsps = [];
foreach (explode(PHP_EOL, $iomadsaml2auth->config->moodleidpsplist) as $ksp) {
    $ksp = trim($ksp);
    if (empty($ksp)) {
        continue;
    }
    $knownsps[] = $ksp;
}

if (!in_array($sp, $knownsps)) {
    throw new iomadsaml2_exception('unknown_sp_error', get_string('moodleidpsplist_error', 'auth_iomadsaml2', $sp));
}

// Get time in UTC.
$datetime = new DateTime();
$datetime->setTimezone(new DatetimeZone('UTC'));
$instant = $datetime->format('Y-m-d') . 'T' . $datetime->format('H:i:s') . 'Z';
$datetime->sub(new DateInterval('P1D'));
$before = $datetime->format('Y-m-d') . 'T' . $datetime->format('H:i:s') . 'Z';
$datetime->add(new DateInterval('P1M'));
$after = $datetime->format('Y-m-d') . 'T' . $datetime->format('H:i:s') . 'Z';

// Get our own IdP URL.
$baseurl = $CFG->wwwroot . '/auth/iomadsaml2/idp';
$issuer = $baseurl . '/metadata.php';

// Make up a session.
$session = 'session' . mt_rand(100000, 999999);

// Construct attributes in XML.
$attributexml = '';
foreach ((array)$attributes as $name => $value) {
    if (is_string($value)) {
        $attributexml .= '<saml:Attribute Name="' . $name .
                '" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified">' .
                '<saml:AttributeValue>' . htmlspecialchars($value) . '</saml:AttributeValue>' .
                '</saml:Attribute>' . "\n";
    }
}
$email = htmlspecialchars($USER->email);
// Construct XML without signature.
$responsexml = <<<EOF
<samlp:Response
        xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
        ID="{$id}_2" InResponseTo="{$id}" Version="2.0" IssueInstant="{$instant}" Destination="{$destination}">
    <saml:Issuer>{$issuer}</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="{$id}_3" Version="2.0"
            IssueInstant="{$instant}">
        <saml:Issuer>{$issuer}</saml:Issuer>
        <saml:Subject>
            <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">
                {$email}
            </saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData InResponseTo="{$id}"
                    Recipient="{$destination}"
                    NotOnOrAfter="{$after}"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions
                NotBefore="{$before}"
                NotOnOrAfter="{$after}">
            <saml:AudienceRestriction>
            <saml:Audience>{$sp}</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="{$instant}" SessionIndex="{$session}">
            <saml:AuthnContext>
                <saml:AuthnContextClassRef>
                    urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport
                </saml:AuthnContextClassRef>
            </saml:AuthnContext>
        </saml:AuthnStatement>
        <saml:AttributeStatement>
            {$attributexml}
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
EOF;
// Load it into a DOM.
$outdoc = new \DOMDocument();
$outdoc->loadXML($responsexml);

// Find the relevant elements.
$xpath = new DOMXPath($outdoc);
$assertion = $xpath->query('//*[local-name()="Assertion"]')[0];
$subject = $xpath->query('child::*[local-name()="Subject"]', $assertion)[0];

// Sign it using the fixture key/cert.
$signer = new \SimpleSAML\XML\Signer(['id' => 'ID']);

$signer->loadPrivateKey($iomadsaml2auth->certpem, $iomadsaml2auth->config->privatekeypass, true);
$signer->loadCertificate($iomadsaml2auth->certcrt, true);
$signer->sign($assertion, $assertion, $subject);

// Don't send as a referer or the login form might end up coming back here.
header('Referrer-Policy: no-referrer');

// Output an HTML form that automatically submits this.
echo '<!doctype html>';
echo html_writer::start_tag('html');
echo html_writer::tag('head', html_writer::tag('title', 'SSO redirect back'));
echo html_writer::start_tag('body');
echo html_writer::start_tag('form', ['id' => 'frog', 'method' => 'post', 'action' => htmlspecialchars_decode($destination)]);
echo html_writer::empty_tag(
    'input',
    ['type' => 'hidden', 'name' => 'SAMLResponse', 'value' => base64_encode($outdoc->saveXML())]
);
echo html_writer::empty_tag(
    'input',
    ['type' => 'hidden', 'name' => 'RelayState', 'value' => $relaystate]
);
echo html_writer::end_tag('form');
echo html_writer::tag('script', 'document.getElementById("frog").submit();');
echo html_writer::end_tag('form');
echo html_writer::end_tag('body');
exit;
