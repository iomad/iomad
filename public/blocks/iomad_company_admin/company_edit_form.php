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
    if ($companytemplates = $DB->get_records(
        'local_iomad_company_role_templates_ass',
        ['companyid' => $companyid],
        null,
        'templateid')) {
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
                !$DB->get_record(
                    'local_iomad_company_users',
                    ['companyid' => $parentid, 'userid' => $USER->id, 'managertype' => 1])) {
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
if ($companytemplates = $DB->get_records(
    'local_iomad_company_role_templates_ass',
    ['companyid' => $companyid],
    null,
    'templateid')) {
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

    if ($isadding && empty($data->submitbutton)) {
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
    }
    else {
        if ($isadding && !empty($data->submitbutton)) {
            // Create company.
            company::create_company($data);

            // Redirect message.
            $redirectmessage = get_string('companycreatedok', 'block_iomad_company_admin');
        }
        else {
            // Updating an existing company.
            $data->id = $companyid;
            company::create_company($data);

            // Redirect message.
            $redirectmessage = get_string('companysavedok', 'block_iomad_company_admin');

            // Is the current user in the company?
            if (company_user::is_company_user()) {
                company_user::reload_company();
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
