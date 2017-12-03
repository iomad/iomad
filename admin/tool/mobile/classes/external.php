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
 * This is the external API for this tool.
 *
 * @package    tool_mobile
 * @copyright  2016 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mobile;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_system;
use moodle_exception;
use moodle_url;
use core_text;

/**
 * This is the external API for this tool.
 *
 * @copyright  2016 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns description of get_plugins_supporting_mobile() parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 3.1
     */
    public static function get_plugins_supporting_mobile_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Returns a list of Moodle plugins supporting the mobile app.
     *
     * @return array an array of warnings and objects containing the plugin information
     * @since  Moodle 3.1
     */
    public static function get_plugins_supporting_mobile() {
        return array(
            'plugins' => api::get_plugins_supporting_mobile(),
            'warnings' => array(),
        );
    }

    /**
     * Returns description of get_plugins_supporting_mobile() result value.
     *
     * @return external_description
     * @since  Moodle 3.1
     */
    public static function get_plugins_supporting_mobile_returns() {
        return new external_single_structure(
            array(
                'plugins' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'component' => new external_value(PARAM_COMPONENT, 'The plugin component name.'),
                            'version' => new external_value(PARAM_NOTAGS, 'The plugin version number.'),
                            'addon' => new external_value(PARAM_COMPONENT, 'The Mobile addon (package) name.'),
                            'dependencies' => new external_multiple_structure(
                                                new external_value(PARAM_COMPONENT, 'Mobile addon name.'),
                                                'The list of Mobile addons this addon depends on.'
                                               ),
                            'fileurl' => new external_value(PARAM_URL, 'The addon package url for download
                                                            or empty if it doesn\'t exist.'),
                            'filehash' => new external_value(PARAM_RAW, 'The addon package hash or empty if it doesn\'t exist.'),
                            'filesize' => new external_value(PARAM_INT, 'The addon package size or empty if it doesn\'t exist.')
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of get_public_config() parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 3.2
     */
    public static function get_public_config_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Returns a list of the site public settings, those not requiring authentication.
     *
     * @return array with the settings and warnings
     * @since  Moodle 3.2
     */
    public static function get_public_config() {
        $result = api::get_public_config();
        $result['warnings'] = array();
        return $result;
    }

    /**
     * Returns description of get_public_config() result value.
     *
     * @return external_description
     * @since  Moodle 3.2
     */
    public static function get_public_config_returns() {
        return new external_single_structure(
            array(
                'wwwroot' => new external_value(PARAM_RAW, 'Site URL.'),
                'httpswwwroot' => new external_value(PARAM_RAW, 'Site https URL (if httpslogin is enabled).'),
                'sitename' => new external_value(PARAM_TEXT, 'Site name.'),
                'guestlogin' => new external_value(PARAM_INT, 'Whether guest login is enabled.'),
                'rememberusername' => new external_value(PARAM_INT, 'Values: 0 for No, 1 for Yes, 2 for optional.'),
                'authloginviaemail' => new external_value(PARAM_INT, 'Whether log in via email is enabled.'),
                'registerauth' => new external_value(PARAM_PLUGIN, 'Authentication method for user registration.'),
                'forgottenpasswordurl' => new external_value(PARAM_URL, 'Forgotten password URL.'),
                'authinstructions' => new external_value(PARAM_RAW, 'Authentication instructions.'),
                'authnoneenabled' => new external_value(PARAM_INT, 'Whether auth none is enabled.'),
                'enablewebservices' => new external_value(PARAM_INT, 'Whether Web Services are enabled.'),
                'enablemobilewebservice' => new external_value(PARAM_INT, 'Whether the Mobile service is enabled.'),
                'maintenanceenabled' => new external_value(PARAM_INT, 'Whether site maintenance is enabled.'),
                'maintenancemessage' => new external_value(PARAM_RAW, 'Maintenance message.'),
                'logourl' => new external_value(PARAM_URL, 'The site logo URL', VALUE_OPTIONAL),
                'compactlogourl' => new external_value(PARAM_URL, 'The site compact logo URL', VALUE_OPTIONAL),
                'typeoflogin' => new external_value(PARAM_INT, 'The type of login. 1 for app, 2 for browser, 3 for embedded.'),
                'launchurl' => new external_value(PARAM_URL, 'SSO login launch URL.', VALUE_OPTIONAL),
                'mobilecssurl' => new external_value(PARAM_URL, 'Mobile custom CSS theme', VALUE_OPTIONAL),
                'tool_mobile_disabledfeatures' => new external_value(PARAM_RAW, 'Disabled features in the app', VALUE_OPTIONAL),
                'identityproviders' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_TEXT, 'The identity provider name.'),
                            'iconurl' => new external_value(PARAM_URL, 'The icon URL for the provider.'),
                            'url' => new external_value(PARAM_URL, 'The URL of the provider.'),
                        )
                    ),
                    'Identity providers', VALUE_OPTIONAL
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of get_config() parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 3.2
     */
    public static function get_config_parameters() {
        return new external_function_parameters(
            array(
                'section' => new external_value(PARAM_ALPHANUMEXT, 'Settings section name.', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Returns a list of site settings, filtering by section.
     *
     * @param string $section settings section name
     * @return array with the settings and warnings
     * @since  Moodle 3.2
     */
    public static function get_config($section = '') {

        $params = self::validate_parameters(self::get_config_parameters(), array('section' => $section));

        $settings = api::get_config($params['section']);
        $result['settings'] = array();
        foreach ($settings as $name => $value) {
            $result['settings'][] = array(
                'name' => $name,
                'value' => $value,
            );
        }

        $result['warnings'] = array();
        return $result;
    }

    /**
     * Returns description of get_config() result value.
     *
     * @return external_description
     * @since  Moodle 3.2
     */
    public static function get_config_returns() {
        return new external_single_structure(
            array(
                'settings' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'The name of the setting'),
                            'value' => new external_value(PARAM_RAW, 'The value of the setting'),
                        )
                    ),
                    'Settings'
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of get_autologin_key() parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 3.2
     */
    public static function get_autologin_key_parameters() {
        return new external_function_parameters (
            array(
                'privatetoken' => new external_value(PARAM_ALPHANUM, 'Private token, usually generated by login/token.php'),
            )
        );
    }

    /**
     * Creates an auto-login key for the current user. Is created only in https sites and is restricted by time and ip address.
     *
     * @param string $privatetoken the user private token for validating the request
     * @return array with the settings and warnings
     * @since  Moodle 3.2
     */
    public static function get_autologin_key($privatetoken) {
        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_autologin_key_parameters(), array('privatetoken' => $privatetoken));
        $privatetoken = $params['privatetoken'];

        $context = context_system::instance();

        // We must toletare these two exceptions: forcepasswordchangenotice and usernotfullysetup.
        try {
            self::validate_context($context);
        } catch (moodle_exception $e) {
            if ($e->errorcode != 'usernotfullysetup' && $e->errorcode != 'forcepasswordchangenotice') {
                // In case we receive a different exception, throw it.
                throw $e;
            }
        }

        api::check_autologin_prerequisites($USER->id);

        if (isset($_GET['privatetoken']) or empty($privatetoken)) {
            throw new moodle_exception('invalidprivatetoken', 'tool_mobile');
        }

        // Check the request counter, we must limit the number of times the privatetoken is sent.
        // Between each request 6 minutes are required.
        $last = get_user_preferences('tool_mobile_autologin_request_last', 0, $USER);
        // Check if we must reset the count.
        $timenow = time();
        if ($timenow - $last < 6 * MINSECS) {
            throw new moodle_exception('autologinkeygenerationlockout', 'tool_mobile');
        }
        set_user_preference('tool_mobile_autologin_request_last', $timenow, $USER);

        // We are expecting a privatetoken linked to the current token being used.
        // This WS is only valid when using mobile services via REST (this is intended).
        $currenttoken = required_param('wstoken', PARAM_ALPHANUM);
        $conditions = array(
            'userid' => $USER->id,
            'token' => $currenttoken,
            'privatetoken' => $privatetoken,
        );
        if (!$token = $DB->get_record('external_tokens', $conditions)) {
            throw new moodle_exception('invalidprivatetoken', 'tool_mobile');
        }

        $result = array();
        $result['key'] = api::get_autologin_key();
        $autologinurl = new moodle_url("/$CFG->admin/tool/mobile/autologin.php");
        $result['autologinurl'] = $autologinurl->out(false);
        $result['warnings'] = array();
        return $result;
    }

    /**
     * Returns description of get_autologin_key() result value.
     *
     * @return external_description
     * @since  Moodle 3.2
     */
    public static function get_autologin_key_returns() {
        return new external_single_structure(
            array(
                'key' => new external_value(PARAM_ALPHANUMEXT, 'Auto-login key for a single usage with time expiration.'),
                'autologinurl' => new external_value(PARAM_URL, 'Auto-login URL.'),
                'warnings' => new external_warnings(),
            )
        );
    }
}
