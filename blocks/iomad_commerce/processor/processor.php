<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../lib.php');

class processor {
    static function trigger_oncheckout($invoiceid) {
        self::process_all_items($invoiceid, 'oncheckout');
        $_SESSION['Payment_Amount'] = get_basket_total();
        create_invoice_reference($invoiceid);
    }
    
    static function trigger_onordercomplete($invoice) {
        self::process_all_items($invoice->id, 'onordercomplete', $invoice );
    }
    
    private static function process_all_items($invoiceid, $eventname, $invoice = null) {
        global $DB;
        
        if ($items = $DB->get_records('invoiceitem', array('invoiceid' => $invoiceid, 'processed' => 0), null, '*')) {
            $curdir = dirname(__FILE__) . '/';
            foreach($items as $item) {
                $processorname = $item->invoiceableitemtype;
                $path = $curdir . $processorname . '.php';
                if (file_exists($path)) {
                    require_once($path);
                    $p = new $processorname;
                    $p->$eventname($item, $invoice);
                }
            }
        }
    }
    
    static function trigger_invoiceitem_onordercomplete($invoiceitemid, $invoice) {
        global $DB;
        
        if ($item = $DB->get_record('invoiceitem', array('id' => $invoiceitemid, 'processed' => 0), '*')) {
            $curdir = dirname(__FILE__) . '/';
            $processorname = $item->invoiceableitemtype;
            $path = $curdir . $processorname . '.php';
            if (file_exists($path)) {
                require_once($path);
                $p = new $processorname;
                $p->onordercomplete($item, $invoice);
            }
        }
    }

    // methods to be overridden in subclasses
    function oncheckout($invoiceitem) {
    }
    
    function pre_order_review_processing() {
    }
    
    function onordercomplete($invoiceitem, $invoice) {
    }
}
