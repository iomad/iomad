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

namespace mod_glossary\output;

use core\output\html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;

/**
 * Glossary renderer class.
 *
 * @package   mod_glossary
 * @copyright 2021 Peter Dias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the glossary tertiary nav
     *
     * @param standard_action_bar $actionmenu
     * @return bool|string
     * @throws \moodle_exception
     */
    public function main_action_bar(standard_action_bar $actionmenu) {
        $context = $actionmenu->export_for_template($this);

        return $this->render_from_template('mod_glossary/standard_action_menu', $context);
    }

    /**
     * Render the glossary entry header.
     *
     * @param stdClass $entry The glossary entry object
     * @param string $mode The display mode
     * @param int $headinglevel The heading level that the concept should be rendered in.
     * @param stdClass|null $user The user object, if rendering the author picture.
     * @param int|null $courseid The course id, if rendering the author picture.
     * @param bool $showlastedited Whether to show the last edited date.
     * @return string
     */
    public function concept_entry_header(
        stdClass $entry,
        string $mode,
        int $headinglevel,
        ?stdClass $user = null,
        ?int $courseid = null,
        bool $showlastedited = false,
    ): string {
        $contextdata = (object)[
            'concept' => glossary_print_entry_concept($entry, true, $headinglevel),
            'entryapproval' => glossary_get_entry_approval($entry, $mode),
        ];

        if ($user) {
            $contextdata->authorpicture = $this->output->user_picture($user, [
                'link' => false,
            ]);

            $fullname = fullname($user);
            $userurl = new moodle_url('/user/view.php', [
                'id' => $user->id,
                'course' => $courseid,
            ]);
            $authordate = (object)[
                'name' => html_writer::link($userurl, $fullname),
                'date' => userdate($entry->timemodified),
            ];
            $contextdata->authordate = get_string('bynameondate', 'glossary', $authordate);
        } else if ($showlastedited) {
            $contextdata->lastedited = get_string('lastedited', 'glossary', userdate($entry->timemodified));
        }

        return $this->render_from_template('mod_glossary/concept_entry_header', $contextdata);
    }
}
