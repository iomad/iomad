<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/email/lib.php');

function emails_report_cron() {
	global $DB;
	
	$runtime = time();
	echo "Running email report cron at ".date('D M Y h:m:s',$runtime)."\n"; 
	
	// Generate automatic reports
	// Training reaching lifetime/expired
	if ($checkcourses = $DB->get_records_sql('SELECT * from {iomad_courses} where validlength!=0')) {
	    // we have some courses which we need to check against
	    foreach ($checkcourses as $checkcourse) {
	        $expiredtext="";
	        $expiringtext="";
	        $latetext="";
	        echo "Get completion information for $checkcourse->courseid \n";
	        if ($coursecompletions = $DB->get_records('course_completions', array('course'=>$checkcourse->courseid))) {
	            // get the course information
	            $course = $DB->get_record('course', array('id'=>$checkcourse->courseid));
	            // we have completion information.
	            foreach ($coursecompletions as $completion) {
	                if (!empty($completion->timecompleted)) {
	                    // got a completed time
	                    if ($completion->timecompleted + $checkcourse->validlength*86400 > $runtime) {
	                        // got someone overdue
	                        $user = $DB->get_record('user',array('id'=>$completion->userid));
	                        echo "Sending overdue email to $user->email \n";
	                        EmailTemplate::send('expire', array('course'=>$course, 'user'=>$user));
	                        $expiredtext .= $user->firstname.' '.$user->lastname.', '.$user->email.' - '. date('D M Y', $completion->timecompleted)."\n";
	                    } else if ($completion->timecompleted + $checkcourse->validlength*86400 + $checkcourse->warnexpire*86400 > $runtime) {
	                        // we got someone approaching expiry
	                        $user = $DB->get_record('user',array('id'=>$completion->userid));
	                        echo "Sending exiry email to $user->email \n";
	                        EmailTemplate::send('expiry_warn_user', array('course'=>$course, 'user'=>$user));
	                        $expiringtext .= $user->firstname.' '.$user->lastname.', '.$user->email.' - '. date('D M Y', $completion->timecompleted)."\n";
	                    }
	                } else if (!empty($completion->timeenrolled)) {
	                    if ($completion->timeenrolled + $checkcourse->warncompletion*86400 > $runtime) {
	                        // go someone not completed in time
	                        $user = $DB->get_record('user',array('id'=>$completion->userid));
	                        echo "Sending completion warning email to $user->email \n";
	                        EmailTemplate::send('completion_warn_user', array('course'=>$course, 'user'=>$user));
	                        $latetext .= $user->firstname.' '.$user->lastname.', '.$user->email.' - '. date('D M Y', $completion->timeenrolled)."\n";
	                    }
	                }
	            }
	            // get the list of company managers
	            $companymanagers = $DB->get_records_sql("SELECT cm.userid from {companymanager} cm, {companycourse} cc WHERE cc.courseid = $checkcourse->courseid AND cc.companyid = cm.companyid");
	            $managers = array();
	            $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
	            foreach ($companymanagers as $companymanager) {
	                $user = $DB->get_record('user',array('id'=>$companymanager->userid));
	                if (has_capability('moodle/course:view',$course_context, $user)) {
	                    $managers[] = $user;
	                }
	            }
	                
	            // check if there are any managers on this course.
	            foreach ($managers as $manager) {
	                if (!empty($expiredtext)) {
	                    // send the summary email
	                    $course->reporttext = $expiredtext;
	                    EmailTemplate::send('expire_manager', array('course'=>$course, 'user'=>$manager));
	                }
	                if (!empty($expiringtext)) {
	                    // send the summary email
	                    $course->reporttext = $expiringtext;
	                    EmailTemplate::send('expiry_warn_manager', array('course'=>$course, 'user'=>$manager));
	                }
	                if (!empty($latetext)) {
	                    // send the summary email
	                    $course->reporttext = $latetext;
	                    EmailTemplate::send('completion_warn_manager', array('course'=>$course, 'user'=>$manager));
	                }
	            }
	        }
	    }
}
}