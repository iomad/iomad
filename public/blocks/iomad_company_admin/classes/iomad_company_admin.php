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
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin;

defined('MOODLE_INTERNAL') || die;

use context_system;
use block_iomad_company_admin\forms\{
    company_auth_options_form,
    company_iomadoidc_form,
    company_iomadoidc_mappings_form,
    company_iomadsaml2_form,
    company_iomadsaml2_mappings_form,
    company_mfa_form,
    company_smtp_options_form};

class iomad_company_admin {

    /**
     * Get the roles for the company_capabilties screen
     */
    public static function get_roles() {
        global $DB;

        $roles = $DB->get_records_sql("SELECT r.* FROM {role} r
                                       JOIN {role_context_levels} rcl ON r.id = rcl.roleid
                                       WHERE rcl.contextlevel = :contextlevel
                                       ORDER BY name",
                                       ['contextlevel' => CONTEXT_COMPANY]);

        return $roles;
    }

    /**
     * Get the Iomad capabilities for given role
     * (We only need to worry about the ones that are SET
     * so we can fish them out of the role_capabilities table
     * directly)
     */
    public static function get_iomad_capabilities($roleid, $companyid) {
        global $DB;

        // We need capabilities defined in the site context
        $context = context_system::instance();
        $capabilities = $DB->get_records('role_capabilities', array('roleid' => $roleid, 'contextid' => $context->id));

        // Filter out caps. Only want 'local/report' and ones containing 'iomad'
        $filtered_capabilities = array();
        foreach ($capabilities as $capability) {
            if ((strpos($capability->capability, 'local/report')===false)
                    && (strpos($capability->capability, 'iomad')===false)
                    && (strpos($capability->capability, 'local/email')===false)
                    ) {
                continue;
            }

            // add the iomad restriction info
            if ($restriction = $DB->get_record('company_role_restriction', array(
                            'roleid' => $roleid,
                            'companyid' => $companyid,
                            'capability' => $capability->capability
            ))) {
                $capability->iomad_restriction = true;
            } else {
                $capability->iomad_restriction = false;
            }
            $filtered_capabilities[$capability->id] = $capability;
        }

        return $filtered_capabilities;
    }

    /**
     * Get the Iomad template capabilities for given role
     * (We only need to worry about the ones that are SET
     * so we can fish them out of the role_capabilities table
     * directly)
     */
    public static function get_iomad_template_capabilities($roleid, $templateid) {
        global $DB;

        // We need capabilities defined in the site context
        $context = context_system::instance();
        $capabilities = $DB->get_records('role_capabilities', array('roleid' => $roleid, 'contextid' => $context->id));

        // Filter out caps. Only want 'local/report' and ones containing 'iomad'
        $filtered_capabilities = array();
        foreach ($capabilities as $capability) {
            if ((strpos($capability->capability, 'local/report')===false)
                    && (strpos($capability->capability, 'iomad')===false)
                    && (strpos($capability->capability, 'local/email')===false)
                    ) {
                continue;
            }

            // add the iomad restriction info
            if ($restriction = $DB->get_record('company_role_templates_caps', array(
                            'roleid' => $roleid,
                            'templateid' => $templateid,
                            'capability' => $capability->capability
            ))) {
                $capability->iomad_restriction = true;
            } else {
                $capability->iomad_restriction = false;
            }
            $filtered_capabilities[$capability->id] = $capability;
        }

        return $filtered_capabilities;
    }

    /**
     * Rearrange list of companies into parent/child order
     * @param array $companies complete list of companies
     * @param array $newlist (partial) ordered list
     * @param int $parentid
     * @param int $depth
     * @return array
     */
    public static function order_companies_by_parent($companies, &$newlist = [], $parentid = 0, $depth = 0) {

        foreach ($companies as $company) {
            $companyid = $company->id;
            if ($company->parentid == $parentid) {
                $company->depth = $depth;
                $newlist[$company->id] = $company;
                $children = array_filter($companies, function($comp) use ($companyid) {
                    return $comp->parentid == $companyid;
                });
                foreach ($children as $child) {
                    self::order_companies_by_parent($companies, $newlist, $companyid, $depth + 1);
                }
            }
        }

        return $newlist;
    }

    /**
     * Serve the new group form as a fragment.
     *
     * @param array $args List of named arguments for the fragment loader.
     * @return string
     */
    function output_fragment_new_submit_user_department_form($args) {
        global $CFG;

        $args = (object) $args;
        $context = $args->context;

        $formdata = [];
        $error = new stdClass();

        list($ignored, $course) = get_context_info_array($context->id);

        //require_capability('block/report_error:reporterror', $context);

        $mform = new \block_iomad_company_admin\forms\submit_user_department_form(null, [], 'post', '', null, true, $formdata);

        // Used to set the courseid.
        //$mform->set_data(['courseid' => $course->id]);

        if (!empty($args->jsonformdata)) {
            // If we were passed non-empty form data we want the mform to call validation functions and show errors.
            $mform->is_validated();
        }

        return $mform->render();
    }

    /**
     * Function to set up the company_iomadoidc_form
     *
     * @return company_iomadoidc_form
     */
    public static function get_company_iomadoidc_form(): company_iomadoidc_form {
        global $companyid, $PAGE, $postfix, $systemcontext;

        // Set up the form.
        $mform = new company_iomadoidc_form($PAGE->url);

        // Set the form data.
        $formdata = get_config('auth_iomadoidc');
        $formdata->action = 'iomadoidcbasic';
        $customicon = 'customicon' . $postfix;
        if (!empty($formdata->$customicon)) {
            $customiconid = file_get_submitted_draft_itemid('customicon');
            file_prepare_draft_area(
                $customiconid,
                $systemcontext->id,
                'auth_iomadoidc',
                'customicon',
                $companyid,
                ['maxfiles' => 1]
            );
            $formdata->customicon = $customiconid;
        }

        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Function to set up the company_iomadoidc_mappings_form
     *
     * @return company_iomadoidc_mappings_form
     */
    public static function get_company_iomadoidc_mappings_form(): company_iomadoidc_mappings_form {
        global $PAGE;

        // Set up the form.
        $mform = new compancompany_iomadoidc_mappings_formy_iomadoidc_form($PAGE->url);

        // Set the form data.
        $formdata = get_config('auth_iomadoidc');
        $formdata->action = 'iomadoidcmappings';
        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Function to set up the company_iomadsaml2_form
     *
     * @return company_iomadsaml2_form
     */
    public static function get_company_iomadsaml2_form(): company_iomadsaml2_form {
        global $PAGE;

        // Set up the form.
        $mform = new company_iomadsaml2_form($PAGE->url);

        // Set the form data.
        $formdata = get_config('auth_iomadsaml2');
        $formdata->action = 'iomadsaml';
        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Function to set up the company_iomadsaml2_mappings_form
     *
     * @return company_iomadsaml2_mappings_form
     */
    public static function get_company_iomadsaml2_mappings_form(): company_iomadsaml2_mappings_form {
        global $PAGE;

        // Set up the form.
        $mform = new company_iomadsaml2_mappings_form($PAGE->url);

        // Set the form data.
        $formdata = get_config('auth_iomadoidc');
        $formdata->action = 'iomadsamlmappings';
        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Function to set up the company_auth_options_form
     *
     * @return company_auth_options_form
     */
    public static function get_company_auth_options_form(): company_auth_options_form {
        global $CFG, $PAGE, $postfix;

        // Set the list of general auth options we support.
        $authoptions = [
            'registerauth',
            'authloginviaemail',
            'guestloginbutton',
            'alternateloginurl',
            'showloginform',
            'forgottenpasswordurl',
            'auth_instructions',
            'allowemailaddresses',
            'denyemailaddresses',
            'enableloginrecaptcha',
            'recaptchapublickey',
            'recaptchaprivatekey',
            'loginpasswordtoggle',
        ];

        // Set up the form.
        $mform = new company_auth_options_form($PAGE->url);

        // Set the form data.
        $formdata = (object) [];
        foreach ($authoptions as $authoption) {
            // We are forcing postfix here as we don't want to get the site options if we can help it.
            $field = $authoption . $postfix;
            if (isset($CFG->$field)) {
                if ($authoption == 'auth_instructions') {
                    $formdata->$authoption = [
                        'text' => $CFG->$field,
                        'format' => 1
                    ];
                } else {
                    $formdata->$authoption = $CFG->$field;
                }
            }
        }

        $formdata->action = 'companyauthoptions';
        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Function to set up the company_smtp_options_form
     *
     * @return company_smtp_options_form
     */
    public static function get_company_smtp_options_form(): company_smtp_options_form {
        global $CFG, $PAGE, $postfix;

        // Set the list of SMTP options we support.
        $smtpoptions = [
            'smtphosts',
            'smtpsecure',
            'smtpauthtype',
            'smtpoauthservice',
            'noreplyaddress',
            'smtpuser',
            'smtppass',
        ];

        // Get the company options for this section.
        $formdata = (object) [];
        foreach ($smtpoptions as $smtpoption) {
            // We are forcing postfix here as we don't want to get the site options if we can help it.
            $field = $smtpoption . $postfix;
            if (isset($CFG->$field)) {
                $formdata->$smtpoption = $CFG->$field;
            }
        }
        $formdata->action = 'companysmtpsettings';

        // Set up the form.
        $mform = new company_smtp_options_form($PAGE->url);

        // Set the form data
        $mform->set_data($formdata);

        // Return the form.
        return $mform;
    }

    /**
     * Process the company_iomadoidc_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_iomadoidc_form(object $data): string {
        global $companyid, $postfix, $systemcontext;

        // Remove unwanted form data.
        unset($data->action);
        unset($data->submitbutton);

        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($data as $id => $value) {
                unset_config($id, 'auth_iomadoidc');
            }
            return get_string('companyoidcsettingsresetok', 'block_iomad_company_admin');
        } else {

            // Process everything else.
            foreach ($data as $id => $value) {
                if ($id == 'customicon' . $postfix) {
                    $fs = get_file_storage();
                    if (!empty($value)) {
                        file_save_draft_area_files(
                            $value,
                            $systemcontext->id,
                            'auth_iomadoidc',
                            'customicon',
                            $companyid,
                            ['maxfiles' => 1]
                        );

                        // Set the plugin config so it can actually be picked up.
                        if ($files = $fs->get_area_files(
                            $systemcontext->id,
                            'auth_iomadoidc',
                            'customicon',
                            $companyid
                        )) {
                            foreach ($files as $file) {
                                if ($file->get_filename() != '.') {
                                    break;
                                }
                            }
                            set_config($id, $file->get_filepath() . $file->get_filename(), 'auth_iomadoidc');
                            auth_iomadoidc_initialize_customicon($file->get_filename());
                        } else {
                            set_config($id, '', 'auth_iomadoidc');
                        }
                    }
                } else {
                    set_config($id, $value, 'auth_iomadoidc');
                }
            }

            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }

    /**
     * Process the company_iomadoidc_mappings_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_iomadoidc_mappings_form(object $data): string {

        // Remove unwanted fields.
        unset($data->action);
        unset($data->submitbutton);

        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($data as $id => $value) {
                unset_config($id, 'auth_iomadoidc');
            }
            return get_string('companyoidcsettingsresetok', 'block_iomad_company_admin');
        } else {
            // Process everything else.
            foreach ($data as $id => $value) {
                set_config($id, $value, 'auth_iomadoidc');
            }

            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }

    /**
     * Process the company_iomadsaml2_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_iomadsaml2_form(object $data): string {
        global $postfix;

        // Remove unwanted fields.
        unset($data->action);
        unset($data->submitbutton);
        $idpmetadata = 'idpmetadata' . $postfix;
        unset($data->$idpmetadata);

        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($data as $id => $value) {
                unset_config($id, 'auth_iomadsaml2');
            }
            auth_iomadsaml2_update_sp_metadata();
            return get_string('companysaml2settingsresetok', 'block_iomad_company_admin');
        } else {

            // We need the current config.
            $iomadsaml2config = get_config('auth_iomadsaml2');

            // Process everything else.
            foreach ($data as $id => $value) {
                if (
                    $id == 'nameidpolicy' . $postfix ||
                    $id == 'spmetadatasign' . $postfix ||
                    $id == 'spentityid' . $postfix ||
                    $id == 'wantassertionssigned' . $postfix ||
                    $id == 'assertionconsumerservices' . $postfix
                ) {
                    if ($iomadsaml2config->$id != $value) {
                        auth_iomadsaml2_update_sp_metadata();
                    }
                }
                if ($id == 'assertionsconsumerservices' . $postfix) {
                    $value = implode(',', $value);
                }
                set_config($id, $value, 'auth_iomadsaml2');
            }

            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }

    /**
     * Process the company_iomadsaml2_mappings_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_iomadsaml2_mappings_form(object $data): string {

        // Remove unwanted fields.
        unset($data->action);
        unset($data->submitbutton);

        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($data as $id => $value) {
                unset_config($id, 'auth_iomadsaml2');
            }
            return get_string('companysaml2settingsresetok', 'block_iomad_company_admin');
        } else {

            // Process everything else.
            foreach ($data as $id => $value) {
                set_config($id, $value, 'auth_iomadsaml2');
            }

            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }

    /**
     * Process the company_auth_options_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_auth_options_form(object $data): string {
        global $postfix;

        // Set the list of general auth options we support.
        $authoptions = [
            'registerauth',
            'authloginviaemail',
            'guestloginbutton',
            'alternateloginurl',
            'showloginform',
            'forgottenpasswordurl',
            'auth_instructions',
            'allowemailaddresses',
            'denyemailaddresses',
            'enableloginrecaptcha',
            'recaptchapublickey',
            'recaptchaprivatekey',
            'loginpasswordtoggle',
        ];

        // Process the changes for auth options.
        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($authoptions as $authoption) {
                $field = $authoption . $postfix;
                unset_config($field);
            }
            return get_string('companyauthsettingsresetok', 'block_iomad_company_admin');
        } else {
            // Remove unwanted fields.
            unset($data->action);
            unset($data->submitbutton);

            // Process everything else.
            foreach ($authoptions as $authoption) {
                $field = $authoption . $postfix;
                if ($authoption == 'auth_instructions') {
                    if (!empty($data->$authoption['text'])) {
                        set_config($field, $data->$authoption['text']);
                    } else {
                        set_config($field, '');
                    }
                } else {
                    // Deal with empty form returns.
                    if (
                        $authoption == 'auth_instructions' ||
                        $authoption == 'alternateloginurl' ||
                        $authoption == 'forgottenpasswordurl' ||
                        $authoption == 'allowemailaddresses' ||
                        $authoption == 'denyemailaddresses' ||
                        $authoption == 'recaptchapublickey' ||
                        $authoption == 'recaptchaprivatekey'
                    ) {
                        if (empty($data->$authoption)) {
                            $data->$authoption = '';
                        }
                    }
                    set_config($field, $data->$authoption);
                }
            }
            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }

    /**
     * Process the company_auth_options_form
     *
     * @param object $data
     * @return string
     */
    public static function process_company_smtp_options_form(object $data): string {
        global $postfix;

        // Set the list of SMTP options we support.
        $smtpoptions = [
            'smtphosts',
            'smtpsecure',
            'smtpauthtype',
            'smtpoauthservice',
            'noreplyaddress',
            'smtpuser',
            'smtppass',
        ];

        // Process the changes for auth options.
        // Are we resetting everything?
        if (!empty($data->resetbutton)) {
            foreach ($smtpoptions as $smtpoption) {
                $field = $smtpoption . $postfix;
                unset_config($field);
            }
            return get_string('companysmtpsettingsresetok', 'block_iomad_company_admin');
        } else {
            // Make the changes.
            unset($data->action);
            unset($data->submitbutton);

            // Process the rest of the options.
            foreach ($smtpoptions as $smtpoption) {
                $field = $smtpoption . $postfix;
                if (empty($data->$smtpoption)) {
                    $data->$smtpoption = '';
                }
                set_config($field, $data->$smtpoption);
            }
            return get_string('companysavedok', 'block_iomad_company_admin');
        }
    }
}
