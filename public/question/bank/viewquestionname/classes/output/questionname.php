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

namespace qbank_viewquestionname\output;

use core\output\action_link;
use core\output\inplace_editable;
use core\output\named_templatable;
use renderable;

/**
 * Question in place editing api call.
 *
 * @package    qbank_viewquestionname
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionname extends inplace_editable implements named_templatable, renderable {
    /**
     * Create the in-place editable based on the question data.
     *
     * @param \stdClass $question
     * @param action_link|null $actionlink If provided, the question name will link to the action link's URL, with the action link's
     *     text used as the link title.
     * @throws \coding_exception
     */
    public function __construct(\stdClass $question, ?action_link $actionlink = null) {
        global $OUTPUT;
        $formattedname = format_string($question->name);
        if ($actionlink) {
            $display = $OUTPUT->action_link($actionlink->url, $formattedname, attributes: ['title' => $actionlink->text]);
        } else {
            $display = $formattedname;
        }
        parent::__construct(
            'qbank_viewquestionname',
            'questionname',
            $question->id,
            question_has_capability_on($question, 'edit'),
            $display,
            $question->name,
            get_string('edit_question_name_hint', 'qbank_viewquestionname'),
            get_string('edit_question_name_label', 'qbank_viewquestionname', (object) [
                'name' => $question->name,
            ])
        );
    }

    public function get_template_name(\renderer_base $renderer): string {
        return 'core/inplace_editable';
    }
}
