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

declare(strict_types=1);

namespace local_iomadcustompage\local\services;

use local_iomadcustompage\form\audience;

/**
 * Audience fragment rendering service.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience_fragment {
    /**
     * Render the audience form fragment context and template.
     *
     * @param array $params
     * @return string
     */
    public static function render(array $params): string {
        global $PAGE;

        $audienceform = new audience(null, null, 'post', '', [], true, [
            'pageid' => $params['pageid'],
            'classname' => $params['classname'],
        ]);
        $audienceform->set_data_for_dynamic_submission();

        $context = [
            'instanceid' => 0,
            'heading' => $params['title'],
            'headingeditable' => $params['title'],
            'form' => $audienceform->render(),
            'canedit' => true,
            'candelete' => true,
            'showormessage' => $params['showormessage'],
        ];

        $renderer = $PAGE->get_renderer('local_iomadcustompage');
        return $renderer->render_from_template('local_iomadcustompage/local/audience/form', $context);
    }
}
