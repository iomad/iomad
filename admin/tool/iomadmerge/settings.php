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
 * Version information
 *
 * @package    tool
 * @subpackage iomadmerge
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @author     Mike Holzer
 * @author     Forrest Gaston
 * @author     Juan Pablo Torres Herrera
 * @author     John Hoopes <hoopes@wisc.edu>, University of Wisconsin - Madison
 * @author     Jordi Pujol-Ahulló, SREd, Universitat Rovira i Virgili
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (has_capability('tool/iomadmerge:iomadmerge', context_system::instance())) {
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/iomadmerge/lib/autoload.php');
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/iomadmerge/lib.php');

    $ADMIN->add('accounts',
            new admin_category('tool_iomadmerge', get_string('pluginname', 'tool_iomadmerge')));
    $ADMIN->add('tool_iomadmerge',
            new admin_externalpage('tool_iomadmerge_merge', get_string('pluginname', 'tool_iomadmerge'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/iomadmerge/index.php',
            'tool/iomadmerge:iomadmerge'));
    $ADMIN->add('tool_iomadmerge',
            new admin_externalpage('tool_iomadmerge_viewlog', get_string('viewlog', 'tool_iomadmerge'),
            $CFG->wwwroot.'/'.$CFG->admin.'/tool/iomadmerge/view.php',
            'tool/iomadmerge:iomadmerge'));
}

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/iomadmerge/lib/autoload.php');
    require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/iomadmerge/lib.php');

    // Add configuration for making user suspension optional
    $settings = new admin_settingpage('iomadmerge_settings',
        get_string('pluginname', 'tool_iomadmerge'));

    $settings->add(new admin_setting_configcheckbox('tool_iomadmerge/suspenduser',
        get_string('suspenduser_setting', 'tool_iomadmerge'),
        get_string('suspenduser_setting_desc', 'tool_iomadmerge'),
        1));

    $supporting_lang = (tool_iomadmerge_transactionssupported()) ? 'transactions_supported' : 'transactions_not_supported';

    $settings->add(new admin_setting_configcheckbox('tool_iomadmerge/transactions_only',
        get_string('transactions_setting', 'tool_iomadmerge'),
        get_string('transactions_setting_desc', 'tool_iomadmerge') . '<br /><br />' .
            get_string($supporting_lang, 'tool_iomadmerge'),
        1));

    $config = tool_iomadmerge_config::instance();
    $none = get_string('none');
    $options = array('none' => $none);
    foreach ($config->exceptions as $exception) {
        $options[$exception] = $exception;
    }
    unset($options['my_pages']); //duplicated records make MyMoodle does not work.
    $settings->add(new admin_setting_configmultiselect('tool_iomadmerge/excluded_exceptions',
        get_string('excluded_exceptions', 'tool_iomadmerge'),
        get_string('excluded_exceptions_desc', 'tool_iomadmerge', $none),
        array('none'), //default value: empty => apply all exceptions.
        $options));

    // quiz attempts
    $quizStrings = new stdClass();
    $quizStrings->{QuizAttemptsMerger::ACTION_RENUMBER} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_RENUMBER, 'tool_iomadmerge');
    $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE, 'tool_iomadmerge');
    $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET, 'tool_iomadmerge');
    $quizStrings->{QuizAttemptsMerger::ACTION_REMAIN} = get_string('qa_action_' . QuizAttemptsMerger::ACTION_REMAIN, 'tool_iomadmerge');

    $quizOptions = array(
    QuizAttemptsMerger::ACTION_RENUMBER => $quizStrings->{QuizAttemptsMerger::ACTION_RENUMBER},
        QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE => $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_SOURCE},
        QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET => $quizStrings->{QuizAttemptsMerger::ACTION_DELETE_FROM_TARGET},
        QuizAttemptsMerger::ACTION_REMAIN => $quizStrings->{QuizAttemptsMerger::ACTION_REMAIN},
    );

    $settings->add(new admin_setting_configselect('tool_iomadmerge/quizattemptsaction',
        get_string('quizattemptsaction', 'tool_iomadmerge'),
        get_string('quizattemptsaction_desc', 'tool_iomadmerge', $quizStrings),
        QuizAttemptsMerger::ACTION_REMAIN,
        $quizOptions)
    );

    $settings->add(new admin_setting_configcheckbox('tool_iomadmerge/uniquekeynewidtomaintain',
        get_string('uniquekeynewidtomaintain', 'tool_iomadmerge'),
        get_string('uniquekeynewidtomaintain_desc', 'tool_iomadmerge'),
        1));

    // Add settings
    $ADMIN->add('tools', $settings);
}
