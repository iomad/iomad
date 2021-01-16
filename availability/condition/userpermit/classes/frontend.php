<?php

namespace availability_userpermit;

defined('MOODLE_INTERNAL') || die();

class frontend extends \core_availability\frontend {

  protected function get_javascript_strings() {
    // return language strings used in js
    return array('title', 'form_description');
  }
  
  protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
    // return parameters used in js init method
    return array();
  }
  
}