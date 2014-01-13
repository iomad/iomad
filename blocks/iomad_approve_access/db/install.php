<?php

// this script is run after the dashboard has been installed

function xmldb_block_iomad_approve_access_install() {
    global $USER, $DB;
    
    // only do this when we are not installing for the first time.
    // That is handled elsewhere.
    if (!empty($USER)) {
        // add this block to the dashboard
        // (yes, I know this isn't really what this is for!!)
        $reportblock = $DB->get_record('block_instances', array('blockname' => 'iomad_reports', 'pagetypepattern' => 'local-dashboard-index'));
        $approveblock = $reportblock;
        $approveblock->blockname = 'iomad_approve_access';
        $approveblock->id = null;
        $DB->insert_record('block_instances', $approveblock);
        $reportblock = $DB->get_record('block_instances', array('blockname' => 'iomad_reports', 'pagetypepattern' => 'local-dashboard-index'));
        $reportblock->defaultweight = $reportblock->defaultweight + 1;
        $DB->update_record('block_instances', $reportblock);
    }

    return true;
}
