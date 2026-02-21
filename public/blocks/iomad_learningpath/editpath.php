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
 * Management page for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\forms\editpath_form;
use block_iomad_learningpath\output\editpath_page;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

// Security.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

// Parameters.
$id = optional_param('id', 0, PARAM_INT);

// Page boilerplate stuff.
$url = new moodle_url('/blocks/iomad_learningpath/editpath.php', ['id' => $id]);
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managetitle', 'block_iomad_learningpath'));
$output = $PAGE->get_renderer('block_iomad_learningpath');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// IOMAD stuff.
$companypaths = new companypaths($companyid, $systemcontext);
$paths = $companypaths->get_paths();
$company = new company($companyid);
$PAGE->set_heading(get_string('pathcompany', 'local_iomad_learningpath', $company->get_name()));

// Attempt to locate path.
$path = $companypaths->get_path($id);

// Check for default group.
$companypaths->check_group($id);

// Set up picture draft area.
$picturedraftid = file_get_submitted_draft_itemid('picture');
file_prepare_draft_area($picturedraftid, $systemcontext->id, 'block_iomad_learningpath', 'picture', $id,
    ['maxfiles' => 1]);

// Form.
$form = new editpath_form(null, ['id' => $id, 'companyid' => $companyid]);

// Handle form activity.
$exiturl = new moodle_url('/blocks/iomad_learningpath/manage.php');
if ($form->is_cancelled()) {

    redirect($exiturl);

} else if ($data = $form->get_data()) {
    $path->name = $data->name;
    $path->description = $data->description['text'];
    $path->active = $data->active;
    $path->timeupdated = time();
    if ($id == 0) {
        $path->timecreated = time();
        $path->active = 0;
        $id = $DB->insert_record('iomad_learningpath', $path);
    } else {
        $DB->update_record('iomad_learningpath', $path);
    }
    // Check if a file has been uploaded.
    $fs = get_file_storage();
    $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $data->picture, 'itemid', false);
    if (!empty($files)) {
        file_save_draft_area_files($data->picture, $systemcontext->id, 'block_iomad_learningpath', 'picture', $id,
            ['maxfiles' => 1]);
        // Resize image and create thumbnail.
        $companypaths->process_image($systemcontext, $id);
    } else {
        foreach (['mainpicture', 'thumbnail', 'picture'] as $filearea) {
            $companypaths->delete_file($systemcontext->id, 'block_iomad_learningpath', $filearea, $id, true);
        }
    }
    redirect($exiturl);
}

$path->description = ['text' => $path->description];
$path->picture = $picturedraftid;
$form->set_data($path);

// Get renderer for page (and pass data).
$editpathpage = new editpath_page($companypaths, $form);

echo $OUTPUT->header();

echo $output->render($editpathpage);

echo $OUTPUT->footer();
