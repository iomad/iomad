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
 * Strings for component 'enrol_license', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    enrol
 * @subpackage license
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @translation German from Guido Hornig 2017, (@link https://lern.link)
 */

$string['customwelcomemessage'] = 'Individuell Willkommensnachricht';
$string['defaultrole'] = 'Standard Rollenzuweisung';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during license enrolment';
$string['enrolenddate'] = 'Enddatum';
$string['enrolenddaterror'] = 'Das Endedatum kann nicht voir dem Anfangsdatum liegen.';
$string['enrolme'] = 'Schulung jetzt starten ...';
$string['enrolperiod'] = 'Teilnahmedauer';
$string['enrolperiod_desc'] = 'Standard Teilnahemdauer (in Sekunden).'; // TODO: fixme!
$string['enrolstartdate'] = 'Anfangsdatum';
$string['groupkey'] = 'Gruppen-Anmeldeschlüssel nutzen.';
$string['groupkey_desc'] = 'Standardmäßige Anmeldung mit einem Gruppen-Anmeldeschlüssel.';
$string['groupkey_help'] = 'Zusätzlich zu anderen Restriktionen ist die Anmeldung nur durch einmalige Eingabe des Gruppen-Anmeldeschlüssels möglich. Gleichzeitig erfolgt die Zuordnung zu einer Kursgruppe. Gruppen können als verschiedene Termine des gleichen Trainings genutzt werden. Der Anmeldeschlüssel muss in den Gruppeneinstellungen und in den Kurseinstellungen eingetragen werden.';
$string['licensenolongervalid'] = 'Ihre Lizenz für diesen Kurs ist nicht mehr gültig.';
$string['license:unenrolself'] = 'Teilnehmer dürfen sich selbst abmelden.';
$string['longtimenosee'] = 'Inaktive Nutzer abmelden nach ';
$string['longtimenosee_help'] = 'Teilnehmer, die den Kurs lange Zeit nicht besucht haben, können automatisch abgemeldet werden. Hier stellen Sie die Zeit ein. Dieser Wert ist unabhängig von der Zeit, die durch die Lizenz bestimmt wird.';
$string['maxenrolled'] = 'MAximale Teilnehmerzahl';
$string['maxenrolled_help'] = 'LEgt die maximale Teilnehmerzahl fest. 0 (Null) bedeutet keine Begrenzung';
$string['maxenrolledreached'] = 'Die maximale Teilnehmeranzahl für Anmeldung mit einer Lizenz wurde bereits erreicht.';
$string['nolicenseinformationfound'] = 'Ihnen steht im Moment keine gültige Lizenz zur Verfügung. Bitte kontaktieren Sie die zuständige Person in Ihrem Unternehmen, damit Ihnen eine Lizenz zugeteilt wird.';
$string['password'] = 'Anmeldeschlüssel';
$string['password_help'] = 'Wird hier ein Anmeldeschlüssel eingetragen, dann ist die Anmeldung nur durch die Eingabe des Anmeldeschlüssels möglich. Der Anmeldeschlüssel ist für alle Teilnehmer gleich. Bleibt das Feld leer, so ist kein Anmeldeschlüssel notwendig. Er Schlüssel muss nur bei der Anmeldung eingetragen werden.';
$string['passwordinvalid'] = 'Dieser Anmeldschlüssel ist ungültig.';
$string['passwordinvalidhint'] = ' Bitte versuchen Sie es noch einmal. Der richtige Anmeldeschlüssel beginnt mit \'{$a}\'';
$string['pluginname'] = 'Lizensierte Anmeldung';
$string['pluginname_desc'] = 'Das Plugin \'Lizensierte Anmeldung\' ermöglicht Teilnehmern die Anmeldung, wenn ihnen eine Lizenz zugeordnet wurde. Intern wird die Anmelung über Anmeldemethode \'Manuelle Anmeldung\' ausgeführt, deshalb muss \'Manuelle Anmeldung\' für den Kurs ebenfalls aktiviert sein.';
$string['requirepassword'] = 'Anmeldeschlüssel notwendig';
$string['requirepassword_desc'] = 'mit dieser Einstellung wird verhindert, dass neue Kurse ohne Anmeldeschlüssel eingerichtet werden und verhindert die Entfernung von bestehenden Anmeldeschlüsseln.';
$string['role'] = 'Rolle zuseisen';
$string['license:config'] = 'Einstellungen für lizensierte Anmeldung';
$string['license:manage'] = 'Anmeldungen verwalten';
$string['license:unenrol'] = 'Teilnehmer abmelden';
$string['license:unenrollicense'] = 'Lizenz vom Kurs entfernen';
$string['sendcoursewelcomemessage'] = 'Willkommensnachricht senden';
$string['sendcoursewelcomemessage_help'] = 'Versenden einer E-Mail mit einer Willkommensnachricht, sobald eine Teilnehmer mit Lizenz sich anmeldet.';
$string['showhint'] = 'Anfang des Anmeldeschlüssels verraten';
$string['showhint_desc'] = 'Gibt allen die Möglichkeit den Anfangsbuchstaben des Anmeldeschlüssels anzuzeigen.';
$string['status'] = 'Lizensierte Anmeldung erlauben';
$string['status_desc'] = 'Lizensierte Anmeldung als Standardeinstellung auswählen.';
$string['status_help'] = 'Ermöglicht die lizensierte Anmeldung bei diesem Kurs. Dazu muss dem Teilnehmer eine Lizenz zugeordnet sein.';
$string['unenrollicenseconfirm'] = 'Möchten Sie sich wirklich vom Kurs "{$a}" abmelden?';
$string['usepasswordpolicy'] = 'Passwortregeln für den Anmeldeschlüssel verwenden';
$string['usepasswordpolicy_desc'] = 'Wählen Sie diese Einstellung, wenn Sie möchten, daß die Passwortregeln auch für Anmeldeschlüssel angewendet werden. Dadurch lassen sich einfach zu ratende Schlüssel vermeiden.';
$string['welcometocourse'] = 'Willkommen bei {$a}';
$string['welcometocoursetext'] = 'Willkommen im Kurs {$a->coursename}!

Schön, dass Sie sich Zeit nehmen und sich beim Kurs ($a->coursename) angemeldet haben.
Bitte nutzen Sie den Kurs ab jetzt. 
Haben Sie Ihr Benutzerprofil schon vollständig ausgefüllt? 
Sie könnten z.B. ein Bild hochladen oder eine Beschreibung einfügen.
Hier gehts zu Ihrem Profil: {$a->profileurl}';

