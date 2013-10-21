<?php

require_once('processor.php');
require_once(dirname(__FILE__) . '/../../../local/iomad/lib/company.php');

class licenseblock extends processor {
    // update the invoice item with the latest license block settings
    function oncheckout($invoiceitem) {
        global $DB;

        if ($ii = $DB->get_record('invoiceitem', array('id' => $invoiceitem->id), '*')) {
            if ($block = get_license_block($ii->invoiceableitemid, $ii->license_allocation)) {
                $ii->currency = $block->currency;
                $ii->price = $block->price;
                $ii->license_validlength = $block->validlength;
                $ii->license_shelflife = $block->shelflife;

                $DB->update_record('invoiceitem', $ii);
            }
        }
    }
    
    function onordercomplete($invoiceitem, $invoice) {
        global $DB;
        
        $transaction = $DB->start_delegated_transaction();
        
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
        $companylicense->allocation = $invoiceitem->license_allocation;
        $companylicense->validlength = $invoiceitem->license_validlength;
        if (!empty($invoiceitem->license_shelflife)) {
            $companylicense->expirydate = ($invoiceitem->license_shelflife * 86400) + time() ;    // 86400 = 24*60*60 = number of seconds in a day
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
        
        // mark the invoice item as processed
        $invoiceitem->processed = 1;
        $DB->update_record('invoiceitem', $invoiceitem);
        
        $transaction->allow_commit();
    }
}
