<?php

class iomad_helper {
	
  const VERSION = '20190208';
	
  // Emails to block, set to 1. Keys from Iomad Dashboard - Email Templates
  private static $blocked_emails = array(
    'advertise_classroom_based_course' => 0,
    'approval' => 0,
    'completion_course_supervisor' => 0,
    'completion_digest_manager' => 0,
    'completion_expiry_warn_supervisor' => 0,
    'completion_warn_manager' => 0,
    'completion_warn_supervisor' => 0,
    'completion_warn_user' => 0,
    'course_classroom_approval' => 0,
    'course_classroom_approval_request' => 0,
    'course_classroom_approved' => 0,
    'course_classroom_denied' => 0,
    'course_classroom_manager_denied' => 0,
    'course_completed_manager' => 0,
    'expire' => 0,
    'expire_manager' => 0,
    'expiry_warn_manager' => 0,
    'expiry_warn_user' => 0,
    'invoice_ordercomplete' => 0,
    'invoice_ordercomplete_admin' => 0,
    'license_allocated' => 0,
    'license_reminder' => 0,
    'license_removed' => 1,
    'password_update' => 0,
    'user_added_to_course' => 1,
    'user_create' => 0,
    'user_removed_from_event' => 0,
    'user_reset' => 0,
    'user_signed_up_for_event' => 0,
  );
  
  public static function is_email_blocked($templatename){
    return !empty(self::$blocked_emails[$templatename]);
 }
 
  public static function has_role($shortname, $user_id, $context){
    global $DB;
    foreach(get_user_roles($context, $user_id) as $role){
      if($role->shortname == $shortname){
        return true;
      }
    }
    return false;
  }

  public static function create_license_name($license) {
    // reformat license name
    $pos = strrpos($license->name, '(');
    if($pos === false){
        $product_name = $license->name;
    }else{
        $product_name = trim(substr($license->name, 0, $pos));
    }
	$licenses_left = max(0, $license->allocation - $license->used);
    $license_name = sprintf('%s (%s/%s) %s', $product_name, $licenses_left, $license->allocation, date('Y-m-d', $license->expirydate));
    return $license_name;
  }
	
  public static function user_license_count_changed_callback($event) {
	// 2 observers defined in local/iomad/db/events.php use this callback
    global $DB; 	  
    $licenseid = $event->other['licenseid'];
    if ($license = $DB->get_record('companylicense', array('id' => $licenseid))) {
      $license->name = self::create_license_name($license);
      $DB->update_record('companylicense', $license);
    }
  }
	
}


