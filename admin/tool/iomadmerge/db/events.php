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
 * @package tool
 * @subpackage iomadmerge
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @var array Available handlers for merging events.
 *
 * Available events: merging_sucess, merging_failed
 */
if ($CFG->branch < 26) {
    $handlers = array(
        'merging_success' => array(
            'handlerfile'      => '/admin/tool/iomadmerge/lib/events/olduser.php',
            'handlerfunction'  => 'tool_iomadmerge_old_user_suspend',
            'schedule'         => 'instant',
            'internal'         => 1,
        ),
    );
} else {
    $observers = array(
        array(
            'eventname'     => 'tool_iomadmerge\event\user_merged_success',
            'callback'      => 'tool_iomadmerge_old_user_suspend',
            'includefile'   => '/admin/tool/iomadmerge/lib/events/olduser.php',
            'internal'      => 1
        )
    );
}
