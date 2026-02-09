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
 * A Moodle text filter to enable multilingual content.
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @copyright  Gaetan Frenoy <gaetan@frenoy.net>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// The actual implementation is under classes/text_filter.php
// This file is just a workaround to make the filter work
// in Moodle versions below 4.5.
class_alias(\filter_multilang2\text_filter::class, \filter_multilang2::class);
