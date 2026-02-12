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
 * IOMAD Dashboard company MFA settings form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

defined('MOODLE_INTERNAL') || die;

use admin_setting;
use context_system;
use html_writer;
use local_iomad\{company, company_user, iomad};
use moodle_url;
use moodleform;
use tool_mfa;

require_once($CFG->libdir . '/adminlib.php');

/**
 * IOMAD Dashboard company MFA settings form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_mfa_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE, $DB, $postfix;

        $mform = & $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement(
            'html',
            html_writer::tag(
                'h2',
                format_string(get_string('mfasettings', 'tool_mfa') . " : " .
                              get_string('settings', 'moodle'))
            ));

        // Get the tool table and details.
        $managemfa = new tool_mfa\local\admin_setting_managemfa(true);
        $mform->addElement('html', $managemfa->output_html([]));

        $name = get_string('settings:enabled', 'tool_mfa');
        $mform->addElement('advcheckbox', 'enabled' . $postfix, $name);
        $mform->setDefault('enabled' . $postfix, false);

        $name = get_string('settings:lockout', 'tool_mfa');
        $description = get_string('settings:lockout_help', 'tool_mfa');
        $mform->addElement('text', 'lockout' . $postfix, $name);
        $mform->addElement('static', 'lockoutdesc', '', $description);
        $mform->setDefault('lockout' . $postfix, 10);
        $mform->setType('lockout' . $postfix, PARAM_INT);

        $name = get_string('settings:debugmode', 'tool_mfa');
        $description = get_string('settings:debugmode_help', 'tool_mfa');
        $mform->addElement('advcheckbox', 'debugmode' . $postfix, $name);
        $mform->addElement('static', 'debugmodedesc', '', $description);
        $mform->setDefault('debugmode' . $postfix, false);

        $name = get_string('settings:redir_exclusions', 'tool_mfa');
        $description = get_string('settings:redir_exclusions_help', 'tool_mfa');
        $mform->addElement('textarea', 'redir_exclusions' . $postfix, $name);
        $mform->addElement('static', 'redir_exclusionsdesc', '', $description);
        $mform->setType('redir_exclusions' . $postfix, PARAM_RAW);

        $name = get_string('settings:guidancecheck', 'tool_mfa');
        $description = get_string('settings:guidancecheck_help', 'tool_mfa');
        $mform->addElement('advcheckbox', 'guidance' . $postfix, $name);
        $mform->addElement('static', ',guidancedesc', '', $description);
        $mform->setDefault('guidance' . $postfix, false);

        $name = get_string('settings:guidancepage', 'tool_mfa');
        $description = get_string('settings:guidancepage_help', 'tool_mfa');
        $mform->addElement('textarea', 'guidancecontent' . $postfix, $name);
        $mform->addElement('static', 'guidancecontentdesc', '', $description);
        $mform->setType('guidancecontent' . $postfix, PARAM_RAW);

        $name = get_string('settings:guidancefiles', 'tool_mfa');
        $description = get_string('settings:guidancefiles_help', 'tool_mfa');
        $mform->addElement('filemanager', 'guidancefiles' . $postfix, $name, 0, [
            'maxfiles' => -1,
                ]);
        $mform->addElement('static', 'guidancefilesdesc', '', $description);

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        $this->add_action_buttons();
    }
}
