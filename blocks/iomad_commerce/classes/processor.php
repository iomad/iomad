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
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce;
use iomad;
use company;
use company_user;
use core_user;
use context_system;
use EmailTemplate;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/local/email/lib.php');
require_once($CFG->dirroot . '/local/iomad_learningpath/classes/companypaths.php');

class processor {
    public static function trigger_oncheckout($invoiceid) {

        self::process_all_items($invoiceid, 'oncheckout');
        $_SESSION['Payment_Amount'] = \block_iomad_commerce\helper::get_basket_total();

        \block_iomad_commerce\helper::create_invoice_reference($invoiceid);
    }

    public static function trigger_onordercomplete($invoice) {
        global $DB;

        self::process_all_items($invoice->id, 'onordercomplete', $invoice );
        //self::trigger_invoiceitem_onordercomplete($invoice->id, 'onordercomplete', $invoice );
        $invoice->status = \block_iomad_commerce\helper::INVOICESTATUS_PAID;
        $DB->update_record('invoice', $invoice);
        self::email_invoices($invoice);
    }

    private static function process_all_items($invoiceid, $eventname, $invoice = null) {
        global $DB, $CFG;

        if ($items = $DB->get_records('invoiceitem', array('invoiceid' => $invoiceid, 'processed' => 0), null, '*')) {
            foreach ($items as $item) {
                $processorname = $item->invoiceableitemtype;
                $function = $processorname . "_" . $eventname;
                self::$function($item, $invoice);
            }
        }
    }

    public static function trigger_invoiceitem_onordercomplete($invoiceitemid, $invoice) {
        global $DB;
        if ($item = $DB->get_record('invoiceitem', array('id' => $invoiceitemid, 'processed' => 0), '*')) {
            $processorname = $item->invoiceableitemtype;
            $function = $processorname . "_onordercomplete";
            self::$function($item, $invoice);
        }
    }

    private static function singlepurchase_oncheckout($invoiceitem) {
        global $DB;

        if($ii = $DB->get_record_sql
          ('SELECT ii.*, css.single_purchase_currency, css.single_purchase_price, css.single_purchase_validlength
                                       FROM
                                            {invoiceitem} ii
                                            INNER JOIN {course_shopsettings} css ON css.id = ii.invoiceableitemid
                                       WHERE
                                            ii.id = :invoiceitemid', array('invoiceitemid' => $invoiceitem->id)))
        {
            $ii->currency = $ii->single_purchase_currency;
            $ii->price = $ii->single_purchase_price;
            $ii->license_validlength = $ii->single_purchase_validlength;
            $DB->update_record('invoiceitem', $ii);
        }
    }

    private static function singlepurchase_onordercomplete($invoiceitem, $invoice) {
        global $DB, $CFG;

        $runtime = time();
        $transaction = $DB->start_delegated_transaction();

        try {
            // Get the item's single purchase details.
            $iteminfo = $DB->get_record('course_shopsettings', array('id' => $invoiceitem->invoiceableitemid));

            // Get the courses.
            $courses = $DB->get_records('course_shopsettings_courses', ['itemid' => $iteminfo->id]);
            $licensecoursecount = $DB->count_records_sql("SELECT COUNT(csc.id) FROM {course_shopsettings_courses} csc
                                                          JOIN {iomad_courses} ic ON (csc.courseid = ic.courseid)
                                                          WHERE
                                                          ic.licensed = 1
                                                          AND csc.itemid = :itemid",
                                                          ['itemid' => $iteminfo->id]);
            // Get learning paths
            $paths = $DB->get_records('course_shopsettings_paths', ['itemid' => $iteminfo->id]);
            if (!empty($paths) || $licensecoursecount > 0) {
                $assignpaths = [];
                // Get the company id
                $companyid = iomad::get_my_companyid(context_system::instance());
                // Get name for company license.
                $company = $DB->get_record('company', ['id' => $companyid]);
                $licensename = $company->shortname . " [" . $iteminfo->name . "] " . userdate(time(), $CFG->iomad_date_format);
                $count = $DB->count_records_sql("SELECT COUNT(*) FROM {companylicense} WHERE " . $DB->sql_like('name', ":licensename"),
                                                ['licensename' => str_replace("'", "\'", $licensename)]);

                if ($count) {
                    $licensename .= ' (' . ($count + 1) . ')';
                }
                // Create mdl_companylicense record.
                $companylicense = (object) [];
                $companylicense->name = $licensename;
                $companylicense->type = $iteminfo->type;
                $companylicense->used = 0;
                $companylicense->clearonexpire = $iteminfo->clearonexpire;
                $companylicense->instant = $iteminfo->instant;
                $companylicense->companyid = $companyid;
                $companylicense->expirydate = (!empty($iteminfo->single_purchase_shelflife)) ? $iteminfo->single_purchase_shelflife + $runtime : 0;
                $companylicense->cutoffdate = (!empty($iteminfo->cutofftime)) ? $iteminfo->cutofftime + $runtime : 0;
            }
            if (!empty($paths)) {
                // Paths are included in the shop item
                $totalcourses = 0;
                $pathcoursesarray = [];
                $pathcourseenrol[] = $pathcourse->course;
                foreach ($paths as $path) {
                    $pathcourses = $DB->get_records('iomad_learningpathcourse',['path' => $path->pathid]);
                    foreach ($pathcourses as $pathcourse) {
                        if ($DB->get_record('iomad_courses', ['courseid' => $pathcourse->course, 'licensed' => 1])) {
                            $pathcoursesarray[] = $pathcourse->course;
                            $totalcourses++;
                        } else {
                            $pathcourseenrol[] = $pathcourse->course;
                        }
                    }
                }
                if (!empty($pathcoursesarray)) {
                    $companylicense->allocation = $totalcourses;
                    $companylicense->humanallocation = 1;
                    $companylicense->program = 1;
                    $companylicense->validlength = (!empty($iteminfo->single_purchase_validlength)) ? $iteminfo->single_purchase_validlength / 86400 : 1825;
                    $companylicenseid = $DB->insert_record('companylicense', $companylicense);
                    // Add the courses to the license
                    foreach ($pathcoursesarray as $pathcourse) {
                        $DB->insert_record('companylicense_courses', ['licenseid' => $companylicenseid, 'courseid' => $pathcourse]);
                        $licenseuserid = $DB->insert_record('companylicense_users', (object)['licenseid' => $companylicenseid, 
                                                                                            'userid' => $invoice->userid,
                                                                                            'isusing' => 0,
                                                                                            'licensecourseid' => $pathcourse,
                                                                                            'issuedate' => $runtime,
                                                                                            'groupid' => 0]);
                        // Create an event to assign the license.
                        $eventother = array('licenseid' => $companylicenseid,
                                            'issuedate' => $runtime,
                                            'duedate' => $runtime);
                        $event = \block_iomad_company_admin\event\user_license_assigned::create(array('context' => \context_course::instance($pathcourse),
                                                                                                    'objectid' => $licenseuserid,
                                                                                                    'courseid' => $pathcourse,
                                                                                                    'userid' => $invoice->userid,
                                                                                                    'other' => $eventother));
                        $event->trigger();
                    }
                }
                if (!empty($pathcourseenrol)) {
                    foreach ($pathcourseenrol as $pathcourse) {
                        if (!$DB->get_record('iomad_courses', ['courseid' => $pathcourse, 'licensed' => 1])) {
                            // Enrol user into course.
                            company_user::enrol($invoice->userid, [$pathcourse]);
                        }
                    }
                }
                $companypaths = new \local_iomad_learningpath\companypaths($companyid, context_system::instance());
                // Add user to path(s)
                foreach ($paths as $path) {
                    $companypaths->add_users($path->pathid, [$invoice->userid]);
                }
            } else if ($licensecoursecount > 0) {
                // Course is licensed
                $companylicense->allocation = $licensecoursecount;
                $companylicense->humanallocation = (empty($iteminfo->program)) ? $licensecoursecount : 1;
                $companylicense->program = $iteminfo->program;
                $validlength = (int) $iteminfo->single_purchase_validlength / 86400;
                // Always get 1 day.
                $companylicense->validlength = ($validlength == 0 ) ? 1 : $validlength;
                $companylicenseid = $DB->insert_record('companylicense', $companylicense);

                foreach ($courses as $course) {
                    if ($DB->get_record('iomad_courses', ['courseid' => $course->courseid, 'licensed' => 1])) {
                        $DB->insert_record('companylicense_courses', ['licenseid' => $companylicenseid, 'courseid' => $course->courseid]);
                        $licenseuserid = $DB->insert_record('companylicense_users', (object)['licenseid' => $companylicenseid, 
                                                                                            'userid' => $invoice->userid,
                                                                                            'isusing' => 0,
                                                                                            'licensecourseid' => $course->id,
                                                                                            'issuedate' => $runtime,
                                                                                            'groupid' => 0]);
                        // Create an event to assign the license.
                        $eventother = array('licenseid' => $companylicenseid,
                                            'issuedate' => $runtime,
                                            'duedate' => $runtime);
                        $event = \block_iomad_company_admin\event\user_license_assigned::create(array('context' => \context_course::instance($course->courseid),
                                                                                                    'objectid' => $licenseuserid,
                                                                                                    'courseid' => $course->courseid,
                                                                                                    'userid' => $invoice->userid,
                                                                                                    'other' => $eventother));
                        $event->trigger();
                    }
                }
            } else {
                foreach ($courses as $course) {
                    if (!$DB->get_record('iomad_courses', ['courseid' => $course->courseid, 'licensed' => 1])) {

                        // Enrol user into course.
                        company_user::enrol($invoice->userid, array($course->courseid));
                    }
                }
            }
            // Mark the invoice item as processed.
            $invoiceitem->processed = 1;
            $DB->update_record('invoiceitem', $invoiceitem);
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public static function licenseblock_oncheckout($invoiceitem) {
        global $DB;

        if ($ii = $DB->get_record('invoiceitem', array('id' => $invoiceitem->id), '*')) {
            if ($block = \block_iomad_commerce\helper::get_license_block($ii->invoiceableitemid, $ii->license_allocation)) {
                $ii->currency = $block->currency;
                $ii->price = $block->price;
                $ii->license_validlength = $block->validlength;
                $ii->license_shelflife = $block->shelflife;

                $DB->update_record('invoiceitem', $ii);
            }
        }
    }

    public static function licenseblock_onordercomplete($invoiceitem, $invoice) {
        global $DB, $CFG;

        $runtime = time();
        $transaction = $DB->start_delegated_transaction();
        try {
            // Get name for company license.
            $companyid = iomad::get_my_companyid(context_system::instance());
            $company = $DB->get_record('company', ['id' => $companyid]);
            $item = $DB->get_record('course_shopsettings', ['id' => $invoiceitem->invoiceableitemid]);
            $courses = $DB->get_records('course_shopsettings_courses', ['itemid' => $item->id]);
            // Get learning paths
            $paths = $DB->get_records('course_shopsettings_paths', ['itemid' => $item->id]);
            // Create the name for the license
            $licensename = $company->shortname . " [" . $item->name . "] " . userdate(time(), $CFG->iomad_date_format);
            $count = $DB->count_records_sql("SELECT COUNT(*) FROM {companylicense} WHERE " . $DB->sql_like('name', ":licensename"),
                                            ['licensename' => str_replace("'", "\'", $licensename)]);
            if ($count) {
                $licensename .= ' (' . ($count + 1) . ')';
            }
            // Create mdl_companylicense record.
            $companylicense = (object) [];
            $companylicense->name = $licensename;
            $companylicense->humanallocation = $invoiceitem->license_allocation;
            $companylicense->clearonexpire = $item->clearonexpire;
            $companylicense->instant = $item->instant;
            $companylicense->startdate = $runtime;
            $companylicense->companyid = $company->id;
            // Deal with license shelf life.
            $companylicense->expirydate = (!empty($item->single_purchase_shelflife)) ? $item->single_purchase_shelflife + $runtime : 0;
            // Deal with cut off time.
            $companylicense->cutoffdate = (!empty($item->cutofftime)) ? $item->cutofftime + $runtime : $companylicense->expirydate;
            if (!empty($paths)) {
                // Paths are included in the shop item
                $totalcourses = 0;
                $pathcoursesarray = [];
                foreach ($paths as $path) {
                    $pathcourses = $DB->get_records('iomad_learningpathcourse',['path' => $path->pathid]);
                    foreach ($pathcourses as $pathcourse) {
                        $pathcoursesarray[] = $pathcourse->course;
                        $totalcourses++;
                    }
                }
                $companylicense->allocation = $totalcourses;
                $companylicense->program = 1;
                $companylicense->validlength = (!empty($item->single_purchase_validlength)) ? $item->single_purchase_validlength / 86400 : 1825;
                $companylicenseid = $DB->insert_record('companylicense', $companylicense);
                // Add the courses to the license
                foreach ($pathcoursesarray as $pathcourse) {
                    $DB->insert_record('companylicense_courses', ['licenseid' => $companylicenseid, 'courseid' => $pathcourse]);
                    $licenseuserid = $DB->insert_record('companylicense_users', (object)['licenseid' => $companylicenseid, 
                                                                                        'userid' => $invoice->userid,
                                                                                        'isusing' => 0,
                                                                                        'licensecourseid' => $pathcourse,
                                                                                        'issuedate' => $runtime,
                                                                                        'groupid' => 0]);
                }
            } else if (!empty($courses)) {
                $companylicense->program = $item->program;
                $companylicense->allocation = (empty($companylicense->program)) ? $invoiceitem->license_allocation : $invoiceitem->license_allocation * count($courses);
                // Deal with license valid length.
                $validlength = (int) $item->single_purchase_validlength / 86400;
                // Always get 1 day.
                $companylicense->validlength = ($validlength == 0 ) ? 1 : $validlength;
                $companylicenseid = $DB->insert_record('companylicense', $companylicense);
                foreach ($courses as $course) {
                    $DB->insert_record('companylicense_courses', ['licenseid' => $companylicenseid, 'courseid' => $course->courseid]);
                }
            }
            // Mark the invoice item as processed.
            $invoiceitem->processed = 1;
            $DB->update_record('invoiceitem', $invoiceitem);
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public static function email_invoices($invoice) {
        global $CFG, $DB;

        if (empty($invoice)) {
            return;
        }

        $basket = \block_iomad_commerce\helper::get_basket_by_id($invoice->id, \block_iomad_commerce\helper::INVOICESTATUS_PAID);
        $invoice->itemized = \block_iomad_commerce\helper::get_invoice_html($basket->id, 0, 0);

        // Notify shop admin.
        if (isset($CFG->commerce_admin_email)) {
            if (!$shopadmin = $DB->get_record('user', array('email' => $CFG->commerce_admin_email))) {
                $shopadmin = (object) [];
                $shopadmin->email = $CFG->commerce_admin_email;
                if (empty($CFG->commerce_admin_firstname)) {
                    $shopadmin->firstname = "Shop";
                } else {
                    $shopadmin->firstname = $CFG->commerce_admin_firstname;
                }
                if (empty($CFG->commerce_admin_lastname)) {
                    $shopadmin->lastname = "Admin";
                } else {
                    $shopadmin->lastname = $CFG->commerce_admin_lastname;
                }
                $shopadmin->id = -999;
            }
        } else {
            $shopadmin = (object) [];
            $shopadmin->email = $CFG->support_email;
            if (empty($CFG->commerce_admin_firstname)) {
                $shopadmin->firstname = "Shop";
            } else {
                $shopadmin->firstname = $CFG->commerce_admin_firstname;
            }
            if (empty($CFG->commerce_admin_lastname)) {
                $shopadmin->lastname = "Admin";
            } else {
                $shopadmin->lastname = $CFG->commerce_admin_lastname;
            }
            $shopadmin->id = -999;
        }

        if ($user = $DB->get_record('user',  array('id' => $invoice->userid))) {
            EmailTemplate::send('invoice_ordercomplete', ['user' => $user, 'invoice' => $invoice, 'sender' => $shopadmin]);
            EmailTemplate::send('invoice_ordercomplete_admin', ['user' => $shopadmin, 'invoice' => $invoice]);
        }
    }
}
