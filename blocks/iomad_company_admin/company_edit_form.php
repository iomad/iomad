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
 * IOMAD Dashboard company edit/create main page
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{company_created, company_updated, dashboard_page_viewed};
use block_iomad_company_admin\forms\company_edit_form;
use core\output\notification;

use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/includes/colourpicker.php');
require_once(__DIR__ . '/lib.php');

// Set up the custom colour picker.
MoodleQuickForm::registerElementType(
    'iomad_colourpicker',
    $CFG->dirroot . '/blocks/iomad_company_admin/includes/colourpicker.php',
    'MoodleQuickForm_iomad_colourpicker');

$companyid = optional_param('companyid', 0, PARAM_INT);
$parentid = optional_param('parentid', 0, PARAM_INT);
$new = optional_param('createnew', 0, PARAM_INT);
$parentchanged = optional_param('parentchanged', 0, PARAM_INT);

// Login and set up $PAGE.
require_login();

// We need the system context.
$systemcontext = context_system::instance();

// Condtionally set up the name for the page.
if (!$new) {
    $linktext = get_string('editcompany', 'block_iomad_company_admin');
} else {
    if (!empty($parentid)) {
        $linktext = get_string('createchildcompany', 'block_iomad_company_admin');
    } else {
        $linktext = get_string('addnewcompany', 'block_iomad_company_admin');
    }
}

// What type of company is this?
$child = false;
if (!$new) {
    // Set the companyid.
    $companyid = iomad::get_my_companyid($systemcontext);
    $companycontext = context_company::instance($companyid);

    // Are we alled to do this?
    iomad::require_capability('block/iomad_company_admin:company_edit', $companycontext);

    // Set adding to false and get the company record.
    $isadding = false;
    $companyrecord = $DB->get_record('local_iomad_companies', ['id' => $companyid], '*', MUST_EXIST);

    // Set the role template value so it displays nicely on the form.
    if ($companyrecord->previousroletemplateid == -1 ) {
        $companyrecord->previousroletemplateid = 'i';
    }
    // Sanitise some data.
    if (empty($companyrecord->usesignature)) {
        $companyrecord->usesignature = false;
    }
    if (empty($companyrecord->uselogo)) {
        $companyrecord->uselogo = false;
    }
    if (empty($companyrecord->useborder)) {
        $companyrecord->useborder = false;
    }
    if (empty($companyrecord->usewatermark)) {
        $companyrecord->usewatermark = false;
    }
    if (empty($companyrecord->showgrade)) {
        $companyrecord->showgrade = false;
    }

    // Deal with email templates.
    $companyrecord->templates = [];
    if ($companytemplates = $DB->get_records('local_iomad_company_role_templates_ass', ['companyid' => $companyid], null, 'templateid')) {
        $companyrecord->templates = array_keys($companytemplates);
    }

    // Get the dashboard page - if there is one.
    if ($companydashboard = $DB->get_record('local_iomad_company_pages', ['companyid' => $companyid, 'type' => 'dashboard'])) {
        $companyrecord->dashboard = $companydashboard->pageid;
    }
} else {
    // We are adding a new company. Set up some defaults.
    $isadding = true;
    $companyid = 0;
    $companyrecord = new stdClass;
    $companyrecord->templates = null;
    $companyrecord->previousroletemplateid = 0;
    $companyrecord->previousemailtemplateid = 0;
    $companyrecord->maxusers = 0;
    $companycontext = $systemcontext;

    // Get any default email templates.
    if ($emailtemplateset = $DB->get_record('local_iomad_email_templatesets', ['isdefault' => 1])) {
        $companyrecord->emailtemplate = $emailtemplateset->id;
    }

    // Do we have a parent company or has it changed?
    if (!empty($parentid) || $parentchanged) {
        if (!empty($parentid)) {
            $companycontext = context_company::instance($parentid);
            iomad::require_capability('block/iomad_company_admin:company_add_child', $companycontext);

            // We are adding a child company.
            $child = true;

            // Can this user manage this parentid?
            if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext) &&
                !$DB->get_record('local_iomad_company_users', ['companyid' => $parentid, 'userid' => $USER->id, 'managertype' => 1])) {
                // No.
                throw new moodle_exception(
                    get_string('invalidcompany', 'block_iomad_company_admin'),
                    'error',
                     new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
                die;
            }
        }

        // Deal with any already set form values from redirect/$SESSION.
        if (!empty($SESSION->current_editing_company_data)) {
            foreach ($SESSION->current_editing_company_data as $index => $value) {
                // Strip out certificate and CSS parts.
                if (in_array($index, ['bgcolor_content',
                                      'bgcolor_header',
                                      'companycertificateborder',
                                      'companycertificateseal',
                                      'companycertificatesignatue',
                                      'companycertificatewatermark',
                                      'compayfavicon',
                                      'companylogo',
                                      'companylogocompact',
                                      'currentparentid',
                                      'customcss',
                                      'headingcolor',
                                      'linkcolor',
                                      'showgrade',
                                      'maincolor',
                                      'useborder',
                                      'uselogo',
                                      'usesignature',
                                      'usewatermark'])) {
                    continue;
                } else {
                    $companyrecord->$index = $value;
                }
            }
            $companyrecord->id = $SESSION->current_editing_company_data['companyid'];

            // Is this an existing company we are moving?
            if (!empty($companyrecord->id)) {
                $isadding = false;
                $companyid = $companyrecord->id;
                $companycontext = context_company::instance($companyid);
                $new = false;
            }
            unset($SESSION->current_editing_company_data);
        }
    } else {
        // Check we can add a new company.
        iomad::require_capability('block/iomad_company_admin:company_add', $companycontext);
    }
}

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_edit_form.php', [
    'companyid' => $companyid,
    'parentid' => $parentid,
    'createnew' => $new,
]);

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Set the page heading.
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Are there any existing companies?
$firstcompany = !$DB->record_exists('local_iomad_companies', []);

// Set the dashboard URL as default.
$companylist = new moodle_url('/blocks/iomad_company_admin/index.php');

// Get the company logos etc.
$draftcompanylogoid = file_get_submitted_draft_itemid('companylogo');
file_prepare_draft_area($draftcompanylogoid,
                        $systemcontext->id,
                        'core_admin',
                        'logo' . $companyid, 0,
                        ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
$companyrecord->companylogo = $draftcompanylogoid;

$draftcompanylogocompactid = file_get_submitted_draft_itemid('companylogocompact');
file_prepare_draft_area($draftcompanylogocompactid,
                        $systemcontext->id,
                        'core_admin',
                        'logocompact' . $companyid, 0,
                        ['maxfiles' => 1]);
$companyrecord->companylogocompact = $draftcompanylogocompactid;

$draftcompanyfaviconid = file_get_submitted_draft_itemid('companyfavicon');
file_prepare_draft_area($draftcompanyfaviconid,
                        $systemcontext->id,
                        'core_admin',
                        'favicon' . $companyid, 0,
                        ['maxfiles' => 1]);
$companyrecord->companyfavicon = $draftcompanyfaviconid;

// Are we creating a child company?
if (!empty($new) && !empty($parentid)) {
    // Did we stash the company information in SESSION?
    if (!empty($SESSION->createcompanyform)) {
        // Is this recent?
        if (time() - $SESSION->createcompanyform->timecreated < 10) {
            $companyrecord = $SESSION->createcompanyform;
        }
        unset($SESSION->createcompanyform);
    }
    // Get the parent certificate files as default.
    $draftcompanycertificatesealid = file_get_submitted_draft_itemid('companycertificateseal');
    file_prepare_draft_area($draftcompanycertificatesealid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificateseal', $parentid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificateseal = $draftcompanycertificatesealid;
    $draftcompanycertificatesignatureid = file_get_submitted_draft_itemid('companycertificatesignature');
    file_prepare_draft_area($draftcompanycertificatesignatureid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificatesignature', $parentid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificatesignature = $draftcompanycertificatesignatureid;
    $draftcompanycertificateborderid = file_get_submitted_draft_itemid('companycertificateborder');
    file_prepare_draft_area($draftcompanycertificateborderid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificateborder', $parentid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificateborder = $draftcompanycertificateborderid;
    $draftcompanycertificatewatermarkid = file_get_submitted_draft_itemid('companycertificatewatermark');
    file_prepare_draft_area($draftcompanycertificatewatermarkid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificatewatermark', $parentid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificatewatermark = $draftcompanycertificatewatermarkid;

    // Deal with the image display options.
    $parentcompanyoptions = $DB->get_record('local_iomad_company_certificates', ['companyid' => $parentid]);
    $companyrecord->uselogo = $parentcompanyoptions->uselogo;
    $companyrecord->usesignature = $parentcompanyoptions->usesignature;
    $companyrecord->useborder = $parentcompanyoptions->useborder;
    $companyrecord->usewatermark = $parentcompanyoptions->usewatermark;
    $companyrecord->showgrade = $parentcompanyoptions->showgrade;

    // Deal with all of the CSS and logo stuff too.
    if (!empty($parentcompanyoptions->bgcolor_header)) {
        $companyrecord->bgcolor_header = $parentcompanyoptions->bgcolor_header;
    }
    if (!empty($parentcompanyoptions->bgcolor_content)) {
        $companyrecord->bgcolor_content = $parentcompanyoptions->bgcolor_content;
    }
    if (!empty($parentcompanyoptions->theme)) {
        $companyrecord->theme = $parentcompanyoptions->theme;
    }
    if (!empty($parentcompanyoptions->customcss)) {
        $companyrecord->customcss = $parentcompanyoptions->customcss;
    }
    if (!empty($parentcompanyoptions->maincolor)) {
        $companyrecord->maincolor = $parentcompanyoptions->maincolor;
    }
    if (!empty($parentcompanyoptions->headingcolor)) {
        $companyrecord->headingcolor = $parentcompanyoptions->headingcolor;
    }
    if (!empty($parentcompanyoptions->linkcolor)) {
        $companyrecord->linkcolor = $parentcompanyoptions->linkcolor;
    }
    if (!empty($parentcompanyoptions->custommenuitems)) {
        $companyrecord->custommenuitems = $parentcompanyoptions->custommenuitems;
    }

    $draftcompanylogoid = file_get_submitted_draft_itemid('companylogo');
    file_prepare_draft_area($draftcompanylogoid,
                            $systemcontext->id,
                            'core_admin',
                            'logo' . $parentid, 0,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companylogo = $draftcompanylogoid;

    $draftcompanylogocompactid = file_get_submitted_draft_itemid('companylogocompact');
    file_prepare_draft_area($draftcompanylogocompactid,
                            $systemcontext->id,
                            'core_admin',
                            'logocompact' . $parentid, 0,
                            ['maxfiles' => 1]);
    $companyrecord->companylogocompact = $draftcompanylogocompactid;

    $draftcompanyfaviconid = file_get_submitted_draft_itemid('companyfavicon');
    file_prepare_draft_area($draftcompanyfaviconid,
                            $systemcontext->id,
                            'core_admin',
                            'favicon' . $parentid, 0,
                            ['maxfiles' => 1]);
    $companyrecord->companyfavicon = $draftcompanyfaviconid;
} else {
    // If the parent has been set to none, we need to capture that here.
    if ($parentchanged) {
        $companyrecord->parentid = $parentid;
    }
    $draftcompanycertificatesealid = file_get_submitted_draft_itemid('companycertificateseal');
    file_prepare_draft_area($draftcompanycertificatesealid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificateseal', $companyid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificateseal = $draftcompanycertificatesealid;
    $draftcompanycertificatesignatureid = file_get_submitted_draft_itemid('companycertificatesignature');
    file_prepare_draft_area($draftcompanycertificatesignatureid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificatesignature', $companyid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificatesignature = $draftcompanycertificatesignatureid;
    $draftcompanycertificateborderid = file_get_submitted_draft_itemid('companycertificateborder');
    file_prepare_draft_area($draftcompanycertificateborderid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificateborder', $companyid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificateborder = $draftcompanycertificateborderid;
    $draftcompanycertificatewatermarkid = file_get_submitted_draft_itemid('companycertificatewatermark');
    file_prepare_draft_area($draftcompanycertificatewatermarkid,
                            $systemcontext->id,
                            'local_iomad',
                            'companycertificatewatermark', $companyid,
                            ['subdirs' => 0, 'maxbytes' => 15 * 1024, 'maxfiles' => 1]);
    $companyrecord->companycertificatewatermark = $draftcompanycertificatewatermarkid;
}
if ($domains = $DB->get_records('local_iomad_company_domains', ['companyid' => $companyid])) {
    $companyrecord->companydomains = '';
    foreach ($domains as $domain) {
        $companyrecord->companydomains .= $domain->domain ."\n";
    }
}

// Set up the form.
$mform = new company_edit_form($PAGE->url, $isadding, $companyid, $companyrecord, $firstcompany, $parentid, $child);
$companyrecord->templates = [];

// Set the parent company id if it's being passed.
if (!empty($companyrecord->parentid)) {
    $companyrecord->currentparentid = $companyrecord->parentid;
} else {
    $companyrecord->currentparentid = 0;
}
if (!empty($parentid)) {
    $companyrecord->parentid = $parentid;
}

// Get email template info.
if ($companytemplates = $DB->get_records('local_iomad_company_role_templates_ass', ['companyid' => $companyid], null, 'templateid')) {
    $companyrecord->templates = array_keys($companytemplates);
}

// Get certificate info.
if ($certificateinfo = $DB->get_record('local_iomad_company_certificates', ['companyid' => $companyid])) {
    $companyrecord->uselogo = $certificateinfo->uselogo;
    $companyrecord->usesignature = $certificateinfo->usesignature;
    $companyrecord->useborder = $certificateinfo->useborder;
    $companyrecord->usewatermark = $certificateinfo->usewatermark;
    $companyrecord->showgrade = $certificateinfo->showgrade;
}

// Set the form data.
$mform->set_data($companyrecord);

// Process the form.
if ($mform->is_cancelled()) {
    redirect($companylist);

} else if ($data = $mform->get_data()) {

    // Set some initial data.
    $data->userid = $USER->id;
    $createcompany = true;
    if (empty($data->validto)) {
        $data->validto = null;
    }

    // Add a new company.
    if ($isadding) {
        if (!empty($data->submitbutton)) {
            // Set up a profiles field category for this company.
            $catdata = (object) [];
            $catdata->sortorder = $DB->count_records('user_info_category') + 1;
            $catdata->name = $data->shortname;
            $data->profilecategoryid = $DB->insert_record('user_info_category', $catdata);

            // Deal with leading/trailing spaces.
            $data->name = trim($data->name);
            $data->shortname = trim($data->shortname);
            $data->code = trim($data->code);
            $data->city = trim($data->city);
            $data->region = trim($data->region);
            $data->custom1 = trim($data->custom1);
            $data->custom2 = trim($data->custom2);
            $data->custom3 = trim($data->custom3);

            // We hit create.
            $companyid = $DB->insert_record('local_iomad_companies', $data);
            $company = new company($companyid);

            $eventother = ['companyid' => $companyid];

            $event = company_created::create([
                'context' => $systemcontext,
                'userid' => $USER->id,
                'objectid' => $companyid,
                'other' => $eventother,
            ]);
            $event->trigger();

            // Set up default department.
            company::initialise_departments($companyid);
            $data->id = $companyid;

            // Set up course category for company.
            $coursecat = (object) [];
            $coursecat->name = $data->name;
            $coursecat->sortorder = 999;
            $coursecat->id = $DB->insert_record('course_categories', $coursecat);
            $coursecat->context = context_coursecat::instance($coursecat->id);
            $categorycontext = $coursecat->context;
            $categorycontext->mark_dirty();
            $DB->update_record('course_categories', $coursecat);
            fix_course_sortorder();
            $companydetails = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
            $companydetails->category = $coursecat->id;
            $DB->update_record('local_iomad_companies', $companydetails);
            $redirectmessage = get_string('companycreatedok', 'block_iomad_company_admin');

            // Deal with any parent company assignments.
            if (!empty($companydetails->parentid)) {
                $company = new company($companydetails->id);
                $company->assign_parent_managers($companydetails->parentid);
            }

            // Deal with any assigned templates.
            if (!empty($data->templates)) {
                $company->assign_role_templates($data->templates);
            }

            // Deal with certificate info.
            $certificateinforec = [
                'companyid' => $companyid,
                'uselogo' => $data->uselogo,
                'usesignature' => $data->usesignature,
                'useborder' => $data->useborder,
                'usewatermark' => $data->usewatermark,
                'showgrade' => $data->showgrade,
            ];
            $DB->insert_record('local_iomad_company_certificates', $certificateinforec);
        } else {
            // Stash the current form information to use when it reloads.
            $redirectmessage = "";
            $SESSION->createcompanyform = $data;
            $SESSION->createcompanyform->timecreated = time();
            $companylist = new moodle_url(
                '/blocks/iomad_company_admin/company_edit_form.php',
                [
                    'createnew' => true,
                    'parentid' => $data->parentid,
                ]);
            $createcompany = false;
        }
    } else {
        // Updating an existing company.
        $data->id = $companyid;

        // Set some defaults.
        if (!empty($data->usedefaultpaymentaccount)) {
            $data->paymentaccountid = '';
        }
        $company = new company($companyid);
        $oldcompany = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
        $oldtheme = $company->get_theme();
        $themechanged = $oldtheme != $data->theme;

        // Check if we have a new expiration date.
        if (!empty($data->validto)) {
            if (!empty($oldcompany->terminated) && $data->validto > $oldcompany->validto) {
                $data->terminated = 0;
            }
        }

        // Was the theme changed?
        if ($themechanged) {
            $company->update_theme($data->theme);
        }

        // Has the company name changed?
        if ($topdepartment = $company->get_company_parentnode($companyid)) {
            if ($topdepartment->name != $data->name) {
                $topdepartment->name = $data->name;
                $topdepartment->shortname = $data->shortname;
                $DB->update_record('local_iomad_company_departments', $topdepartment);
            }
        }

        // Set the default response.
        $redirectmessage = get_string('companysavedok', 'block_iomad_company_admin');

        // Has the company parentid changed?
        $companyparent = $company->get_parentid();
        if ($companyparent != $data->parentid) {
            // Is there currently a company parent set?
            if (!empty($companyparent)) {
                // Clear the old ones.
                $company->unassign_parent_managers($companyparent);
            }

            // Update the company record.
            $DB->update_record('local_iomad_companies', $data);

            if (!empty($data->parentid)) {
                // Assign the new ones.
                $company->assign_parent_managers($data->parentid);
            }
        }

        // Did we apply a role template?
        if (!empty($data->roletemplate)) {
            if ($data->roletemplate != 'i') {
                $data->previousroletemplateid = $data->roletemplate;
            } else {
                $data->previousroletemplateid = -1;
            }
        }

        // Did we apply an email template?
        if (!empty($data->emailtemplate)) {
            $data->previousemailtemplateid = $data->emailtemplate;
        }

        $DB->update_record('local_iomad_companies', $data);
        // Fire an event for this.
        $eventother = ['companyid' => $companyid,
                            'oldcompany' => json_encode($oldcompany)];

        $event = company_updated::create([
            'context' => $companycontext,
            'userid' => $USER->id,
            'objectid' => $companyid,
            'other' => $eventother,
        ]);
        $event->trigger();

        // Deal with certificate info.
        $certificateinforec = (array) $DB->get_record('local_iomad_company_certificates', ['companyid' => $companyid]);
        if (!empty($certificateinforec['id'])) {
            $certificateinforec['uselogo'] = $data->uselogo;
            $certificateinforec['usesignature'] = $data->usesignature;
            $certificateinforec['useborder'] = $data->useborder;
            $certificateinforec['usewatermark'] = $data->usewatermark;
            $certificateinforec['showgrade'] = $data->showgrade;
            $DB->update_record('local_iomad_company_certificates', $certificateinforec);
        } else {
            $certificateinforec = [
                'companyid' => $companyid,
                'uselogo' => $data->uselogo,
                'usesignature' => $data->usesignature,
                'useborder' => $data->useborder,
                'usewatermark' => $data->usewatermark,
                'showgrade' => $data->showgrade,
            ];
            $DB->insert_record('local_iomad_company_certificates', $certificateinforec);
        }

        // Deal with an dashboard stuff.
        $DB->delete_records('local_iomad_company_pages', ['companyid' => $companyid, 'type' => 'dashboard']);
        if (!empty($data->dashboard)) {
            $DB->insert_record('local_iomad_company_pages', ['companyid' => $companyid, 'pageid' => $data->dashboard, 'type' => 'dashboard']);
        }

        // Is the current user in the company?
        if (company_user::is_company_user()) {
            company_user::reload_company();
        }
    }

    // Only do the rest of the company create stuffs if we are not re-directing back to the form on parentid change.
    if ($createcompany) {
        $company = new company($data->id);

        // Deal with role templates.
        if (!empty($data->roletemplate)) {
            // We need to do something with the roles.
            if ($data->roletemplate == 'i') {
                if (!empty($data->parentid)) {
                    // Apply the same roles as per the parent company.
                    $company->apply_role_templates();
                }
            } else {
                $company->apply_role_templates($data->roletemplate);
            }
        }

        // Deal with email templates.
        if (!empty($data->emailtemplate) && iomad::has_capability('local/iomad:email_edit', $companycontext)) {
            // We need to do something with the email templates.
            $company->apply_email_templates($data->emailtemplate);
        }

        // Deal with any assigned templates.
        if (empty($data->templates)) {
            $data->templates = [];
        }
        $company->assign_role_templates($data->templates, true);

        // Deal with logo config settings.
        $fs = get_file_storage();
        if (!empty($data->companylogo)) {
            file_save_draft_area_files($data->companylogo,
                                       $systemcontext->id,
                                       'core_admin',
                                       'logo' . $data->id,
                                       0,
                                       ['maxfiles' => 1]);

            // Set the plugin config so it can actually be picked up.
            if ($files = $fs->get_area_files($systemcontext->id, 'core_admin', 'logo'. $data->id)) {
                foreach ($files as $file) {
                    if ($file->get_filename() != '.') {
                        break;
                    }
                }
                set_config('logo' . $data->id, $file->get_filepath() . $file->get_filename(), 'core_admin');
            } else {
                set_config('logo' . $data->id, '', 'core_admin');
            }
        }

        // Deal with logos.
        if (!empty($data->companylogocompact)) {
            file_save_draft_area_files($data->companylogocompact,
                                       $systemcontext->id,
                                       'core_admin',
                                       'logocompact' . $data->id,
                                       0,
                                       ['maxfiles' => 1]);

            // Set the plugin config so it can actually be picked up.
            if ($files = $fs->get_area_files($systemcontext->id, 'core_admin', 'logocompact'. $data->id)) {
                foreach ($files as $file) {
                    if ($file->get_filename() != '.') {
                        break;
                    }
                }
                set_config('logocompact' . $data->id, $file->get_filepath() . $file->get_filename(), 'core_admin');
            } else {
                set_config('logocompact' . $data->id, '', 'core_admin');
            }
        }

        // Deal with favicons.
        if (!empty($data->companyfavicon)) {
            file_save_draft_area_files($data->companyfavicon,
                                       $systemcontext->id,
                                       'core_admin',
                                       'favicon' . $data->id,
                                       0,
                                       ['maxfiles' => 1]);

            // Set the plugin config so it can actually be picked up.
            if ($files = $fs->get_area_files($systemcontext->id, 'core_admin', 'favicon'. $data->id)) {
                foreach ($files as $file) {
                    if ($file->get_filename() != '.') {
                        break;
                    }
                }
                set_config('favicon' . $data->id, $file->get_filepath() . $file->get_filename(), 'core_admin');
            } else {
                set_config('favicon' . $data->id, '', 'core_admin');
            }
        }

        // Deal with certificates.
        if (!empty($data->companycertificateseal)) {
            file_save_draft_area_files($data->companycertificateseal,
                                       $systemcontext->id,
                                       'local_iomad',
                                       'companycertificateseal',
                                       $data->id,
                                       ['subdirs' => 0, 'maxbytes' => 150 * 1024, 'maxfiles' => 1]);
        }
        if (!empty($data->companycertificatesignature)) {
            file_save_draft_area_files($data->companycertificatesignature,
                                       $systemcontext->id,
                                       'local_iomad',
                                       'companycertificatesignature',
                                       $data->id,
                                       ['subdirs' => 0, 'maxbytes' => 150 * 1024, 'maxfiles' => 1]);
        }
        if (!empty($data->companycertificateborder)) {
            file_save_draft_area_files($data->companycertificateborder,
                                       $systemcontext->id,
                                       'local_iomad',
                                       'companycertificateborder',
                                       $data->id,
                                       ['subdirs' => 0, 'maxbytes' => 150 * 1024, 'maxfiles' => 1]);
        }
        if (!empty($data->companycertificatewatermark)) {
            file_save_draft_area_files($data->companycertificatewatermark,
                                       $systemcontext->id,
                                       'local_iomad',
                                       'companycertificatewatermark',
                                       $data->id,
                                       ['subdirs' => 0, 'maxbytes' => 150 * 1024, 'maxfiles' => 1]);
        }

        // Delete any recorded domains for this company.
        $DB->delete_records('local_iomad_company_domains', ['companyid' => $companyid]);

        // Add any new ones back in.
        if (!empty($data->companydomains)) {
            $domainsarray = preg_split('/[\r\n]+/', $data->companydomains, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($domainsarray as $domain) {
                if (!empty($domain)) {
                    $DB->insert_record('local_iomad_company_domains', ['companyid' => $companyid, 'domain' => $domain]);
                }
            }
        }
    }

    redirect($companylist, $redirectmessage, notification::NOTIFY_SUCCESS);
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->display();

// Display the footer.
echo $OUTPUT->footer();
