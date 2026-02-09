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
 * Action of purpose.
 *
 * @package    local_designer
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir . "/formslib.php");
require_once($CFG->dirroot. "/local/designer/form/purpose_form.php");
require_once($CFG->dirroot. "/local/designer/form/purpose_delete_form.php");


$action = required_param('action', PARAM_TEXT);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/designer/purpose.php', ['action' => $action]));
$PAGE->navbar->add(get_string('managepurposes', 'format_designer'), new moodle_url('/local/designer/purpose.php'));

require_login();

require_capability('local/designer:managepurposes', $context);

switch ($action) {
    case 'create':
        $PAGE->set_title(get_string('create_purpose', 'format_designer'));
        $PAGE->set_heading(get_string('create_purpose', 'format_designer'));
        $PAGE->navbar->add(get_string('create_purpose', 'format_designer'));

        $form = new local_designer\form\purpose_form($PAGE->url);

        if ($data = $form->get_data()) {
            $record = new \stdClass;
            $record->name = $data->name;
            $record->status = $data->status;
            $record->icon = $data->iconsearch;
            $record->custom = 1;
            $record->customclass = $data->customclass;
            $record->timeadded = time();
            $id = $DB->insert_record('local_designer_purposes', $record);
            \core\notification::success(get_string('purpose_created', 'format_designer'));
            redirect(new moodle_url('/local/designer/purposes.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/designer/purposes.php'));
        }
        break;

    case 'edit':
        $PAGE->set_title(get_string('edit_purpose', 'format_designer'));
        $PAGE->set_heading(get_string('edit_purpose', 'format_designer'));
        $PAGE->navbar->add(get_string('edit_purpose', 'format_designer'));

        $id = required_param('id', PARAM_INT);

        $purpose = $DB->get_record('local_designer_purposes', ['id' => $id], '*', MUST_EXIST);
        $form = new local_designer\form\purpose_form($PAGE->url,
            ['purpose' => (array) $purpose]);

        if ($data = $form->get_data()) {
            $data->icon = $data->iconsearch;
            $DB->update_record('local_designer_purposes', $data);

            \core\notification::success(get_string('purpose_edited', 'format_designer'));
            redirect(new moodle_url('/local/designer/purposes.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/designer/purposes.php'));
        } else {
            $theme = \theme_config::load($PAGE->theme->name);
            $faiconsystem = \core\output\icon_system_fontawesome::instance($theme->get_icon_system());
            $iconlist = $faiconsystem->get_core_icon_map();
            $purpose->icon = current(array_keys($iconlist, $purpose->icon));
            $form->set_data($purpose);
        }
        break;

    case 'delete':
        $PAGE->set_title(get_string('delete_purpose', 'format_designer'));
        $PAGE->set_heading(get_string('delete_purpose', 'format_designer'));
        $PAGE->navbar->add(get_string('delete_purpose', 'format_designer'));

        $id = required_param('id', PARAM_INT);

        $purpose = $DB->get_record('local_designer_purposes', ['id' => $id], '*', MUST_EXIST);
        $purpose->iconsearch = $purpose->icon;

        $form = new \local_designer\form\purpose_delete_form($PAGE->url);

        if ($data = $form->get_data()) {
            $DB->delete_records_select('format_designer_options', 'name = :name AND value = :value',
                ['name' => 'purpose', 'value' => $DB->sql_compare_text($purpose->name)]);
            $DB->delete_records('local_designer_purposes', ['id' => $data->id]);
            \core\notification::success(get_string('purpose_deleted', 'format_designer'));
            redirect(new moodle_url('/local/designer/purposes.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/designer/purposes.php'));
        } else {
            $form->set_data($purpose);
        }
        break;
}


echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

