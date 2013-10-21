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
 * @package    Block Approve Enroll
 * @copyright  2011 onwards E-Learn Design Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function approve_enrol_has_users() {
    global $CFG, $DB, $USER, $SESSION;
    
    require_once($CFG->dirroot.'/local/perficio/lib/company.php');
    require_once($CFG->dirroot.'/local/perficio/lib/user.php');

    // Set the companyid to bypass the company select form if possible.
    if (!empty($SESSION->currenteditingcompany)) {
        $companyid = $SESSION->currenteditingcompany;
    } else if (!empty($USER->company)) {
        $companyid = company_user::companyid();
    } else {
        return false;
    }

    // check if we can have users of my type.
    if (is_siteadmin($USER->id)) {
        $approvaltype = 'both';
    } else {
        // what type of manager am I?
        if ($manager = $DB->get_record('companymanager', array('userid'=>$USER->id))) {
            if (!empty($manager->departmentmanager)) {
                $approvaltype = 'manager';
            } else {
                $approvaltype = 'company';
            }
        } else {
            return false;
        }
    }
    if ($approvaltype == 'both' || $approvaltype == 'manager') {
        // Get the list of users I am responsible for.
        $myuserids = company::get_my_users_list($companyid);
        if (!empty($myuserids) && $DB->get_records_sql("SELECT * FROM {block_iomad_approve_access}
                                  WHERE companyid = :companyid
                                  AND manager_ok = 0
                                  AND userid != :myuserid
                                  AND userid IN ($myuserids)", 
                                  array('companyid'=>$companyid, 'myuserid'=>$USER->id))) {
            return true;
        }
    }    
    if ($approvaltype == 'both' || $approvaltype == 'company') {
        if ($DB->get_records('block_iomad_approve_access', array('companyid'=>$companyid, 'tm_ok'=>'0 AND userid != '.$USER->id))) {
            return true;
        }
    }    

    // hasn't returned yet, return false as default.
    return false;
}

function approve_enroll_get_my_users() {
    global $CFG, $DB, $USER, $SESSION;

    require_once($CFG->dirroot.'/local/perficio/lib/company.php');
    require_once($CFG->dirroot.'/local/perficio/lib/user.php');

    // Set the companyid to bypass the company select form if possible.
    if (!empty($SESSION->currenteditingcompany)) {
        $companyid = $SESSION->currenteditingcompany;
    } else if (!empty($USER->company)) {
        $companyid = company_user::companyid();
    } else {
        return false;
    }

    // check if we can have users of my type.
    if (is_siteadmin($USER->id)) {
        $approvaltype = 'both';
    } else {
        // what type of manager am I?
        if ($manager = $DB->get_record('companymanager', array('userid'=>$USER->id))) {
            if (!empty($manager->departmentmanager)) {
                $approvaltype = 'manager';
            } else {
                $approvaltype = 'company';
            }
        } else {
            return false;
        }
    }

    // Get the list of users I am responsible for.
    $myuserids = company::get_my_users_list($companyid);
    if (!empty($myuserids)) {
        if ($approvaltype == 'manager') {
            //  need to deal with departments here.
            if ($userarray = $DB->get_records('block_iomad_approve_access', array('companyid'=>$companyid,
                                              'manager_ok'=>'0 AND userid != '.$USER->id.' AND userid IN ('.$myuserids.')'))) {
                return $userarray;
            }
        }    
    
        if ($approvaltype == 'company') {
            if ($userarray = $DB->get_records('block_iomad_approve_access', array('companyid'=>$companyid,
                                              'tm_ok'=>'0 AND userid != '.$USER->id.' AND userid IN ('.$myuserids.')'))) {
                return $userarray;
            }
        }
    
        if ($approvaltype == 'both') {
            if ($userarray = $DB->get_records_sql("SELECT * FROM {block_iomad_approve_access}
                                                   WHERE companyid=:companyid
                                                   AND (tm_ok = 0 OR manager_ok = 0)
                                                   AND userid != :myuserid
                                                   AND userid IN ($myuserids)",
                                                   array('companyid'=>$companyid, 'myuserid'=>$USER->id))) {
                return $userarray;
            }
        }
    }

    return array();    
}
