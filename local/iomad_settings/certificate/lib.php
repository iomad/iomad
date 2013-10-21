<?php

const RESET_SEQUENCE_NEVER = 'never';
const RESET_SEQUENCE_DAILY = 'daily';
const RESET_SEQUENCE_ANNUALLY = 'annually';


function padleft($value, $n) {
    return str_pad($value, $n, "0", STR_PAD_LEFT);
}

function iomad_settings_serialnumber($serialnumberrecord) {
    $matches = array();
    $prefix = $serialnumberrecord->prefix;
    if (preg_match_all('/\{SEQNO:(\d+)\}/i', $prefix, &$matches)) {
        foreach($matches[1] as $match) {
            $seqno = padleft($serialnumberrecord->sequenceno, $match);
            $prefix = preg_replace('/\{SEQNO[^\}]*\}/i', $seqno, $prefix);
        }
    }
    
    return $prefix;
}

function iomad_settings_establishment_code() {
    global $CFG;
    return padleft(isset($CFG->establishment_code) ? $CFG->establishment_code : 0, 4);
}

function iomad_settings_attempt_serialnumber_insert($serialobj) {
    global $DB;

    $bool = 0;
    try {
        $bool = $DB->insert_record('certificate_serialnumber', $serialobj);
    } catch (Exception $e) {
    }
    return $bool;
}

// Create serial number or retrieve if one already exists for the issues certificate
function iomad_settings_create_serial_number($certificate, $certrecord, $course, $certdate) {
    global $DB;

    if (!$serialobj = $DB->get_record('certificate_serialnumber', array('issued_certificate' => $certrecord->id), '*')) {
        $prefix = iomad_settings_replacement($certificate->serialnumberformat,$course, '', $certdate);

        $serialobj = new stdClass;
        $serialobj->certificateid = $certrecord->certificateid;
        $serialobj->issued_certificate = $certrecord->id;
        $serialobj->prefix = $prefix;
        $serialobj->timecreated = time();
        if ($certificate->reset_sequence == RESET_SEQUENCE_DAILY) {
            $serialobj->sequence = date('Ymd');
        } else {
            $serialobj->sequence = date('Y');
        }
        $serialobj->sequenceno = iomad_settings_next_serial_number($serialobj->certificateid, $serialobj->sequence);

        // there is a unique index on prefix and sequenceno that will prevent inserts if the prefix/sequenceno combo already exists
        while (!iomad_settings_attempt_serialnumber_insert($serialobj)) {
            // check that serial number already exists in database and the insert didn't fail for some other reason
            if ($DB->get_record('certificate_serialnumber', array('prefix' => $prefix, 'sequenceno' => $serialobj->sequenceno), 'id')) {
                // somebody moved the goal posts, try again
                $serialobj->sequenceno = iomad_settings_next_serial_number($serialobj->certificateid, $serialobj->sequence);
                $serialobj->timecreated = time();
            } else {
                // this shouldn't happen
                print_error("Certificate Serial Number couldn't be created");
            }
        }
    }

    return iomad_settings_serialnumber($serialobj);
}

function iomad_settings_next_serial_number($certificateid, $sequence) {
    global $DB;
    
    // find the last serialnumber created in the same sequence
    $lastserial = $DB->get_records_select('certificate_serialnumber', "certificateid = $certificateid AND sequence >= $sequence ORDER BY timecreated desc LIMIT 0,1");

    // get the record out of the array (or set to null if no record returned)
    if (count($lastserial) > 0) {
        $keys = (array_keys($lastserial));
        $lastserial = $lastserial[$keys[0]];
    } else {
        $lastserial = null;
    }

    return isset($lastserial) ? $lastserial->sequenceno + 1 : 1;
}

function format_date_with_iomad_settings_format($format, $date) {
    $tformat = preg_replace('/DD/i', '%d', $format);
    $tformat = preg_replace('/MM/i', '%m', $tformat);
    $tformat = preg_replace('/YYYY/i', '%Y', $tformat);
    $tformat = preg_replace('/YY/i', '%y', $tformat);
    
    return strftime($tformat, $date ? $date : time());
}

function iomad_settings_replacement($customtext, $course, $serialnumber, $certdate) {
    // {SN} = serial number
    $customtext = str_replace("{SN}", $serialnumber, $customtext);
    
    // {EC} = establishment code
    $customtext = str_replace("{EC}", iomad_settings_establishment_code(), $customtext);
    
    // {CC} = course code
    $coursecode = padleft(isset($course->idnumber) && $course->idnumber != "" ? $course->idnumber : $course->id, 4);
    $customtext = str_replace("{CC}", $coursecode, $customtext);

    // {CD:???} = completion date
    $matches = array();
    // match {CD:YMD} (YMD = date format, can be anything except end brace '}', but format_date_with_iomad_settings_format determines what really is used.
    if (preg_match_all('/\{CD:([^\}]+)\}/i', $customtext, &$matches)) {
        foreach($matches[1] as $match) {
            $formatteddate = format_date_with_iomad_settings_format($match, $certdate);
            $customtext = preg_replace('/\{CD:' . $match . '\}/i', $formatteddate, $customtext);
        }
    }

    return $customtext;
}