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
 * IOMAD local tracking plugin
 *
 * @package   local_iomad_track
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->libdir.'/filelib.php');

/**
 * Send the stored file to the user
 *
 */
function local_iomad_track_pluginfile($course, $birecord_or_cm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_USER) {
        send_file_not_found();
    }

    require_login();

    if ($filearea !== 'issue') {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    if (!$file = $fs->get_file($context->id, 'local_iomad_track', 'issue', $itemid, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    // NOTE: it woudl be nice to have file revisions here, for now rely on standard file lifetime,
    //       do not lower it because the files are dispalyed very often.
    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/*
 * Function to remove entries from the local_iomad_track table.
 *
 * @param boolean $full remove just the saved certificate or everything.
 */
function local_iomad_track_delete_entry($trackid, $full=false) {
    global $DB,$CFG;

    // Do we have a recorded certificate?
    if ($certs = $DB->get_records('local_iomad_track_certs', ['trackid' => $trackid])) {
        foreach ($certs as $cert) {
            $DB->delete_records('local_iomad_track_certs', ['id' => $cert->id]);
        }
    }

    // Remove the actual underlying file.
    if ($file = $DB->get_record_sql("SELECT * FROM {files}
                                     WHERE component= :component
                                     AND itemid = :itemid
                                     AND filename <> '.'",
                                     ['component' => 'local_iomad_track', 'itemid' => $trackid])) {
        $filedir1 = substr($file->contenthash,0,2);
        $filedir2 = substr($file->contenthash,2,2);
        $filepath = $CFG->dataroot . '/filedir/' . $filedir1 . '/' . $filedir2 . '/' . $file->contenthash;
        unlink($filepath);
    }
    $DB->delete_records('files', ['itemid' => $trackid, 'component' => 'local_iomad_track']);

    // Are we getting rid of the full record?
    if ($full) {
        $DB->delete_records('local_iomad_track', ['id' => $trackid]);
    }
}

/*
 * Function to download a number of certificates in a zip file
 * and pass it to the browser.
 */
function local_iomad_track_download_certs($companyid = 0, $courses = [], $users = []) {
    global $DB, $CFG, $USER;

    // Set the companyid
    if (empty($companyid)) {
        $companyid = iomad::get_my_companyid(context_system::instance());
    }
    $companycontext = \core\context\company::instance($companyid);

    $company = new company($companyid);

    // Deal with the courses.
    if (empty($courses)) {
        $allcourses = array_keys($company->get_menu_courses(true, false, false, false));
    } else {
        $allcourses = $courses;
    }

    // Deal with the users.
    $sqlparams = [];
    $sqlselect = "courseid = :courseid AND companyid = :companyid AND timecompleted > 0";
    if (!empty($users)) {
        [$insql, $sqlparams] = $DB->get_in_or_equal($users,
                                                    SQL_PARAMS_NAMED,
                                                    'uids');
        $sqlselect .= " AND userid {$insql}";

    }

    // Create the zip file.
    $zipfile = new ZipArchive();
    $tempfilename = $CFG->dataroot . '/temp/filestorage/' . time();
    $realfilename = "certificates.zip";
    if ($zipfile->open($tempfilename, ZipArchive::CREATE) === TRUE) {
        // Process all of the courses.
        foreach ($allcourses as $course) {
            $sqlparams['courseid'] = $course;
            $sqlparams['companyid'] = $company->id;
            $comprecords = $DB->get_records_select('local_iomad_track',
                                                   $sqlselect,
                                                   $sqlparams);
            if (count($comprecords) > 0) {
                // For all of the track saved files.
                foreach ($comprecords as $comprecord) {
                    if ($filerec = $DB->get_record_select('files',
                                                          "component =:component
                                                           AND filearea = :filearea
                                                           AND itemid = :itemid
                                                           AND filesize > 0
                                                           AND filename != '-'",
                                                          ['component' => 'local_iomad_track',
                                                           'filearea' => 'issue',
                                                           'itemid' => $comprecord->id])) {
                        if ($userrec = $DB->get_record('user', ['id' => $comprecord->userid])) {
                            $savefilename = format_string($comprecord->coursename) . "/" .
                                            $userrec->firstname . "_" .
                                            $userrec->lastname . "_" . $userrec->id . "/" .
                                            $comprecord->id . "_" . $filerec->filename;
                            $first = substr($filerec->contenthash, 0, 2);
                            $second = substr($filerec->contenthash, 2, 2);
                            $filepath = $CFG->dataroot . "/filedir/$first/$second/" . $filerec->contenthash;
                            $zipfile->addFile($filepath, $savefilename);
                        }
                    }
                }
            }
        }
        $zipfile->close();

        // Send the headers to force download the zip file.
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$realfilename");
        header("Content-length: " . filesize($tempfilename));
        header("Pragma: no-cache");
        header("Expires: 0");
        ob_clean();
        flush();
        $handle = fopen($tempfilename, "rb");
        while (!feof($handle)){
            echo fread($handle, 8192);
        }
        fclose($handle);
        unlink($tempfilename);
        exit;
    }
}
