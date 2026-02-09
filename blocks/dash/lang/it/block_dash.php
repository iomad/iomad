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
$string['field:coursesinprogress'] = 'Corso in corso' ;
$string['label:coursesinprogress'] = 'Corsi in corso' ;
$string['field:iomadcompletedcoursesalltime'] = 'Totale corsi completati' ;
$string['label:iomadcompletedcoursesalltime'] = 'Totale corsi completati' ;
$string['campo:iomadenrolledcoursesalltime'] = 'Totale corsi iscritti' ;
$string['label:iomadenrolledcoursesalltime'] = 'Totale corsi iscritti' ;
$string['field:iomadcourseswithreminder'] = 'Corsi in corso (ricordati)' ;
$string['label:iomadcourseswithreminder'] = 'Corsi in corso (ricordati)' ;
$string['field:iomadcourseswithoutreminder'] = 'Corsi in corso' ;
$string['label:iomadcourseswithoutreminder'] = 'Corsi in corso' ;
$string['iomadenrolldate'] = 'Iscritto il' ;
$string['iomadcompleteddate'] = 'Completato il' ;
$string['iomadstatus'] = 'Stato';
$string['iomadcompleted'] = 'Completato';
$string['iomadreminded'] = 'Ricordato';
$string['iomadinprogress'] = 'In corso';
$string['status:infuture'] = 'In futuro';
$string['coursebutton'] = 'Pulsante del corso';
$string['viewcourse'] = 'Al corso';
$string['courseimagelink'] = ' Immagine del corso collegata';
$string['pagination_summary'] = 'Mostra {$a->limit_from} - {$a->limit_to} di {$a->total}';

$string['coursebutton'] = 'Azione';
$string['enrolnow'] = 'Iscriviti ora';
$string['smart_coursebutton'] = 'Azione';
//END
//START Thomas 4.1.2025: Strings for microlearning thread learning path
$string['unavailable'] = 'Non ancora disponibile';
$string['leanringpath_infocontent'] = 'Hai completato <b>{$a->completed}</b> su <b> {$a->total} </b> corsi. Il prossimo corso di questo viaggio di apprendimento è: <b> {$a->nextcourse}</b>.';
$string['iomadleanringpath_infocontent'] = 'Hai completato <b>{$a->completed}</b> su <b> {$a->total} </b> corsi. Il prossimo corso di questo viaggio di apprendimento è: <b> {$a->nextcourse}</b>, disponibile a partire da {$a->nextcoursestartdate}.';
$string['learningpathstart'] = 'Partenza';
$string['learningpathfinish'] = 'Arrivo';
$string['resumelearningpath'] = 'Continuare il viaggio';
$string['resumecourse'] = "Continuare il corso";
$string['completedcourse'] = "Il corso è stato completato";
$string['startcourse'] = "Iniziare il corso";
//END