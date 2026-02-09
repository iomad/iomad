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

defined('MOODLE_INTERNAL') || die();


// Lesson.
require_once($CFG->dirroot . "/mod/lesson/renderer.php");
class theme_iomadmoon_mod_lesson_renderer extends mod_lesson_renderer {

    /**
     * Returns HTML for a lesson inaccessible message
     *
     * @param string $message
     * @return <type>
     */
    public function lesson_inaccessible($message) {
        global $CFG;
        $output = $this->output->box_start('generalbox boxaligncenter my-2 w-100');
        $output .= $this->output->box_start('center my-2 w-100');
        $output .= $message;
        $output .= $this->output->box('<a class="btn btn-sm btn-secondary" href="' .
            $CFG->wwwroot .
            '/course/view.php?id=' .
            $this->page->course->id .
            '">' .
            get_string('returnto', 'lesson', format_string($this->page->course->fullname, true)) .
            '</a>', ' btn btn-sm btn-secondary');
        $output .= $this->output->box_end();
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to prompt the user to log in
     * @param lesson $lesson
     * @param bool $failedattempt
     * @return string
     */
    public function login_prompt(lesson $lesson, $failedattempt = false) {
        global $CFG;
        $output = $this->output->box_start('password-form');
        $output .= $this->output->box_start('generalbox boxaligncenter my-2 w-100');
        $output .= '<form id="password" method="post" action="' .
            $CFG->wwwroot .
            '/mod/lesson/view.php" autocomplete="off">';
        $output .= '<fieldset class="invisiblefieldset center">';
        $output .= '<input type="hidden" name="id" value="' .
            $this->page->cm->id .
            '" />';
        $output .= '<input type="hidden" name="sesskey" value="' .
            sesskey() .
            '" />';
        if ($failedattempt) {
            $output .= $this->output->notification(get_string('loginfail', 'lesson'));
        }
        $output .= get_string('passwordprotectedlesson', 'lesson', format_string($lesson->name)) . '<br /><br />';
        $output .= get_string('enterpassword', 'lesson') .
            " <input type=\"password\" name=\"userpassword\" /><br /><br />";
        $output .= "<input class='btn btn-sm btn-primary' type='submit' value='" .
            get_string('continue', 'lesson') .
            "' />";
        $output .= "<input class='btn btn-sm btn-danger' type='submit' name='backtocourse' value='" .
            get_string('cancel', 'lesson') . "' />";
        $output .= '</fieldset></form>';
        $output .= $this->output->box_end();
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a continue button
     * @param lesson $lesson
     * @param int $lastpageseen
     * @return string
     */
    public function continue_links(lesson $lesson, $lastpageseenid) {
        global $CFG;
        $output = $this->output->box(get_string('youhaveseen', 'lesson'), 'generalbox boxaligncenter m-0 p-0 w-100');

        $yeslink = html_writer::link(new moodle_url('/mod/lesson/view.php', array(
            'id' => $this->page->cm->id,
            'pageid' => $lastpageseenid, 'startlastseen' => 'yes'
        )), get_string('yes'), array('class' => 'btn btn-sm btn-primary'));
        $output .= html_writer::tag('span', $yeslink, array('class' => 'lessonbutton'));
        $output .= '&nbsp;';

        $nolink = html_writer::link(new moodle_url('/mod/lesson/view.php', array(
            'id' => $this->page->cm->id,
            'pageid' => $lesson->firstpageid, 'startlastseen' => 'no'
        )), get_string('no'), array('class' => 'btn btn-sm btn-secondary'));
        $output .= html_writer::tag('span', $nolink, array('class' => 'lessonbutton'));

        return $output;
    }


    /**
     * Returns HTML to display a message
     * @param string $message
     * @param single_button $button
     * @return string
     */
    public function message($message, single_button $button = null) {
        $output = $this->output->box_start('generalbox boxaligncenter wrapper-fw');
        $output .= $message;
        if ($button !== null) {
            $output .= $this->output->box($this->output->render($button), 'lessonbutton');
        }
        $output .= $this->output->box_end();
        return $output;
    }


    /**
     * Returns the HTML for displaying the end of lesson page.
     *
     * @param  lesson $lesson lesson instance
     * @param  stdclass $data lesson data to be rendered
     * @return string         HTML contents
     */
    public function display_eol_page(lesson $lesson, $data) {

        $output = '';
        $canmanage = $lesson->can_manage();
        $course = $lesson->courserecord;

        if ($lesson->custom && !$canmanage && (($data->gradeinfo->nquestions < $lesson->minquestions))) {
            $output .= $this->box_start('generalbox boxaligncenter wrapper-fw');
        }

        if ($data->gradelesson) {
            // We are using level 3 header because the page title is a sub-heading of lesson title (MDL-30911).
            $output .= $this->heading(get_string("congratulations", "lesson"), 3);
            $output .= $this->box_start('generalbox boxaligncenter wrapper-fw');
        }

        if ($data->notenoughtimespent !== false) {
            $output .= $this->paragraph(
                    get_string("notenoughtimespent", "lesson", $data->notenoughtimespent),
                    'center wrapper-fw');
        }

        if ($data->numberofpagesviewed !== false) {
            $output .= $this->paragraph(
                    get_string("numberofpagesviewed", "lesson", $data->numberofpagesviewed),
                    'center wrapper-fw');
        }
        if ($data->youshouldview !== false) {
            $output .= $this->paragraph(
                get_string("youshouldview", "lesson", $data->youshouldview),
                'center wrapper-fw');
        }
        if ($data->numberofcorrectanswers !== false) {
            $output .= $this->paragraph(
                get_string("numberofcorrectanswers", "lesson", $data->numberofcorrectanswers),
                'center wrapper-fw');
        }

        if ($data->displayscorewithessays !== false) {
            $output .= $this->box(
                get_string("displayscorewithessays", "lesson", $data->displayscorewithessays),
                'center wrapper-fw');
        } else if ($data->displayscorewithoutessays !== false) {
            $output .= $this->box(
                get_string("displayscorewithoutessays", "lesson", $data->displayscorewithoutessays),
                'center wrapper-fw');
        }

        if ($data->yourcurrentgradeisoutof !== false) {
            $output .= $this->paragraph(
                get_string("yourcurrentgradeisoutof", "lesson", $data->yourcurrentgradeisoutof),
                'center wrapper-fw');
        }
        if ($data->eolstudentoutoftimenoanswers !== false) {
            $output .= $this->paragraph(get_string("eolstudentoutoftimenoanswers", "lesson"));
        }
        if ($data->welldone !== false) {
            $output .= $this->paragraph(get_string("welldone", "lesson"));
        }

        if ($data->progresscompleted !== false) {
            $output .= $this->progress_bar($lesson, $data->progresscompleted);
        }

        if ($data->displayofgrade !== false) {
            $output .= $this->paragraph(get_string("displayofgrade", "lesson"), 'alert alert-warning');
        }

        $output .= $this->box_end(); // End. of Lesson button to Continue.

        if ($data->reviewlesson !== false) {
            $output .= '<div class="my-2 w-100">' . html_writer::link(
                $data->reviewlesson,
                get_string('reviewlesson', 'lesson'),
                array('class' => 'btn btn-sm btn-primary')
            ) . '</div>';
        }
        if ($data->modattemptsnoteacher !== false) {
            $output .= $this->paragraph(get_string("modattemptsnoteacher", "lesson"), 'centerpadded alert alert-warning');
        }

        if ($data->activitylink !== false) {
            $output .= $data->activitylink;
        }

        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $output .= html_writer::link(
            $url,
            get_string('returnto', 'lesson', format_string($course->fullname, true)),
            array('class' => 'btn btn-sm btn-secondary'));

        if (
            has_capability('gradereport/user:view', context_course::instance($course->id))
            && $course->showgrades && $lesson->grade != 0 && !$lesson->practice
        ) {
            $url = new moodle_url('/grade/index.php', array('id' => $course->id));
            $output .= '<div class="wrapper-fw mt-2">' . html_writer::link(
                $url,
                get_string('viewgrades', 'lesson'),
                array('class' => 'btn btn-sm btn-outline-primary')
            ) . '</div>';
        }
        return $output;
    }
}
