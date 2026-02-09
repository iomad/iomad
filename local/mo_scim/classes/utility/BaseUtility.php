<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Base utility
 * @package   local_mo_scim
 * @copyright   2025  miniOrange
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later, see license.txt
 * @author      miniOrange
 */

namespace local_mo_scim\utility;

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

use local_mo_scim\database\DatabaseHandler;

/**
 * Base utility class
 */
class BaseUtility {
    /**
     * Database handler instance
     * @var DatabaseHandler
     */
    private $dbhandler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->dbhandler = new DatabaseHandler();
    }

    /**
     * Get authentication type to set
     * @return string
     */
    public static function getauthenticationtypetoset() {
        $enabledplugins = get_enabled_auth_plugins();

        if (in_array('iomadoidc', $enabledplugins)) {
            return 'iomadoidc';
        } else if (in_array('iomadsaml2', $enabledplugins)) {
            return 'iomadsaml2';
        } else if (in_array('mo_saml', $enabledplugins)) {
            return 'mo_saml';
        } else if (in_array('saml2', $enabledplugins)) {
            return 'saml2';
        }

        return 'manual';
    }

    /**
     * Update user profile with attributes
     * @param object $user
     * @param array $userdata
     * @return object
     */
    public function updateuserprofilewithattributes($user, $userdata) {
        $userprofile     = new \stdClass();
        $userprofile->id = $user->id;
        $customattributes = $this->dbhandler->getcustomattributes();
        if (!empty($customattributes)) {
            foreach ($customattributes as $attribute) {
                $shortname = $attribute->shortname;
                $updatedshortname = "profile_field_" . $attribute->shortname;
                foreach ($userdata as $key => $value) {
                    if (strcmp($key, $shortname) === 0) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $userprofile->$updatedshortname = $value;
                    }
                }
            }
        }
        foreach ($userdata as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        profile_save_data($userprofile);
        user_update_user($user, false);
        return get_complete_user_data('id', $userprofile->id);
    }
}
