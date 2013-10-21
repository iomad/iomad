<?php

require_once('processor.php');
require_once(dirname(__FILE__) . '/../../../local/iomad/lib/user.php');

class singlepurchase extends processor {
    // update the invoice item with the latest single purchase price and license valid_length
    function oncheckout($invoiceitem) {
        global $DB;
        
        if ($ii = $DB->get_record_sql('SELECT ii.*, css.single_purchase_currency, css.single_purchase_price, css.single_purchase_validlength
                                       FROM
                                            {invoiceitem} ii
                                            INNER JOIN {course_shopsettings} css ON css.courseid = ii.invoiceableitemid
                                       WHERE
                                            ii.id = :invoiceitemid', array('invoiceitemid' => $invoiceitem->id)))
        {
            $ii->currency = $ii->single_purchase_currency;
            $ii->price = $ii->single_purchase_price;
            $ii->license_validlength = $ii->single_purchase_validlength;
            $DB->update_record('invoiceitem', $ii);
        }
    }
    
    function onordercomplete($invoiceitem, $invoice) {
        global $DB;
        
        $transaction = $DB->start_delegated_transaction();
        
        if ($DB->get_record('iomad_courses', array('courseid'=>$invoiceitem->invoiceableitemid,'licensed'=>1))) {
            // get the item's single purchase details
            $courseinfo = $DB->get_record('course_shopsettings',array('courseid'=>$invoiceitem->invoiceableitemid));
            
                    // get name for company license
            $company = company::get_company_byuserid($invoice->userid);
            $course = $DB->get_record('course', array('id' => $invoiceitem->invoiceableitemid), 'id, shortname', MUST_EXIST);
            $licensename = $company->shortname . " [" . $course->shortname . "] " . date("Y-m-d");
            
            $count = $DB->count_records_sql("SELECT COUNT(*) FROM {companylicense} WHERE name LIKE '" . (str_replace("'","\'",$licensename)) . "%'");
            if ($count) {
                $licensename .= ' (' . ($count + 1) . ')';
            }
    
            // create mdl_companylicense record
            $companylicense = new stdClass;
            $companylicense->name = $licensename;
            $companylicense->allocation = 1;
            $companylicense->used = 1;
            $companylicense->validlength = $courseinfo->single_purchase_validlength;
            if (!empty($courseinfo->single_purchase_shelflife)) {
                $companylicense->expirydate = ($courseinfo->single_purchase_shelflife * 86400) + time() ;    // 86400 = 24*60*60 = number of seconds in a day
            } else {
                $companylicense->expirydate = 0;
            }
            $companylicense->companyid = $company->id;
            $companylicenseid = $DB->insert_record('companylicense', $companylicense);
            
            // create mdl_companylicense_courses record for the course
            $clc = new stdClass;
            $clc->licenseid = $companylicenseid;
            $clc->courseid = $course->id;
            $DB->insert_record('companylicense_courses', $clc);
            
            //  assign the license to the user
            $DB->insert_record('companylicense_users',array('userid'=>$invoice->userid, 'licenseid'=>$companylicenseid));
        
        } else {
            // enrol user into course
            $user = new stdClass;
            $user->id = $invoice->userid;
            company_user::enrol($user, array($invoiceitem->invoiceableitemid));
        }
        
        // mark the invoice item as processed
        $invoiceitem->processed = 1;
        $DB->update_record('invoiceitem', $invoiceitem);

        $transaction->allow_commit();
    }
}
