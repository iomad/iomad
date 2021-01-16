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
 * Allow overriding of roles by other roles.
 *
 * @package    core_role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$mode = required_param('mode', PARAM_ALPHANUMEXT);
$classformode = array(
    'assign' => 'core_role_allow_assign_page',
    'override' => 'core_role_allow_override_page',
    'switch' => 'core_role_allow_switch_page'
);
if (!isset($classformode[$mode])) {
    print_error('invalidmode', '', '', $mode);
}

$baseurl = new moodle_url('/admin/roles/allow.php', array('mode'=>$mode));
admin_externalpage_setup('defineroles', '', array(), $baseurl);

$syscontext = context_system::instance();
require_capability('moodle/role:manage', $syscontext);

$controller = new $classformode[$mode]();

if (optional_param('submit', false, PARAM_BOOL) && data_submitted() && confirm_sesskey()) {
    $controller->process_submission();
    $syscontext->mark_dirty();
    $event = null;
    // Create event depending on mode.
    switch ($mode) {
        case 'assign':
            $event = \core\event\role_allow_assign_updated::create(array('context' => $syscontext));
            break;
        case 'override':
            $event = \core\event\role_allow_override_updated::create(array('context' => $syscontext));
            break;
        case 'switch':
            $event = \core\event\role_allow_switch_updated::create(array('context' => $syscontext));
            break;
    }
    if ($event) {
        $event->trigger();
    }
    redirect($baseurl);
}

$controller->load_current_settings();

// Display the editing form.
echo $OUTPUT->header();

$currenttab = $mode;
require('managetabs.php');

$table = $controller->get_table();

echo $OUTPUT->box($controller->get_intro_text());

echo '<form action="' . $baseurl . '" method="post">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
echo html_writer::table($table);
echo '<div class="buttons">';
echo '<input type="submit" class="btn btn-primary" name="submit" value="' . get_string('savechanges') . '"/>';
echo '</div></form>';

echo $OUTPUT->footer();
