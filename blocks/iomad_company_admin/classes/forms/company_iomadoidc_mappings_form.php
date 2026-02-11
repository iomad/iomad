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
 * IOMAD Dashboard compani OIDC mappings form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use moodle_url;
use core_text;
use moodleform;
use local_iomad\iomad;
use html_writer;

/**
 * IOMAD Dashboard compani OIDC mappings form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_iomadoidc_mappings_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB, $postfix;

        // Set up the form.
        $mform = & $this->_form;

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $authplugin = get_auth_plugin('iomadoidc');
        $auth = $authplugin->authtype;
        $userfields = $authplugin->userfields;
        $mapremotefields = true;
        $updateremotefields = false;

        // Get all of the profile field categories.
        $profilecategories = iomad::iomad_filter_profile_categories($DB->get_records('user_info_category'));
        $customfields = [];
        if (!empty($profilecategories)) {
            $insql = $DB->get_in_or_equal($profilecategories);
            $customfields = $DB->get_records_select_menu(
                'user_info_field',
                'categoryid',
                $insql,
                '',
                "id,concat('profile_field_',shortname)");
            $customfields = array_values($customfields);
        }

        // Introductory explanation and help text.
        $mform->addElement('html', html_writer::tag(
            'h2',
            format_string(
                get_string('pluginname', 'auth_iomadoidc') .
                " : " .
                get_string('auth_data_mapping', 'auth')
                )
            )
        );
        $mform->addElement('html', get_string('cfg_field_mapping_desc', 'auth_iomadoidc'));
        $mform->addElement('html', html_writer::tag('p', get_string('managermapping', 'local_iomad_oidc_sync')));

        // Add in custom data for MS Graph search.
        $mform->addElement('text',
                           "graphproperties{$postfix}",
                           get_string('graphproperties', 'local_iomad_oidc_sync'),
                           ['size' => 75]);
        $mform->addHelpButton("graphproperties{$postfix}", 'graphproperties', 'local_iomad_oidc_sync');
        $mform->setType("graphproperties{$postfix}", PARAM_TEXT);

        // Generate the list of options.
        $lockoptions = [
            'unlocked' => get_string('unlocked', 'auth'),
            'unlockedifempty' => get_string('unlockedifempty', 'auth'),
            'locked' => get_string('locked', 'auth'),
        ];

        $alwaystext = get_string('update_oncreate_and_onlogin', 'auth_iomadoidc');
        $onlogintext = get_string('update_onlogin', 'auth');
        $updatelocaloptions = [
            'always' => $alwaystext,
            'oncreate' => get_string('update_oncreate', 'auth'),
            'onlogin' => $onlogintext,
        ];

        $updateextoptions = [
            '0' => get_string('update_never', 'auth'),
            '1' => get_string('update_onupdate', 'auth'),
        ];

        // Generate the list of profile fields to allow updates / lock.
        if (!empty($customfields)) {
            $userfields = array_merge($userfields, $customfields);
            $customfieldname = $DB->get_records('user_info_field', null, '', 'shortname, name');
        }

        $remotefields = auth_iomadoidc_get_remote_fields();
        $emailremotefields = auth_iomadoidc_get_email_remote_fields();

        foreach ($userfields as $field) {
            // Define the fieldname we display to the  user.
            // this includes special handling for some profile fields.
            $fieldname = $field;
            $fieldnametoolong = false;
            if ($fieldname === 'lang') {
                $fieldname = get_string('language');
            } else if (!empty($customfields) && in_array($field, $customfields)) {
                // If custom field then pick name from database.
                $fieldshortname = str_replace('profile_field_', '', $fieldname);
                $fieldname = $customfieldname[$fieldshortname]->name;
                if (core_text::strlen($fieldshortname) > 67) {
                    // If custom profile field name is longer than 67 characters we will not be able to store the setting
                    // such as 'field_updateremote_profile_field_NOTSOSHORTSHORTNAME' in the database because the character
                    // limit for the setting name is 100.
                    $fieldnametoolong = true;
                }
            } else if ($fieldname == 'url') {
                $fieldname = get_string('webpage');
            } else {
                $fieldname = get_string($fieldname);
            }

            // Generate the list of fields / mappings.
            if ($fieldnametoolong) {
                // Display a message that the field can not be mapped because it's too long.
                $url = new moodle_url('/user/profile/index.php');
                $a = (object)['fieldname' => s($fieldname), 'shortname' => s($field), 'charlimit' => 67, 'link' => ''];
                $mform->addElement('static', $auth.'/field_not_mapped_'.sha1($field) . $postfix, '',
                                    get_string('cannotmapfield', 'auth', $a));
            } else if ($mapremotefields) {
                // We are mapping to a remote field here.
                // Mapping.
                if ($field == 'email') {
                    $mform->addElement('select', "field_map_{$field}{$postfix}",
                                        get_string('auth_fieldmapping', 'auth', $fieldname),
                                        $emailremotefields);
                } else {
                    if ($field == 'firstname' ||
                        $field == 'lastname') {
                        $mform->addElement('select', "field_map_{$field}{$postfix}",
                                            get_string('auth_fieldmapping', 'auth', $fieldname),
                                            $remotefields);
                    } else {
                        $mform->addElement('text', "field_map_{$field}{$postfix}",
                                            get_string('auth_fieldmapping', 'auth', $fieldname));
                        $mform->setType("field_map_{$field}{$postfix}", PARAM_CLEAN);
                    }
                }

                // Update local.
                $mform->addElement('select', "field_updatelocal_{$field}{$postfix}",
                                    get_string('auth_updatelocalfield', 'auth', $fieldname),
                                    $updatelocaloptions);

                // Update remote.
                if ($updateremotefields) {
                    $mform->addElement('select', "field_updateremote_{$field}{$postfix}",
                                        get_string('auth_updateremotefield', 'auth', $fieldname),
                                        $updateextoptions);
                }

                // Lock fields.
                $mform->addElement('select', "field_lock_{$field}{$postfix}",
                                    get_string('auth_fieldlockfield', 'auth', $fieldname),
                                    $lockoptions);
            } else {
                // Lock fields Only.
                $mform->addElement('select', "field_lock_{$field}{$postfix}",
                                    get_string('auth_fieldlockfield', 'auth', $fieldname),
                                    $lockoptions);
            }
        }

        // Show reset?
        $mform->addElement(
            'selectyesno',
            'allowreset',
            get_string('allowformreset', 'block_iomad_company_admin'),
        );

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $actionbuttons = [];
        $actionbuttons[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $actionbuttons[] = $mform->createElement('cancel');
        $actionbuttons[] = $mform->createElement(
            'submit',
            'resetbutton',
            get_string('resetdefault', 'block_iomad_company_admin'),
            [
                'class' => 'dangerbutton',
            ]);
        $mform->addGroup($actionbuttons, 'buttonar', '', ' ', false);

        $mform->hideIF('resetbutton', 'allowreset', 'eq', 0);
    }

    /**
     * Form validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $postfix;

        $errors = parent::validation($data, $files);
        if (!empty($data["graphproperties{$postfix}"])) {
            if (str_contains($data["graphproperties{$postfix}"], " ")) {
                $errors["graphproperties{$postfix}"] = get_string('invalidentry', 'error');
            }
        }
        return $errors;
    }
}
