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

// Workshop.
require_once($CFG->dirroot . "/mod/workshop/renderer.php");
class theme_iomadmoon_mod_workshop_renderer extends mod_workshop_renderer {
    /**
     * Renders the user plannner tool
     *
     * @param workshop_user_plan $plan prepared for the user
     * @return string html code to be displayed
     */
    protected function render_workshop_user_plan(workshop_user_plan $plan) {
        $o = ''; // Output HTML code.
        $numberofphases = count($plan->phases);
        $o .= html_writer::start_tag(
            'div',
            array(
                'class' => 'userplan',
                'aria-labelledby' => 'mod_workshop-userplanheading',
                'aria-describedby' => 'mod_workshop-userplanaccessibilitytitle',
            )
        );
        $o .= html_writer::start_tag(
            'div',
            array(
                'class' => 'rui-userplan-container'
            )
        );
        $o .= html_writer::span(
            get_string('userplanaccessibilitytitle', 'workshop', $numberofphases),
            'accesshide',
            array('id' => 'mod_workshop-userplanaccessibilitytitle')
        );
        $o .= html_writer::link(
            '#mod_workshop-userplancurrenttasks',
            get_string('userplanaccessibilityskip', 'workshop'),
            array('class' => 'accesshide')
        );
        foreach ($plan->phases as $phasecode => $phase) {
            $o .= html_writer::start_tag('dl', array('class' => 'phase'));
            $actions = '';

            if ($phase->active) {
                // Mark the section as the current one.
                $icon = $this->output->pix_icon('i/marked', '', 'moodle', ['role' => 'presentation']);
                $actions .= get_string('userplancurrentphase', 'workshop') . ' ' . $icon;
            } else {
                // Display a control widget to switch to the given phase or mark the phase as the current one.
                foreach ($phase->actions as $action) {
                    if ($action->type === 'switchphase') {
                        if (
                            $phasecode == workshop::PHASE_ASSESSMENT && $plan->workshop->phase == workshop::PHASE_SUBMISSION
                            && $plan->workshop->phaseswitchassessment
                        ) {
                            $icon = new pix_icon('i/scheduled', get_string('switchphaseauto', 'mod_workshop'));
                        } else {
                            $icon = new pix_icon('i/marker', get_string('switchphase' . $phasecode, 'mod_workshop'));
                        }
                        $actions .= $this->output->action_icon($action->url, $icon, null, null, true);
                    }
                }
            }

            if (!empty($actions)) {
                $actions = $this->output->container($actions, 'actions');
            }
            $classes = 'phase' . $phasecode;
            if ($phase->active) {
                $title = html_writer::span($phase->title, 'phasetitle', ['id' => 'mod_workshop-userplancurrenttasks']);
                $classes .= ' active';
            } else {
                $title = html_writer::span($phase->title, 'phasetitle');
                $classes .= ' nonactive';
            }
            $o .= html_writer::start_tag('dt', array('class' => $classes));
            $o .= $this->output->container($title . $actions);
            $o .= html_writer::start_tag('dd', array('class' => $classes . ' phasetasks'));
            $o .= $this->helper_user_plan_tasks($phase->tasks);
            $o .= html_writer::end_tag('dd');
            $o .= html_writer::end_tag('dl');
        }
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');
        return $o;
    }
}
