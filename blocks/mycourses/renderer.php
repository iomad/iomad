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
 * local_messaging renderer
 *
 * @package    local_messaging
 * @copyright  2015 Howard miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir. '/coursecatlib.php');

class block_mycourses_renderer extends plugin_renderer_base {


    /**
     * Show list of defined templates with edit/delete
     * @param array $templates list of template objects
     * @param sting $baseurl base URL for links
     */
    public function display_courses($mycompletion) {
        global $OUTPUT, $CFG, $DB; 

        //  Block info header.
        $returntext = '';

        // Not started courses header block.
        $returntext .= '<div class="header" id="mycourses_notstarted"><h2>'.
              get_string('notstartedheader', 'block_mycourses').
              '</h2>';

        // Not started courses listings.
        $returntext .= '<div class="mycourseslisting">';
        if (!empty($mycompletion->mynotstartedenrolled)) {
            foreach ($mycompletion->mynotstartedenrolled as $mid => $notstarted) {
                // Display the course info.
                $coursecontext = context_course::instance($notstarted->courseid);
                $summaryinfo = file_rewrite_pluginfile_urls($notstarted->coursesummary, 'pluginfile.php',$coursecontext->id,'course','summary',null);

                $courserec = $DB->get_record("course", array("id"=>$notstarted->courseid));
                $course = new course_in_list($courserec);

                // display course overview files
                $contentimages = $contentfiles = '';
                $returntext .= '<div class="mycourselisting">';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $contentimages .= html_writer::tag('div',
                                html_writer::empty_tag('img', array('src' => $url)),
                                array('class' => 'courseimage'));
                    } else {
                        $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                        $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                                html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                        $contentfiles .= html_writer::tag('span',
                                html_writer::link($url, $filename),
                                array('class' => 'coursefile fp-filename-icon'));
                    }
                }
                $returntext .= $contentimages. $contentfiles;

                $returntext .= '<div class="mycourseheading">';
                $returntext .= '<h4 class="title"><a href="' . new moodle_url('/course/view.php', array('id' => $notstarted->courseid)) . '">' . $notstarted->coursefullname . '</a></h4></div>';
                if ($CFG->mycourses_showsummary) {
                    $returntext .= '<div class="mycoursesummary">' . $summaryinfo . '</div>';
                }
                $returntext .= '</div>';
            }
        }
        if (!empty($mycompletion->mynotstartedlicense)) {
            foreach ($mycompletion->mynotstartedlicense as $mid => $notstarted) {
                // Display the course info.
                $coursecontext = context_course::instance($notstarted->courseid);
                $summaryinfo = file_rewrite_pluginfile_urls($notstarted->coursesummary, 'pluginfile.php',$coursecontext->id,'course','summary',null);
                $courserec = $DB->get_record("course", array("id"=>$notstarted->courseid));
                $course = new course_in_list($courserec);

                // display course overview files
                $contentimages = $contentfiles = '';
                $returntext .= '<div class="mycourselisting">';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $contentimages .= html_writer::tag('div',
                                html_writer::empty_tag('img', array('src' => $url)),
                                array('class' => 'courseimage'));
                    } else {
                        $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                        $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                                html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                        $contentfiles .= html_writer::tag('span',
                                html_writer::link($url, $filename),
                                array('class' => 'coursefile fp-filename-icon'));
                    }
                }
                $returntext .= $contentimages. $contentfiles;
                $returntext .= '<div class="mycourseheading">';
                $returntext .= '<h4 class="title"><a href="' . new moodle_url('/course/view.php', array('id' => $notstarted->courseid)) . '">' . $notstarted->coursefullname . '</a></h4></div>';
                if ($CFG->mycourses_showsummary) {
                    $returntext .= '<div class="mycoursesummary">' . $summaryinfo . '</div>';
                }
                $returntext .= '</div>';
            }
        } else {
            $returntext .= '<div>' . get_string('nocourses', 'block_mycourses') . '</div>';
        }

        $returntext .= '</div></div><hr />';

        // In progress courses header block.
        $returntext .= '<div class="header" id="mycourses_inprogress"><h2>'.
              get_string('inprogressheader', 'block_mycourses').
              '</h2>';

        // In progress courses listings.
        $returntext .= '<div class="mycourseslisting">';
        if (!empty($mycompletion->myinprogress)) {
            foreach ($mycompletion->myinprogress as $inprogress) {
                // Display the course info.
                $coursecontext = context_course::instance($inprogress->courseid);
                $summaryinfo = file_rewrite_pluginfile_urls($inprogress->coursesummary, 'pluginfile.php',$coursecontext->id,'course','summary',null);

                $courserec = $DB->get_record("course", array("id"=>$inprogress->courseid));
                $course = new course_in_list($courserec);

                // display course overview files
                $contentimages = $contentfiles = '';
                $returntext .= '<div class="mycourselisting">';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $contentimages .= html_writer::tag('div',
                                html_writer::empty_tag('img', array('src' => $url)),
                                array('class' => 'courseimage'));
                    } else {
                        $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                        $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                                html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                        $contentfiles .= html_writer::tag('span',
                                html_writer::link($url, $filename),
                                array('class' => 'coursefile fp-filename-icon'));
                    }
                }
                $returntext .= $contentimages. $contentfiles;
                $returntext .= '<div class="mycourseheading">';
                $returntext .= '<h4 class="title"><a href="' . new moodle_url('/course/view.php', array('id' => $inprogress->courseid)) . '">' . $inprogress->coursefullname . '</a></h4></div>';
                if ($CFG->mycourses_showsummary) {
                    $returntext .= '<div class="mycoursesummary">' . $summaryinfo . '</div>';
                }
                $returntext .= '</div>';
            }
        } else {
            $returntext .= '<div>' . get_string('nocourses', 'block_mycourses') . '</div>';
        }

        $returntext .= '</div></div><hr />';

        // Completed courses header block.
        $returntext .= '<div class="header" id="mycourses_completed"><h2>'.
              get_string('completedheader', 'block_mycourses').
              '</h2>';

        $returntext .= '<div class="mycourseslisting">';
        // Completed courses listings.
        if (!empty($mycompletion->mycompleted)) {
            foreach ($mycompletion->mycompleted as $completed) {
                // Display the course info.
                $coursecontext = context_course::instance($completed->courseid);
                $summaryinfo = file_rewrite_pluginfile_urls($completed->coursesummary, 'pluginfile.php',$coursecontext->id,'course','summary',null);

                $courserec = $DB->get_record("course", array("id"=>$completed->courseid));
                $course = new course_in_list($courserec);

                // display course overview files
                $contentimages = $contentfiles = '';
                $returntext .= '<div class="mycourselisting">';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $contentimages .= html_writer::tag('div',
                                html_writer::empty_tag('img', array('src' => $url)),
                                array('class' => 'courseimage'));
                    } else {
                        $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                        $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                                html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                        $contentfiles .= html_writer::tag('span',
                                html_writer::link($url, $filename),
                                array('class' => 'coursefile fp-filename-icon'));
                    }
                }
                $returntext .= $contentimages. $contentfiles;

                $returntext .= '<div class="course_title">';
                $returntext .= '<h4 class="title"><a href="' . new moodle_url('/course/view.php', array('id' => $completed->courseid)) . '">' . $completed->coursefullname . '</a></h4></div>';
                if ($CFG->mycourses_showsummary) {
                    $returntext .= '<div class="mycoursesummary">' . $summaryinfo . '</div>';
                }

                if (!empty($completed->finalgrade)) {
                    $returntext .= "<div>"  . get_string('finalscore', 'block_mycourses') . ' ' . intval($completed->finalgrade) . "% ";
                    if (!empty($completed->certificate)) {
                        $returntext .= $completed->certificate;
                    }
                    $returntext .=  "</div>";
                } else {
                    $returntext .= "<div>" . get_string('finalscore', 'block_mycourses') . " 0%</div>";
                }

                $returntext .= '</div>';
            }
        } else {
            $returntext .= '<div>' . get_string('nocourses', 'block_mycourses') . '</div>';
        }

        $returntext .= '</div></div>';

        $returntext .= '<div class="coursearchivebutton"><a class="btn" href="' . new moodle_url('/blocks/mycourses/archive.php') . '">'
                       . get_string('archive', 'block_mycourses') . '</a></div>';
        return $returntext;
    }

    /**
     * Show list of defined templates with edit/delete
     * @param array $templates list of template objects
     * @param sting $baseurl base URL for links
     */
    public function display_archive($mycompletion) {
        global $OUTPUT, $CFG, $DB; 

        //  Block info header.
        $returntext = '<div class="header" id="mycourses_archive"><h2>'.
              get_string('myarchiveheader', 'block_mycourses').
              '</h2></div>';

        $returntext .= '<div class="myarchivelisting">';
        if (!empty($mycompletion->myarchive)) {
            foreach ($mycompletion->myarchive as $archive) {
                $coursecontext = context_course::instance($archive->courseid);
                $summaryinfo = file_rewrite_pluginfile_urls($archive->coursesummary, 'pluginfile.php',$coursecontext->id,'course','summary',null);

                $courserec = $DB->get_record("course", array("id"=>$archive->courseid));
                $course = new course_in_list($courserec);

                // display course overview files
                $contentimages = $contentfiles = '';
                $returntext .= '<div class="mycourselisting">';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $contentimages .= html_writer::tag('div',
                                html_writer::empty_tag('img', array('src' => $url)),
                                array('class' => 'courseimage'));
                    } else {
                        $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                        $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                                html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                        $contentfiles .= html_writer::tag('span',
                                html_writer::link($url, $filename),
                                array('class' => 'coursefile fp-filename-icon'));
                    }
                }
                $returntext .= $contentimages. $contentfiles;

                // Display the course info.
                $returntext .= '<div class="course_title">';
                $returntext .= '<h2 class="title"><a href="' . new moodle_url('/course/view.php', array('id' => $archive->courseid)) . '">' . $archive->coursefullname . '</a></h2></div>';
                if ($CFG->mycourses_showsummary) {
                    $returntext .= '<div class="mycoursesummary">' . $summaryinfo . '</div>';
                }
                
                if (!empty($archive->finalgrade)) {
                    $returntext .= "<div>" . get_string('finalscore', 'block_mycourses') . ' ' . intval($archive->finalgrade) . "% ";
                    if (!empty($archive->certificate)) {
                        $returntext .= $archive->certificate;
                    }
                    $returntext .= "</div>";
                } else {
                    $returntext .= "<div>" . get_string('finalscore', 'block_mycourses') . " 0%</div>";
                }
                $returntext .= '</div>';
            }
        }

        $returntext .= '</div>';

        $returntext .= '</br><a href="' . new moodle_url('/my/') . '">Back</a>';
        return $returntext;
    }
}
