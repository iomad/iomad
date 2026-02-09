<?php
// This file is part of filter_multilang2 - https://moodle.org/
//
// filter_multilang2 is free software: you can redistribute it and/or
// modify it under the terms of the GNU General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// filter_multilang2 is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with filter_multilang2.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Multilang2 filter settings
 *
 * @package    filter_multilang2
 * @copyright  2024 Iñaki Arenaza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = ['default' => new lang_string('parentlangdefault', 'filter_multilang2'),
                'include_en' => new lang_string('parentlangalwaysen', 'filter_multilang2'),
                'never' => new lang_string('parentlangnever', 'filter_multilang2')];
    $items[] = new admin_setting_configselect('filter_multilang2/parentlangbehaviour',
                                              new lang_string('parentlangbehaviour', 'filter_multilang2'),
                                              new lang_string('parentlangbehaviour_desc', 'filter_multilang2'),
                                              'default', $options);
    foreach ($items as $item) {
        $item->set_updatedcallback('\filter_multilang2\text_filter::reset_parentcache');
        $settings->add($item);
    }
}
