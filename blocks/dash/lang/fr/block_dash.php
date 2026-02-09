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
$string['field:coursesinprogress'] = 'Cours en cours' ;
$string['label:coursesinprogress'] = 'Cours en cours' ;
$string['field:iomadcompletedcoursesalltime'] = 'Total des cours complétés' ;
$string['label:iomadcompletedcoursesalltime'] = 'Total des cours complétés' ;
$string['field:iomadenrolledcoursesalltime'] = 'Total des cours inscrits' ;
$string['label:iomadenrolledcoursesalltime'] = 'Total des cours inscrits' ;
$string['field:iomadcourseswithreminder'] = 'Cours en cours (rappelés)' ;
$string['label:iomadcourseswithreminder'] = 'Cours en cours (rappelés)' ;
$string['field:iomadcourseswithoutreminder'] = 'Cours en cours' ;
$string['label:iomadcourseswithoutreminder'] = 'Cours en cours' ;
$string['iomadenrolldate'] = 'Inscrit le' ;
$string['iomadcompleteddate'] = 'Complété le' ;
$string['iomadstatus'] = 'Statut' ;
$string['iomadcompleted'] = 'Complété' ;
$string['iomadreminded'] = 'Rappelé' ;
$string['iomadinprogress'] = 'En cours' ;
$string['status:infuture'] = 'Dans le futur';
$string['coursebutton'] = 'Bouton de cours';
$string['viewcourse'] = 'Voir le cours';
$string['courseimagelink'] = 'Image de cours liée';
$string['pagination_summary'] = 'Affiche {$a->limit_from} - {$a->limit_to} de {$a->total}';

$string['coursebutton'] = 'Action';
$string['enrolnow'] = 'S\'inscrire maintenant';
$string['smart_coursebutton'] = 'Action';
//END
//START Thomas 4.1.2025: Strings for microlearning thread learning path
$string['unavailable'] = 'Pas encore disponible';
$string['leanringpath_infocontent'] = 'Vous avez complété <b>{$a->completed}</b> sur <b>{$a->total} </b> cours. Le prochain cours de ce voyage d\'apprentissage est : <b>{$a->nextcourse}</b>.';
$string['iomadleanringpath_infocontent'] = 'Vous avez complété <b>{$a->completed}</b> sur <b>{$a->total} </b> cours. Le prochain cours de ce voyage d\'apprentissage est : <b>{$a->nextcourse}</b>, disponible à partir de {$a->nextcoursestartdate}.';
$string['learningpathstart'] = 'Départ';
$string['learningpathfinish'] = 'Arrivée';
$string['resumelearningpath'] = 'Poursuivre le voyage';
$string['resumecourse'] = "Continuer le cours";
$string['completedcourse'] = "Le cours est complété";
$string['startcourse'] = "Démarrer le cours";
//END