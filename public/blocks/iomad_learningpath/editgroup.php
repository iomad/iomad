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
 * Group page for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\forms\editgroup_form;
use block_iomad_learningpath\output\editgroup_page;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
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
$learningpath = required_param('learningpath', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

// Page boilerplate stuff.
$url = new moodle_url('/blocks/iomad_learningpath/editgroup.php', ['id' => $id, 'learningpath' => $learningpath]);
$exiturl = new moodle_url('/blocks/iomad_learningpath/courselist.php', ['id' => $learningpath]);
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('grouptitle', 'block_iomad_learningpath'));
$PAGE->set_heading(get_string('grouptitle', 'block_iomad_learningpath'));
$output = $PAGE->get_renderer('block_iomad_learningpath');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// IOMAD stuff.
$companypaths = new companypaths($companyid, $systemcontext);
$paths = $companypaths->get_paths();
$PAGE->navbar->add(get_string('grouptitle', 'block_iomad_learningpath'), $url);

// Attempt to locate path.
$path = $companypaths->get_path($learningpath);

// Get/create group.
$group = $companypaths->get_group($learningpath, $id);

// Delete?
if ($delete) {
    $companypaths->delete_group($learningpath, $delete);
    redirect($exiturl);
}

// Form.
$form = new editgroup_form();

// Handle form activity.
if ($form->is_cancelled()) {

    redirect($exiturl);

} else if ($data = $form->get_data()) {
    $group->name = $data->name;
    $group->sequence = $data->sequence;
    if ($id == 0) {
        $id = $DB->insert_record('iomad_learningpathgroup', $group);
    } else {
        $DB->update_record('iomad_learningpathgroup', $group);
    }

    redirect($exiturl);
}

$form->set_data($group);

// Get renderer for page (and pass data).
$editgrouppage = new editgroup_page($companypaths, $form);

echo $OUTPUT->header();

echo $output->render($editgrouppage);

echo $OUTPUT->footer();
