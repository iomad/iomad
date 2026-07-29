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
 * Identity provider metadata
 *
 * @package    auth_iomadsaml2
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreStart
require_once(__DIR__ . '/../../../config.php');
// @codingStandardsIgnoreEnd
require_once('../setup.php');
require_once('../locallib.php');

$iomadsaml2auth = new \auth_iomadsaml2\auth();

if ($iomadsaml2auth->config->moodleidpenabled) {
    $download = optional_param('download', '', PARAM_RAW);
    if ($download) {
        header('Content-Disposition: attachment; filename=' . $iomadsaml2auth->spname . '.xml');
    }

    $cert = file_get_contents($iomadsaml2auth->certcrt);
    $cert = preg_replace('~(-----(BEGIN|END) CERTIFICATE-----)|\n~', '', $cert);
    $baseurl = $CFG->wwwroot . '/auth/iomadsaml2/idp';

    $xml = <<<EOF
    <md:EntityDescriptor entityID="{$baseurl}/metadata.php" xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
    <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" WantAuthnRequestsSigned="false">
    <md:KeyDescriptor>
        <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
            <X509Data><X509Certificate>{$cert}</X509Certificate></X509Data>
        </KeyInfo>
    </md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
        Location="{$baseurl}/slo.php" />
    <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</md:NameIDFormat>
    <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
        Location="{$baseurl}/sso.php" />
    </md:IDPSSODescriptor>
    </md:EntityDescriptor>
    EOF;

    header('Content-Type: text/xml');
    echo($xml);
} else {
    throw new iomadsaml2_exception('idp_enabled_error', get_string('moodleidpenabled_error', 'auth_iomadsaml2'));
}
