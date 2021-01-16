<?php

namespace availability_userpermit;

defined('MOODLE_INTERNAL') || die();

class condition extends \core_availability\condition {

  protected $permitvalid;
  protected $fieldname = 'Permitexpiry'; // shortname of profile field
  
  public function __construct($structure) {
    // retrieve $structure from database
    if(isset($structure->checkpermit)){
      $this->checkpermit = $structure->checkpermit;
    }else{
//      $this->checkpermit = 0;
      $this->checkpermit = $structure->checkpermit;
    }
  }
  
  public function save() {
    // save to database
    return (object) array('type' => 'name', 'checkpermit' => $this->checkpermit);
  }
  
  public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {

    global $USER;
    
    if(!isloggedin()){
      return false;
    }
    if(isguestuser($userid)){
      return false;
    }
    if($USER->id != $userid){
      return false;
    }
//    if(empty($USER->profile[$this->fieldname])){
//      return false;
//    }

    // get expiry timestamp
    if(0){ 
      // value cached in user session
      $expiry_timestamp = (int) $USER->profile[$this->fieldname];
    }else{ 
      // value from database
      global $DB;
      $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => $this->fieldname), IGNORE_MISSING);
      $expiry_timestamp = (int) $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => $fieldid), IGNORE_MISSING);
    }
    
    
    // compare with today's date, today is valid, yesterday is invalid, be aware of the time in time()
    $current_timestamp = time();
//    (SG) if ($current_timestamp >= $expiry_timestamp) {
//    var_dump("current_timestamp <br>\n\r\n\r",$current_timestamp, date("Ymd",$current_timestamp));
//    var_dump("expiry_timestamp <br>\n\r\n\r",$expiry_timestamp, date("Ymd",$expiry_timestamp));
    
    if ( date("Ymd",$expiry_timestamp) < date("Ymd",$current_timestamp)) {
      $this->permitvalid = false;
    }else{
      $this->permitvalid = true;
    }
   
    // $not should be used to negate the condition 
    if($not){
      return !$this->permitvalid;
    }else{
      return $this->permitvalid;
    }
    
  }
/* SG
  public function get_description($full, $not, \core_availability\info $info) {
    // get information for editing screens
    if($not){
      return !$this->checkpermit ? get_string('allowed_description', 'availability_userpermit') : get_string('disallowed_description', 'availability_userpermit');
    }else{
      return $this->checkpermit ? get_string('allowed_description', 'availability_userpermit') : get_string('disallowed_description', 'availability_userpermit');
    }
  }
*/
/*GH*/
  public function get_description($full, $not, \core_availability\info $info) {
    // get information for editing screens
    if($not){
      return get_string('disallowed_description', 'availability_userpermit');
    }else{
      return get_string('allowed_description', 'availability_userpermit');
    }
  }
  
  public function get_debug_string() {
    return $this->permitvalid ? 'True' : 'False';
  }
  
}