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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce;

use block_iomad_company_admin\event\user_license_assigned;
use block_iomad_commerce\helper;
use block_iomad_learningpaths\companypaths;
use context_system;
use context_course;
use local_iomad\{company_user, emailtemplate, iomad};

/**
 * Block IOMAD eCommerce processor class
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {

    /**
     * Checkout trigger function
     *
     * @param id $invoiceid
     * @return void
     */
    public static function trigger_oncheckout($invoiceid) {

        self::process_all_items($invoiceid, 'oncheckout');
        $_SESSION['Payment_Amount'] = helper::get_basket_total();

        helper::create_invoice_reference($invoiceid);
    }

    /**
     * On order completion trigger function
     *
     * @param id $invoice
     * @return void
     */
    public static function trigger_onordercomplete($invoice) {
        global $DB;

        self::process_all_items($invoice->id, 'onordercomplete', $invoice );
        $invoice->status = helper::INVOICESTATUS_PAID;
        $DB->update_record('invoice', $invoice);
        self::email_invoices($invoice);
    }

    /**
     * Internal function to process all invoice items
     *
     * @param int $invoiceid
     * @param string $eventname
     * @param object $invoice
     * @return void
     */
    private static function process_all_items($invoiceid, $eventname, $invoice = null) {
        global $DB;

        // Get any invoice items.
        if ($items = $DB->get_records('invoiceitem', ['invoiceid' => $invoiceid, 'processed' => 0], null, '*')) {
            // Process them.
            foreach ($items as $item) {
                $processorname = $item->invoiceableitemtype;
                $function = $processorname . "_" . $eventname;
                self::$function($item, $invoice);
            }
        }
    }

    /**
     * Invoice item order complete trigger function.
     *
     * @param int $invoiceitemid
     * @param object $invoice
     * @return void
     */
    public static function trigger_invoiceitem_onordercomplete($invoiceitemid, $invoice) {
        global $DB;

        // Check the item exists and hasn't been processed already.
        if ($item = $DB->get_record('invoiceitem', ['id' => $invoiceitemid, 'processed' => 0], '*')) {
            // Process it.
            $processorname = $item->invoiceableitemtype;
            $function = $processorname . "_onordercomplete";
            self::$function($item, $invoice);
        }
    }

    /**
     * Process block purchase of licenses.
     *
     * @param int $invoiceitem
     * @return void
     */
    public static function licenseblock_oncheckout($invoiceitem) {
        global $DB;

        // Does the item exist?
        if ($ii = $DB->get_record('invoiceitem', ['id' => $invoiceitem->id], '*')) {
            // Is it an unprocessed license?
            if ($block = helper::get_license_block($ii->invoiceableitemid, $ii->license_allocation)) {
                $ii->currency = $block->currency;
                $ii->price = $block->price;
                $ii->license_validlength = $block->validlength;
                $ii->license_shelflife = $block->shelflife;

                // Process it.
                $DB->update_record('invoiceitem', $ii);
            }
        }
    }

    /**
     * On order complete license block trigger
     *
     * @param int $invoiceitem
     * @param object $invoice
     * @return void
     */
    public static function licenseblock_onordercomplete($invoiceitem, $invoice) {
        global $DB;

        $runtime = time();
        $transaction = $DB->start_delegated_transaction();
        try {
            // Get name for company license.
            $companyid = iomad::get_my_companyid(context_system::instance());
            $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
            $item = $DB->get_record('course_shopsettings', ['id' => $invoiceitem->invoiceableitemid]);
            $courses = $DB->get_records('course_shopsettings_courses', ['itemid' => $item->id]);

            // Get any learning paths.
            $paths = $DB->get_records('course_shopsettings_paths', ['itemid' => $item->id]);

            // Create name for any licenses.
            $licensename = $company->shortname .
                           " [" . $item->name . "] " .
                           userdate(time(), get_config('local_iomad', 'date_format'));
            $count = $DB->count_records_sql("SELECT COUNT(*)
                                             FROM {local_iomad_company_licenses}
                                             WHERE " . $DB->sql_like('name', ":licensename"),
                                            ['licensename' => str_replace("'", "\'", $licensename)]);
            if ($count) {
                $licensename .= ' (' . ($count + 1) . ')';
            }

            // Create mdl_companylicense record..
            $companylicense = (object) [];
            $companylicense->name = $licensename;
            $companylicense->humanallocation = $invoiceitem->license_allocation;
            $companylicense->clearonexpire = $item->clearonexpire;
            $companylicense->instant = $item->instant;
            $companylicense->startdate = $runtime;
            $companylicense->companyid = $company->id;

            // Deal with license shelf life.
            $companylicense->expirydate = (!empty($item->single_purchase_shelflife)) ?
                                            $item->single_purchase_shelflife + $runtime :
                                            0;

            // Deal with cut off time.
            $companylicense->cutoffdate = (!empty($item->cutofftime)) ?
                                            $item->cutofftime + $runtime :
                                            $companylicense->expirydate;

            // Deal with learning paths.
            if (!empty($paths)) {
                // Paths are included in the shop item.
                $totalcourses = 0;
                $pathcoursesarray = [];

                // Process the paths.
                foreach ($paths as $path) {
                    // Get the courses.
                    $pathcourses = $DB->get_records('iomad_learningpathcourse', ['path' => $path->pathid]);
                    foreach ($pathcourses as $pathcourse) {
                        $pathcoursesarray[] = $pathcourse->course;
                        $totalcourses++;
                    }
                }

                // Continue setting up the license.
                $companylicense->allocation = $totalcourses;
                $companylicense->program = 1;
                $companylicense->validlength = (!empty($item->single_purchase_validlength)) ?
                                                $item->single_purchase_validlength / 86400 : 1825;
                $companylicenseid = $DB->insert_record('local_iomad_company_licenses', $companylicense);

                // Add the courses to the license.
                foreach ($pathcoursesarray as $pathcourse) {
                    $DB->insert_record('local_iomad_company_license_courses', ['licenseid' => $companylicenseid, 'courseid' => $pathcourse]);
                    $DB->insert_record('local_iomad_company_license_users', (object)['licenseid' => $companylicenseid,
                                                                        'userid' => $invoice->userid,
                                                                        'isusing' => 0,
                                                                        'licensecourseid' => $pathcourse,
                                                                        'issuedate' => $runtime,
                                                                        'groupid' => 0]);
                }
            } else if (!empty($courses)) {
                // Define the type of license.
                $companylicense->program = $item->program;
                $companylicense->allocation = (empty($companylicense->program)) ?
                                                $invoiceitem->license_allocation :
                                                $invoiceitem->license_allocation * count($courses);

                // Deal with license valid length.
                $validlength = (int) $item->single_purchase_validlength / 86400;

                // Always get 1 day.
                $companylicense->validlength = ($validlength == 0 ) ? 1 : $validlength;

                // Create the license record.
                $companylicenseid = $DB->insert_record('local_iomad_company_licenses', $companylicense);

                // Add the courses to it.
                foreach ($courses as $course) {
                    $DB->insert_record('local_iomad_company_license_courses', ['licenseid' => $companylicenseid,
                                                                  'courseid' => $course->courseid]);
                }
            }

            // Mark the invoice item as processed.
            $invoiceitem->processed = 1;
            $DB->update_record('invoiceitem', $invoiceitem);

            // No errors, so we commit.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Internal processing function for single purchase during checkout
     *
     * @param object $invoiceitem
     * @return void
     */
    private static function singlepurchase_oncheckout($invoiceitem) {
        global $DB;

        if ($ii = $DB->get_record_sql(
            "SELECT ii.*,
                    css.single_purchase_currency,
                    css.single_purchase_price,
                    css.single_purchase_validlength
             FROM {invoiceitem} ii
             INNER JOIN {course_shopsettings} css ON css.id = ii.invoiceableitemid
             WHERE ii.id = :invoiceitemid",
            ['invoiceitemid' => $invoiceitem->id])) {

            // Fill the item details from defaults.
            $ii->currency = $ii->single_purchase_currency;
            $ii->price = $ii->single_purchase_price;
            $ii->license_validlength = $ii->single_purchase_validlength;

            // Update the record.
            $DB->update_record('invoiceitem', $ii);
        }
    }

    /**
     * Internal processing function for single purchase after completion.
     *
     * @param object $invoiceitem
     * @param object $invoice
     * @return void
     */
    private static function singlepurchase_onordercomplete($invoiceitem, $invoice) {
        global $DB;

        $runtime = time();
        $transaction = $DB->start_delegated_transaction();

        try {
            // Get the item's single purchase details.
            $iteminfo = $DB->get_record('course_shopsettings', ['id' => $invoiceitem->invoiceableitemid]);

            // Get the courses.
            $courses = $DB->get_records('course_shopsettings_courses', ['itemid' => $iteminfo->id]);

            // Get the number of licenses.
            $licensecoursecount = $DB->count_records_sql(
                "SELECT COUNT(csc.id) FROM {course_shopsettings_courses} csc
                 JOIN {local_iomad_courses} ic ON (csc.courseid = ic.courseid)
                 WHERE ic.licensed = 1
                 AND csc.itemid = :itemid",
            ['itemid' => $iteminfo->id]);

            // Deal with any learning paths.
            $paths = $DB->get_records('course_shopsettings_paths', ['itemid' => $iteminfo->id]);
            if (!empty($paths) || $licensecoursecount > 0) {

                // Get the company id.
                $companyid = iomad::get_my_companyid(context_system::instance());
                // Get name for company license.
                $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
                $licensename = $company->shortname .
                               " [" . $iteminfo->name . "] " .
                               userdate(time(), get_config('local_iomad', 'date_format'));
                $count = $DB->count_records_sql(
                    "SELECT COUNT(*)
                     FROM {local_iomad_company_licenses}
                     WHERE " . $DB->sql_like('name', ":licensename"),
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
                $companylicense->expirydate = (!empty($iteminfo->single_purchase_shelflife)) ?
                                                $iteminfo->single_purchase_shelflife + $runtime :
                                                0;
                $companylicense->cutoffdate = (!empty($iteminfo->cutofftime)) ?
                                               $iteminfo->cutofftime + $runtime :
                                               0;
            }

            // Are there any paths in the shop item?
            if (!empty($paths)) {
                $totalcourses = 0;
                $pathcoursesarray = [];
                $pathcourseenrol[] = $pathcourse->course;

                // Deal with them.
                foreach ($paths as $path) {
                    // Get the courses.
                    $pathcourses = $DB->get_records('iomad_learningpathcourse', ['path' => $path->pathid]);

                    // Process them.
                    foreach ($pathcourses as $pathcourse) {
                        // Is this a licensed course or a manual enrol course?
                        if ($DB->get_record('local_iomad_courses', ['courseid' => $pathcourse->course,
                                                              'licensed' => 1])) {
                            $pathcoursesarray[] = $pathcourse->course;
                            $totalcourses++;
                        } else {
                            $pathcourseenrol[] = $pathcourse->course;
                        }
                    }
                }

                // Did we find any license courses?
                if (!empty($pathcoursesarray)) {
                    // Create a license for them.
                    $companylicense->allocation = $totalcourses;
                    $companylicense->humanallocation = 1;
                    $companylicense->program = 1;
                    $companylicense->validlength = (!empty($iteminfo->single_purchase_validlength)) ?
                                                    $iteminfo->single_purchase_validlength / 86400 :
                                                    1825;
                    // Create the license record.
                    $companylicenseid = $DB->insert_record('local_iomad_company_licenses', $companylicense);

                    // Add the courses to the license.
                    foreach ($pathcoursesarray as $pathcourse) {
                        $DB->insert_record('local_iomad_company_license_courses', ['licenseid' => $companylicenseid,
                                                                      'courseid' => $pathcourse]);

                        // Assign the license and course to the user.
                        $licenseuserid = $DB->insert_record('local_iomad_company_license_users', (object)['licenseid' => $companylicenseid,
                                                                                            'userid' => $invoice->userid,
                                                                                            'isusing' => 0,
                                                                                            'licensecourseid' => $pathcourse,
                                                                                            'issuedate' => $runtime,
                                                                                            'groupid' => 0]);

                        // Create an event to assign the license.
                        $eventother = ['licenseid' => $companylicenseid,
                                       'issuedate' => $runtime,
                                       'duedate' => $runtime];
                        $event = user_license_assigned::create(['context' => context_course::instance($pathcourse),
                                                                'objectid' => $licenseuserid,
                                                                'courseid' => $pathcourse,
                                                                'userid' => $invoice->userid,
                                                                'other' => $eventother]);
                        $event->trigger();
                    }
                }

                // Deal with any manually enrolled courses.
                if (!empty($pathcourseenrol)) {
                    foreach ($pathcourseenrol as $pathcourse) {
                        // Check it's not a license course.
                        if (!$DB->get_record('local_iomad_courses', ['courseid' => $pathcourse, 'licensed' => 1])) {
                            // Enrol user into course.
                            company_user::enrol($invoice->userid, [$pathcourse]);
                        }
                    }
                }
                // Create a new company learning path object for this.
                $companypaths = new companypaths($companyid, context_system::instance());

                // Add user to path(s).
                foreach ($paths as $path) {
                    $companypaths->add_users($path->pathid, [$invoice->userid]);
                }
            } else if ($licensecoursecount > 0) {
                // Course is licensed.
                $companylicense->allocation = $licensecoursecount;
                $companylicense->humanallocation = (empty($iteminfo->program)) ? $licensecoursecount : 1;
                $companylicense->program = $iteminfo->program;
                $validlength = (int) $iteminfo->single_purchase_validlength / 86400;

                // Always get 1 day.
                $companylicense->validlength = ($validlength == 0 ) ? 1 : $validlength;

                // Create the license record.
                $companylicenseid = $DB->insert_record('local_iomad_company_licenses', $companylicense);

                // Add the courses to it.
                foreach ($courses as $course) {
                    // Check this is a license enrolment course.
                    if ($DB->get_record('local_iomad_courses', ['courseid' => $course->courseid, 'licensed' => 1])) {
                        // Add it to the license.
                        $DB->insert_record('local_iomad_company_license_courses', ['licenseid' => $companylicenseid,
                                                                      'courseid' => $course->courseid]);
                        // Assign the course/license to the user.
                        $licenseuserid = $DB->insert_record('local_iomad_company_license_users', (object)['licenseid' => $companylicenseid,
                                                                                            'userid' => $invoice->userid,
                                                                                            'isusing' => 0,
                                                                                            'licensecourseid' => $course->id,
                                                                                            'issuedate' => $runtime,
                                                                                            'groupid' => 0]);

                        // Create an event to assign the license.
                        $eventother = ['licenseid' => $companylicenseid,
                                            'issuedate' => $runtime,
                                            'duedate' => $runtime];
                        $event = user_license_assigned::create(['context' => context_course::instance($course->courseid),
                                                                'objectid' => $licenseuserid,
                                                                'courseid' => $course->courseid,
                                                                'userid' => $invoice->userid,
                                                                'other' => $eventother]);
                        $event->trigger();
                    }
                }
            } else {
                // Process manual enrolment courses.
                foreach ($courses as $course) {
                    // Check it's not a license enrolment course.
                    if (!$DB->get_record('local_iomad_courses', ['courseid' => $course->courseid, 'licensed' => 1])) {

                        // Enrol user into course.
                        company_user::enrol($invoice->userid, [$course->courseid]);
                    }
                }
            }

            // Mark the invoice item as processed.
            $invoiceitem->processed = 1;
            $DB->update_record('invoiceitem', $invoiceitem);

            // No errors so commit the transaction.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Email the invoice to the user.
     *
     * @param object $invoice
     * @return void
     */
    public static function email_invoices($invoice) {
        global $CFG, $DB;

        if (empty($invoice)) {
            return;
        }

        // Get the paid for basket contents.
        $basket = helper::get_basket_by_id($invoice->id, helper::INVOICESTATUS_PAID);
        $invoice->itemized = helper::get_invoice_html($basket->id, 0, 0);

        // Notify shop admin.
        if (isset($CFG->commerce_admin_email)) {
            if (!$shopadmin = $DB->get_record('user', ['email' => $CFG->commerce_admin_email])) {
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

        if ($user = $DB->get_record('user',  ['id' => $invoice->userid])) {
            emailtemplate::send('invoice_ordercomplete', ['user' => $user, 'invoice' => $invoice, 'sender' => $shopadmin]);
            emailtemplate::send('invoice_ordercomplete_admin', ['user' => $shopadmin, 'invoice' => $invoice]);
        }
    }
}
