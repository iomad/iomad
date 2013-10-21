<?php

//this script is run after the report_scheduler block has been installed

 function xmldb_block_iomad_report_scheduler_install() {
/*    global $SITE;

    // if it doesn't exist add the iomad_report_scheduler block to the front page
    $page = new moodle_page();
    $page->set_course( $SITE );
    $page->set_pagetype( 'site-index' );
    $page->blocks->add_regions( array(BLOCK_POS_RIGHT) );
    $page->blocks->add_block( 'iomad_report_scheduler', BLOCK_POS_RIGHT, 0, false );
  */
    return true;
}
