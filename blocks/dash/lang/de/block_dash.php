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
 * Strings for component 'block_dash', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_dash
 * @copyright 2019 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// START: added by Thomas 7.8.2024: uses IOMAD tracking table to get historical data (all time)
$string['field:coursesinprogress'] = 'Laufende Kurse';
$string['label:coursesinprogress'] = 'Laufende Kurse';
$string['field:iomadcompletedcoursesalltime'] = 'Abgeschlossene Kurse insgesamt';
$string['label:iomadcompletedcoursesalltime'] = 'Abgeschlossene Kurse insgesamt';
$string['field:iomadenrolledcoursesalltime'] = 'Angemeldete Kurse insgesamt';
$string['label:iomadenrolledcoursesalltime'] = 'Angemeldete Kurse insgesamt';
$string['field:iomadcourseswithreminder'] = 'Laufende Kurse (erinnert)';
$string['label:iomadcourseswithreminder'] = 'Laufende Kurse (erinnert)';
$string['field:iomadcourseswithoutreminder'] = 'Laufende Kurse';
$string['label:iomadcourseswithoutreminder'] = 'Laufende Kurse';
$string['courseimagelink'] = 'Verlinktes Kursbild';
$string['iomadstatus'] = 'Status';
$string['iomadenrolldate'] = 'Angemeldet am';
$string['iomadcompleteddate'] = 'Abgeschlossen am';
$string['coursebutton'] = 'Kursbutton';
$string['viewcourse'] = 'Zum Kurs';
$string['pagination_summary'] = 'Zeigt {$a->limit_from} - {$a->limit_to} von {$a->total}';
$string['iomadcompleted'] = 'Abgeschlossen';
$string['iomadreminded'] = 'Erinnert';
$string['iomadinprogress'] = 'Laufend';
$string['status:infuture'] = 'In Zukunft';

$string['coursebutton'] = 'Aktion';
$string['enrolnow'] = 'Jetzt anmelden';
$string['smart_coursebutton'] = 'Aktion';
//END
//START Thomas 4.1.2025: Strings for microlearning thread learning path
$string['unavailable'] = 'Noch nicht verfügbar';
$string['leanringpath_infocontent'] = 'Sie haben <b>{$a->completed} </b>von <b>{$a->total}</b> Kursen abgeschlossen. Der nächste Kurs in dieser Lernreise ist: <b>{$a->nextcourse}</b>.';
$string['iomadleanringpath_infocontent'] = 'Sie haben <b>{$a->completed} </b>von <b>{$a->total}</b> Kursen abgeschlossen. Der nächste Kurs in dieser Lernreise ist: <b>{$a->nextcourse}</b>, verfügbar ab {$a->nextcoursestartdate}.';
$string['learningpathstart'] = 'Start';
$string['learningpathfinish'] = 'Ziel';
$string['resumelearningpath'] = 'Lernreise fortsetzen';
$string['resumecourse'] = "Kurs fortsetzen";
$string['completedcourse'] = "Kurs ist abgeschlossen";
$string['startcourse'] = "Kurs starten";
//END