<?php
      // format.php - course format featuring social forum
      //              included from view.php

    require_once($CFG->dirroot.'/mod/forum/lib.php');

    $strgroups  = get_string('groups');
    $strgroupmy = get_string('groupmy');
    $editing    = $PAGE->user_is_editing();

    if ($forum = forum_get_course_forum($course->id, 'social')) {

        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $modcontext = context_module::instance($cm->id);

    /// Print forum intro above posts  MDL-18483
        if (trim($forum->intro) != '') {
            $options = new stdClass();
            $options->para = false;
            $introcontent = format_module_intro('forum', $forum, $cm->id);

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $modcontext)) {
                $streditsummary  = get_string('editsummary');
                $introcontent .= '<div class="editinglink"><a title="'.$streditsummary.'" '.
                                 '   href="modedit.php?update='.$cm->id.'&amp;sesskey='.sesskey().'">'.
                                 $OUTPUT->pix_icon('t/edit', $streditsummary) . '</a></div>';
            }
            echo $OUTPUT->box($introcontent, 'generalbox', 'intro');
        }

        echo '<div class="subscribelink">', forum_get_subscribe_link($forum, $modcontext), '</div>';

        $numdiscussions = course_get_format($course)->get_course()->numdiscussions;
        if ($numdiscussions < 1) {
            // Make sure that the value is at least one.
            $numdiscussions = 1;
        }
        forum_print_latest_discussions($course, $forum, $numdiscussions, 'plain', '', false);

    } else {
        echo $OUTPUT->notification('Could not find or create a social forum here');
    }
