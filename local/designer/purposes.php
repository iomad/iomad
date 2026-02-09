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
 * Create group OR edit group settings.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot. "/local/designer/lib.php");
require_once($CFG->dirroot. "/local/designer/purposes.php");
require_once($CFG->dirroot. "/local/designer/form/purpose_table_settings_form.php");

use local_designer\purposes_table;

$action = optional_param('action', '', PARAM_TEXT);
$purposeid = optional_param('purpose', 0, PARAM_INT);

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/designer/purposes.php'));

if ($action && $purposeid) {
    switch($action) {
        case 'disable':
            $DB->set_field('local_designer_purposes', 'status', 0, ['id' => $purposeid]);
            break;
        case 'enable' :
            $DB->set_field('local_designer_purposes', 'status', 1, ['id' => $purposeid]);
            break;
    }
    redirect($PAGE->url);
}

$strgroups = get_string('managepurposes', 'format_designer');

$PAGE->set_title($strgroups);
$PAGE->set_heading($strgroups);
$PAGE->set_pagelayout('admin');
$PAGE->set_button($OUTPUT->single_button(new moodle_url('/local/designer/purpose.php', ['action' => 'create']),
    get_string('create_purpose', 'format_designer')));


$table = new purposes_table();
$table->define_baseurl($PAGE->url);
$table->is_downloadable(false);


$form = new local_designer\form\purpose_table_settings_form($PAGE->url);

$pagesize = get_user_preferences('local_designer_purpose_pagesize', 25);

if ($data = $form->get_data()) {
    $pagesize = $data->pagesize;
    set_user_preference('local_designer_purpose_pagesize', $data->pagesize);
} else {
    $form->set_data(['pagesize' => $pagesize]);
}


ob_start();
$table->out($pagesize, true);
$tablehtml = ob_get_contents();
ob_end_clean();

echo $OUTPUT->header();
echo $tablehtml;
$form->display();
echo $OUTPUT->footer();

