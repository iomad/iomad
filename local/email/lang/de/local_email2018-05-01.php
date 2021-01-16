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
//JuSt 30.04.2018
$string['add_template_button'] = 'Ersetzen';
$string['addnewtemplate'] = 'Standardvorlage überschreiben';
$string['blocktitle'] = 'E-Mail Vorlagen';
$string['body'] = 'Inhalt';
$string['controls'] = 'Steuerungselemente';
$string['crontask'] = 'Verwaltungs-E-Mails versenden';
$string['custom'] = 'individuell';
$string['default'] = 'standard';
$string['delete_template'] = 'Vorlage löschen';
$string['delete_template_button'] = 'Standard wiederherstellen';
$string['delete_template_checkfull'] = 'Ganz sicher das die Vorlage {$a} durch die Standardvorlage ersetzen?';
$string['edit_template'] = 'bearbeiten';
$string['editatemplate'] = 'Eine Vorlage bearbeiten';
$string['emailtemplatename'] = 'Name';
$string['email_data'] = 'Daten für Ersetzungen';
$string['email_templates_for'] = 'E-Mailvorlagen für \'{$a}\'';
$string['email_template'] = 'E-Mailvorlage \'{$a->name}\' für \'{$a->companyname}\'';
$string['email_template_send'] = 'Sende Nachicht an alle zugehörigen Nutzer der \'{$a->companyname}\' unter dem Namen \'{$a->name}\'';
$string['email:add'] = 'Standard E-Mailvorlagen überschreiben';
$string['email:delete'] = 'Auf Standard E-Mailvorlagen zurücksetzen';
$string['email:edit'] = 'E-Mailvorlagen bearbeiten';
$string['email:list'] = 'Zeige alle E-Mailvorlagen an';
$string['email:send'] = 'Verschicke vorlagenbasierte E-Mails';
$string['language'] = 'Sprache';
$string['language_help'] = 'Dies ist eine Liste aller gerade installierten Sprachpakete.Das ändern der E-Mailvorlagen wird Sie nur für diese Sprache ändern.'; 
$string['override'] = 'Ersetzen';
$string['pluginname'] = 'Lokal: Email';
$string['save_to_override_default_template'] = 'Als Standard speichern';
$string['select_email_var'] = 'E-Mail Platzhalter einfügen';
$string['select_course'] = 'Wähle Kurs aus';
$string['send_button'] = 'Senden';
$string['send_emails'] = 'Sende E-Mail';
$string['subject'] = 'Betreff';
$string['template_list_title'] = 'E-Mailvorlagen';
$string['templatetype'] = 'Vorlagen-Typ';

/* Email templates */
$string['approval_description'] = 'E-Mailvorlage an Manager senden, wenn ein Nutzer für eine Kursanfrage schickt.';
$string['approval_name'] = 'Nutzer-Kursanfrage für Manager';
$string['approval_subject'] = 'Neue Kurs-Zulassung';
$string['approval_body'] = '<p>Wollen Sie {User_FirstName} {User_LastName} zum {Course_FullName} zulassen.</p>
<p>Bitte melden Sie sich auf {Site_FullName} (<a href="{LinkURL}">{LinkURL}</a>) an, um diese Anfrage anzunehmen oder abzulehnen.</p>';

$string['approved_description'] = 'Vorlage an Nutzer senden, wenn Sie zu einem Kurs zugelassen wurden.';
$string['approved_name'] = 'Nutzer zu Kurs zugelassen.';
$string['approved_subject'] = 'Sie wurden zum {Course_FullName} zugelassen';
$string['approved_body'] = '<p>Sie haben jetzt Zugang zum {Course_FullName}. Um darauf zuzureifen, klicken Sie hier: <a href="{CourseURL}">{CourseURL}</a>.</p>';

$string['course_classroom_approval_description'] = 'An Manager senden, wenn ein Nutzer nach Zulassung für ein Traningsevent fragt.';
$string['course_classroom_approval_name'] = 'Anfrage an Manager zur Event-Teilnahmebestätigung eines Nutzers';
$string['course_classroom_approval_subject'] = 'Zulassung zu einem Präsenstraining';
$string['course_classroom_approval_body'] = '<p>Sie werden gebeten {Approveuser_FirstName} {Approveuser_LastName} den Zugriff zum Präsestraining {Event_Name} zu genehmigen -</p>
<br>
Uhreit : {Classroom_Time}</br>
Ort : {Classroom_Name}</br>
Addresse : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}</br>
</br>
<p>Bilde melden Sie sich selber hier an {Site_FullName} ('.$CFG->wwwroot.') um diese Anfrage anzunehmen oder abzulehnen.</p>';

$string['course_classroom_approved_description'] = 'Template sent out to users when they have been granted access to a training event.';
$string['course_classroom_approved_name'] = 'User training event access approved';
$string['course_classroom_approved_subject'] = 'Face to face training event approved';
$string['course_classroom_approved_body'] = '<p>You have been approved access to the face to face training course {Event_Name} at the following event -</p>
</br>
Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_denied_description'] = 'Template sent out to users when they have been denied access to a training event.';
$string['course_classroom_denied_name'] = 'User training event access denied';
$string['course_classroom_denied_subject'] = 'Face to face training event approval denied';
$string['course_classroom_denied_body'] = '<p>Your approval request has been rejected for {Event_Name} at the following event -</p>
</br>
Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_manager_denied_description'] = 'Template sent out to department manager when a user has been denied access to a training event.';
$string['course_classroom_manager_denied_name'] = 'Department mananager training event access denied';
$string['course_classroom_manager_denied_subject'] = 'Face to face training event approval denied by company manager';
$string['course_classroom_manager_denied_body'] = '<p>The approval request for {Approveuser_FirstName} {Approveuser_LastName} has been rejected by {User_FirstName} {User_LastName} ({User_Email}) for {Event_Name} at the following event -</p>
</br>
Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}';

$string['course_classroom_approval_request_description'] = 'Template sent out to user when they request access to a training event.';
$string['course_classroom_approval_request_name'] = 'User training event request confirmation';
$string['course_classroom_approval_request_subject'] = 'New face to face training event approval request sent';
$string['course_classroom_approval_request_body'] = '<p>You have asked for access to the face to face training course {Event_Name} at the following event -</p>
</br>
Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}</br>
<p>You will be notified once your manager has approved or denied access.</p>';

$string['courseclassroom_approved_description'] = 'Template sent out to users when they have been granted access to a training event.';
$string['courseclassroom_approved_name'] = 'User training event approved';
$string['courseclassroom_approved_subject'] = 'You have been approved access to {Event_Name}';
$string['courseclassroom_approved_body'] = '<p>You have been granted access to course {Event_Name}.  To access this, please click on <a href="{CourseURL}">{CourseURL}</a>.<p>';

$string['course_completed_manager_description'] = 'Sende Vorlage an Manager, wenn ein Nutzer den Kurs abschließt.';
$string['course_completed_manager_name'] = '\"Manager Kurs abgeschlossen\" Nachicht';
$string['course_completed_manager_subject'] = 'Student course completion report';
$string['course_completed_manager_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName}</p>
<p>{Course_ReportText}</p>';

$string['user_added_to_course_description'] = 'Sende Vorlage an Nutzer, wenn Sie in einem Kurs sind.';
$string['user_added_to_course_name'] = 'Nutzer zu Kurs hinzugefügt';
$string['user_added_to_course_subject'] = 'Benutzer zum {Course_FullName} hinzugefügt';
$string['user_added_to_course_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName}</p>
<br>
<p>Sie haben jetzt Zugriff auf das Online Training zum {Course_FullName}.  Bitte melden Sie sich auf  <a href="{CourseURL}">{CourseURL}</a> um daran teinzunehmen.</p>';

$string['invoice_ordercomplete_description'] = 'Sende E-Mailvorlage an Nutzer, wennn er im Shop eine Bestellung tätigt.';
$string['invoice_ordercomplete_name'] = 'Nutzer Bestellung getätigt';
$string['invoice_ordercomplete_subject'] = 'Danke für ihre Bestellung auf {Site_ShortName}';
$string['invoice_ordercomplete_body'] = '<p>Sehr geehrte(r) Frau/Herr {User_FirstName} {User_LastName}</p>
<p>Ihren Bestellschein finden Sie hier {Invoice_Reference}</p>
<p>Danke, dass Sie folgendes bestellt haben:</p>
<p>{Invoice_Itemized}</p>
<p>Lizenzen werden erstellt oder Teilnahmen vom Administrator genehmigt, sobald diese Bestellung bezahlt wurde.</p>';

$string['invoice_ordercomplete_admin_description'] = 'E-Mailvorlage wird an den Systemadmin gesendet, wenn eine Bestelllung getätigt wird.';
$string['invoice_ordercomplete_admin_name'] = 'Admin-Bestellung getätigt';
$string['invoice_ordercomplete_admin_subject'] = 'E-Commerce Bestellung (Bestellschein {Invoice_Reference})';
$string['invoice_ordercomplete_admin_body'] = '<p>Sehr geehrte(r) E-Commerce Admin</p>
<p>DIe folgende Bestellung wurde gerade von {Invoice_FirstName} {Invoice_LastName} von {Invoice_Company} getätigt.</br>
Der Bestellschein wurde ihnen in einer E-Mail zugeschickt.</p>

<p>{Invoice_Itemized}</p>';

$string['advertise_classroom_based_course_description'] = 'E-Mailvorlage senden, wenn ein Manager ein neues Trainingsevent ankündigt.';
$string['advertise_classroom_based_course_name'] = 'Neues Trainingsevent ankündigen';
$string['advertise_classroom_based_course_subject'] = 'Kurs {Course_FullName}';
$string['advertise_classroom_based_course_body'] = '<o>Diese Nachicht soll Sie über folgenden Kurs informieren:</p>
<p>{Course_FullName}</p>


<p>Dieser wird im Raum {Classroom_Name} stattfinden, an folgender Adresse:</p>
<p>{Classroom_Address}</br>
{Classroom_City} {Classroom_Postcode}</br>
{Classroom_Country}</br>

<p>mit einer Kapazität von {Classroom_Capacity} Teilnehmern.</p>

<p>Bitte klicken Sie hier <a href="{CourseURL}">{CourseURL}</a> um mehr über den Kurs und das Buch an diesem Event herauszufinden.</p>';

$string['user_signed_up_for_event_description'] = 'Vorlage an Nutzer senden, wenn diese sich für ein Trainingsevent anmelden, für das keine Managererlaubnis nötig ist.';
$string['user_signed_up_for_event_name'] = 'Nutzer-Anmeldung für Trainingsevent';
$string['user_signed_up_for_event_subject'] = 'Anwesenheitsvermerk {Course_FullName}';
$string['user_signed_up_for_event_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Sie haben sich zum Anwesenheitstraung zum {Course_FullName} an folgendem Event angemeldet-</p>

<p>Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}</br>

<p>Please ensure you have completed an pre-course tasks required before attendance</p>';

$string['user_removed_from_event_description'] = 'Vorlage zur Bestätigung an Nutzer senden, wenn Sie von einem Trainingsevent entfernt werden.';
$string['user_removed_from_event_name'] = 'Nutzer-Trainingsevent storniert';
$string['user_removed_from_event_subject'] = 'Stornierungsschein {Course_FullName}';
$string['user_removed_from_event_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Sie wurden als am {Course_FullName} nicht mehr teilnehmend markiert, der an folgendem Event stattfindet-</p>

<p>Time : {Classroom_Time}</br>
Location : {Classroom_Name}</br>
Address : {Classroom_Address}</br>
          {Classroom_City} {Classroom_Postcode}';

$string['license_allocated_description'] = 'Sende Vorlage an Nutzer, wenn Ihnen eine Lizenz für den Kurs zugewiesen wird.';
$string['license_allocated_name'] = 'Nutzer Lizenz zugewiesen';
$string['license_allocated_subject'] = 'Zugriff zum {Course_FullName} gewährt';
$string['license_allocated_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Sie haben jetzt Zugroff auf das Online-Training zum {Course_FullName}. Um am Training teilzunehmen, klicken Sie hier <a href="{CourseURL}">{CourseURL}</a>.</br>
Sobald Sie dem Kurs beigetreten sind, haben Sie für {License_Length} Tage Zugriff darauf.<br>
Der Zugang kann bis {License_Valid} aktiviert werden. Danach verfällt die Lizenz .</p>';

$string['license_reminder_description'] = 'Erinnerung an Teilnehmer vom Manager, dass Sie noch keinen Zugang zu einem Kurs haben, für den Ihnen schon eine Lizenz gegeben wurde.';
$string['license_reminder_name'] = 'User license activation reminder';
$string['license_reminder_subject'] = 'Erinnerung: Sie wurden dem {Course_FullName} zugewiesen';
$string['license_reminder_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Sie haben jetzt Zugroff auf das Online-Training zum {Course_FullName}. Um am Training teilzunehmen, klicken Sie hier <a href="{CourseURL}">{CourseURL}</a>.</br>
Sobald Sie dem Kurs beigetreten sind, haben Sie für {License_Length} Tage Zugriff darauf.Ungenutzer Zugang läuft nach {License_Valid} Tagen ab.</p>';

$string['license_removed_description'] = 'Vorlage an Nutzer senden, wenn ihnen eine Kurs-Lizenz entzogen wurde.';
$string['license_removed_name'] = 'Nutzer Kurs-Lizenz widerrufen';
$string['license_removed_subject'] = 'Zugang zum{Course_FullName} entfernt';
$string['license_removed_body'] = '<p>Ihr Zugang zum {Course_FullName} wurde widerrufen. Wenn Sie dies für einen Fehler halten, bitte melden Sie sich bei ihrem Trainingsmanager</p>';

$string['password_update_description'] = 'Vorlage an Nutzer senden, wenn Ihr Kennwort von einem Manager geändert wurde.';
$string['password_update_name'] = 'Nutzer Kennwort geändert';
$string['password_update_subject'] = 'Kennwortänderungsnachicht an {User_FirstName}';
$string['password_update_body'] = '<p>Ihr Kennwort wurde vom Administrationspersonal aktualisiert. Ihr neues Kennwort ist</p>

<p>{User_Newpassword}</p>

<p>Wenn Sie es ändern wollen, besuchen Sie <a href="{LinkURL}">{LinkURL}</a></p>';

$string['completion_warn_user_description'] = 'Vorlage an Nutzer senden, wenn Sie einen Kurs nicht in der dafüur vorgesehenen Zeit beendet haben.';
$string['completion_warn_user_name'] = 'Nutzer Kursbeendigungswarnung';
$string['completion_warn_user_subject'] = 'Nachicht: Kurs {Course_FullName} wurde nicht beendet';
$string['completion_warn_user_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>
<p>Sie haben Ihr Trainung zum {Course_FullName} immer noch nicht vollständig gemacht.Um dies zu korrigieren, klicken Sie hier <a href="{CourseURL}">{CourseURL}</a></p>';

$string['completion_warn_manager_description'] = 'An Manager um Sie darüber zu informieren, dass ein Nutzer den Kurs nicht beendet hat.';
$string['completion_warn_manager_name'] = 'Manager-Kurs Beendigungswarnung';
$string['completion_warn_manager_subject'] = 'Nutzer Beendigungsfehlermeldung';
$string['completion_warn_manager_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>
<p>Die folgenden Nutzer haben ihre Trainings nicht innerhalb des normalen Zeitraums erledigt:</p>

<p>{Course_ReportText}</p>';

$string['completion_digest_manager_description'] = 'Vorlage an Manager senden, um Sie zu informieren, dass Nutzer ihre Kurse nicht in der konfigurierten Zeit beendet haben, wenn Manager-E-Mails als Zusammenfassung verschickt werden.';
$string['completion_digest_manager_name'] = 'Manager Kursbeendungswarnung - Zusammenfassung';
$string['completion_digest_manager_subject'] = 'Nutzer-Vollständigkeitsbericht';
$string['completion_digest_manager_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>
<p>Die folgenden Nutzer haben ihr Training innerhalb der letzten Woche abgeschlossen:</p>

<p>{Course_ReportText}</p>';

$string['expiry_warn_user_description'] = 'An Nutzer senden, wenn ihr Training in einem Kurs bald ausläuft.';
$string['expiry_warn_user_name'] = 'Nutzer-Training-Auslaufswarnung';
$string['expiry_warn_user_subject'] = 'Nachicht: Zulassung zum {Course_FullName} l.';
$string['expiry_warn_user_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>
<p>Ihre Trainingszulassung zum {Course_FullName} läuft bald aus. Arrangieren Sie eine Neuzulassung falls angemessen.</p>';

$string['expiry_warn_manager_description'] = 'An Manager senden, um Sie über Nutzer zu informieren, deren Training bald ausläuft.';
$string['expiry_warn_manager_name'] = 'Manager-Training-Auslaufswarnung';
$string['expiry_warn_manager_subject'] = 'Anerkennungsauslaufsmeldung';
$string['expiry_warn_manager_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>
<p>Die Anerkennung der folgenden Nutzer läuft bald aus :</p>

<p>{Course_ReportText}</p>';

$string['expire_description'] = 'An Nutzer senden, wenn ihr Training in einem Kurs ausgelaufen ist.';
$string['expire_name'] = 'Nutzer-Training ausgelaufen';
$string['expire_subject'] = 'Kurs läuft aus';
$string['expire_body'] = '<p>Ihr Training im {Course_FullName} läuft bald aus.</p>';

$string['expire_manager_description'] = 'An Manager senden, um Sie über Nutzer zu informieren, deren Trainingslizenz ausgelaufen ist.';
$string['expire_manager_name'] = 'Manager Training Auslaufswarnung';
$string['expire_manager_subject'] = 'Anerkennungsauslaufsmeldung im {Course_FullName}';
$string['expire_manager_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_FullName},</p>
<p>Die Anerkennung volgender Nutzer im Kurs {Course_FullName} ist ausgelaufen :</p>

<p>{User_ReportText}</p>';

$string['user_reset_description'] = 'An Nutzer senden, wenn ein Manager ihre Nutzer-Informationen verändert.';
$string['user_reset_name'] = 'Nutzer Konto Zurücksetzung';
$string['user_reset_subject'] = 'Die Anmeldeinformationen für Ihr Konto wurden zurückgesetzt';
$string['user_reset_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Ihre Konto-Informationen sind:</p>

<p>Nutzername: {User_Username}</br>
Kennwort: {User_Newpassword}</br>
(Sie werden Ihr Kennwort ändern müssen, wenn Sie sich anmelden)</p>

<p>Mit freundlichen Grüßen,</p>

<p>{Sender_FirstName} {Sender_LastName}</p>';

$string['user_create_description'] = 'An neue Nutzer senden, wenn ein neues Konto erstellt wurde.';
$string['user_create_name'] = 'Neuer Nutzer erstellt';
$string['user_create_subject'] = 'Ein neues Online-Konto wurde für Sie erstellt';
$string['user_create_body'] = '<p>Sehr geehrte(r) Herr/Frau {User_LastName},</p>

<p>Ein neuer Nutzer Konto wurde für Sie im \'Training Management System\' erstellt.
und Ihnen wurde ein temporäres Kennwort zugewiesen.</p>

<p>Ihre aktuellen Anmeldeinformationen sind jetzt:<p>
<p>Nutzername: {User_Username}</br>
Kennwort: {User_Newpassword}</br>
(Wenn Sie sich das erste Mal anmelden, ändern Sie Ihre Anmeldeinformationen)</p>

<p>Um auf Ihr Lernprogramm zuzugreifen, melden Sie sich auf </p>
<p><a href="{SiteURL}">{SiteURL}</a> an.</p>


<p>In den meisten E-Mailprogrammen sollte dies als blauer Link zu sehen sein, den Sie einfach klicken können. Wenn das nicht 
funktioniert, kopieren Sie ihn und fügen Sie ihn in die Suchleiste Ihres Internet-Browsers ein.</p>

<p>Bei technischen Fragen, melden Sie sich einfach bei unserem IT Support team/Helpdesk</p>

<p>Mit freundlichen Grüßen^,</p>

<p>{Sender_FirstName} {Sender_LastName}</p>';

$string['completion_course_supervisor_description'] = 'An die E-Mail(falls angegeben) des Vorgesetzten eines Nutzers senden, wenn dieser einen Kurs abschließt.';
$string['completion_course_supervisor_name'] = 'Vorgesetzten-"Kurs abgeschlossen"-Nachicht';
$string['completion_course_supervisor_subject'] = 'Nachicht: Kurs {Course_FullName} wurde abgeschlossen';
$string['completion_course_supervisor_body'] = '<p>{User_FirstName} {User_LastName} hat den Trainings-Kurs {Course_FullName} abgeschlossen. Bei liegt ein Zertifikat für Ihre Teilnahme.</p>

<p>Das Zertifikat ist auch später noch im Kurs verfügbar.</p>';

$string['completion_warn_supervisor_description'] = 'An E-Mail(falls angegeben) des Vorgesetzten eines Nutzer senden, wenn der Nutzer einen Kurs nicht in der konfigurierten Zeit abgeschlossen hat.';
$string['completion_warn_supervisor_name'] = 'User\'s supervisor course completion warning.';
$string['completion_warn_supervisor_subject'] = 'Nachicht: Kurs {Course_FullName} wurde nicht abgeschlossen';
$string['completion_warn_supervisor_body'] = '<p>{User_FirstName} {User_LastName} hat den Kurs {Course_FullName} nicht innerhalb des konfigurierten Zeitraum abgeschlossen</p>';

$string['completion_expiry_warn_description'] = 'An die E-Mail(falls angegeben) des Vorgesetzten eines Nutzers senden,wenn dessen Trainingslizenz ausgelaufen ist.';
$string['completion_expiry_warn_name'] = 'E-Mail an Vorgesetzten, wenn Trainingslizenz abgelaufen ist';
$string['completion_expiry_warn_supervisor_subject'] = 'Nachicht: Kurs {Course_FullName} Trainingsauslauf';
$string['completion_expiry_warn_supervisor_body'] = '<p>Das Training von {User_FirstName} {User_LastName} im {Course_FullName} läuft bald aus. Soll dieser Kurs weiter besucht werden, so beantragen Sie ihn bitte neu.</p>';