<?php

class comprep{

    // get the jsmodule setup thingy
    function getJsModule() {
        $jsmodule = array(
            'name'     => 'local_report_completion',
            'fullpath' => '/course/report/iomad_completion/module.js',
            'requires' => array('base', 'node', 'charts', 'json'),
            'strings' => array(
                )
        );
        return $jsmodule;
    }

    // create the select list of courses
    function courseselectlist($companyid=0) {
        global $DB;
        global $SITE;

        // "empty" array
        $course_select = array(0=>get_string('select','local_report_completion'));

        // if the companyid=0 then there's no courses
        if ($companyid==0) {
            return $course_select;
        }

        // get courses for given company
        if (!$courses = $DB->get_records('company_course',array('companyid'=>$companyid))) {
            return $course_select;
        }
        // get the course names and put them in the list
        foreach ($courses as $course) {
            if ($course->courseid == $SITE->id) {
                continue;
            }
            $coursefull = $DB->get_record('course',array('id'=>$course->courseid));
            $course_select[$coursefull->id] = $coursefull->fullname;
        }
        return $course_select;
    }

    // create list of course users
    function participantsselectlist($courseid, $companyid ) {
        global $DB;

        // empty list
        $participant_select = array(0=>get_string('select','local_report_completion'));

        // if companyid = 0 then nothing to do
        if ($companyid == 0) {
            return $participant_select;
        }

        // get company
        if (!$company = $DB->get_record( 'company',array('id'=>$companyid))) {
            error( 'unable to find company record' );
        }

        // get list of users
        $users = self::getCompanyUsers( $company->shortname );

        // add to select list
        foreach ($users as $user) {
            $participant_select[ $user->id ] = fullname( $user );
        }

        return $participant_select;
    }

    // get the users that belong to company
    // with supplied short name
    // TODO: Also need to restrict by course, but difficult
    // to see what capability or role assignment to check
    function getCompanyUsers( $companyid ) {
        global $DB;

        if (! $dataids = $DB->get_records('company_users', array('company_id'=>$companyid))) {
            return array();
        }

        // run through and get users
        $users = array();
        foreach ($dataids as $dataid) {
            $userid = $dataid->userid;
            if (!$user = $DB->get_record( 'user',array('id'=>$userid))) {
                print_error( 'userrecordnotfound','local_report_completion' );
            } 
            $users[] = $user;
        }

        return $users;
    }

    // find completion data. $courseid=0 means all courses
    // for that company
    function get_completion( $companyid, $courseid=0, $wantedusers=null, $compfrom=0, $compto=0 ) {
        global $DB, $CFG;

        // get list of course ids
        $courseids = array();
        if ($courseid==0) {
            if (!empty($CFG->iomadglobalcourses)) {
            	if (!$courses = $DB->get_records_sql("SELECT c.id AS courseid FROM {course} c 
            	                                     WHERE c.id in ( SELECT courseid FROM {companycourse} WHERE companyid = $companyid ) 
            	                                     OR c.id in ( SELECT pc.courseid FROM {iomad_courses} pc INNER JOIN {company_shared_courses} csc 
            	                                     ON pc.courseid=csc.courseid where pc.shared=2 AND csc.companyid = $companyid )
            	                                     OR c.id in ( SELECT pc.courseid FROM {iomad_courses} pc WHERE pc.shared=1)")) {
                    // no courses for company, so exit
                    return false;
                }
            } else {
        	    if (!$courses = $DB->get_records('companycourse',array('companyid'=>$companyid))) {
                    // no courses for company, so exit
                    return false;
                }
            }
            foreach ($courses as $course) {
                $courseids[] = $course->courseid;
            }
        }
        else {
            $courseids[] = $courseid;
        }

        // going to build an array for the data
        $data = array();

        // count the three statii for the graph
        $notstarted = 0;
        $inprogress = 0;
        $completed = 0;

        // get completion data for each course
        foreach ($courseids as $courseid) {

            // get course object
            if (!$course = $DB->get_record('course',array('id'=>$courseid))) {
                error( 'unable to find course record' );
            }
            $datum = null;
            $datum->coursename = $course->fullname;

            // instantiate completion info thingy
            $info = new completion_info( $course );

            // if completion is not enabled on the course
            // there's no point carrying on
            if (!$info->is_enabled()) {
                $datum->enabled = false;
                $data[ $courseid ] = $datum;
                continue;
            }
            else {
                $datum->enabled = true;
            }

            // get criteria for coursed
            // this is an array of tracked activities (only tracked ones)
            $criteria = $info->get_criteria();

            // number of tracked activities to complete
            $tracked_count = count( $criteria );
            $datum->tracked_count = $tracked_count;

            // get data for all users in course
            // this is an array of users in the course. It contains a 'progress'
            // array showing completed *tracked* activities
            $progress = $info->get_progress_all();

            // iterate over users to get info
            $users = array();
            $numusers = 0;
            $numprogress = 0;
            $numcomplete = 0;
            $numnotstarted = 0;
            foreach ($wantedusers as $wanteduser) {
                if (empty($progress[$wanteduser])) {
                	continue;
                }
                $user = $progress[$wanteduser];
                
                ++$numusers;
                $u = null;
                $u->fullname = fullname( $user );

                // count of progress is the number they have completed
                $u->completed_count = count( $user->progress );
                if ($tracked_count>0) {
                    $u->completed_percent = round(100 * $u->completed_count / $tracked_count, 2);
                }
                else {
                    $u->completed_percent = '0';
                }
                // find user's completion info for this course
                if ($completioninfo = $DB->get_record( 'course_completions',array('userid'=>$user->id, 'course'=>$courseid))) {
                    if ((!empty($compfrom) || !empty($compto)) && empty($completioninfo->timecompleted)) {
                        continue;
                    } else if (!empty($compfrom) && ($completioninfo->timecompleted < $compfrom)) {
                        continue;
                    } else if (!empty($compto) && ($completioninfo->timecompleted > $compto)) {
                        continue;
                    } else {
                        $u->timeenrolled = $completioninfo->timeenrolled;
                        if (!empty($completioninfo->timestarted)) {
                        	$u->timestarted = $completioninfo->timestarted;
    	                    if (!empty($completioninfo->timecompleted)) {
    	                    	$u->timecompleted = $completioninfo->timecompleted;
    		                    $u->status = 'completed';
    		                    ++$numcomplete;
    	                    } else {
    	                    	$u->timecompleted = 0;
        	                    $u->status = 'inprogress';
    	                        ++$numprogress;
    	                    }
                        	
                        } else {
                        	$u->timestarted = 0;
                        	$u->status = 'notstarted';
                            ++$numnotstarted;
                        }
                    }
                    
                }
                else {
                    $u->timeenrolled = 0;
                    $u->timecompleted = 0;
                    $u->timestarted = 0;
                    $u->status = 'notstarted';
                    ++$numnotstarted;
                }
                
                //get the users score
                $GBSQL = "select gg.finalgrade as result from {grade_grades} gg, {grade_items} gi 
                          WHERE gi.courseid=$courseid AND gi.itemtype='course' AND gg.userid=".$user->id."
                          AND gi.id=gg.itemid";
                if (!$gradeinfo = $DB->get_record_sql($GBSQL)) {
                    $gradeinfo = new object();
                    $gradeinfo->result = null;
                }
                $u->result = round($gradeinfo->result,0);
                $userinfo = $DB->get_record('user',array('id'=>$user->id));
                $u->email = $userinfo->email;
                $u->id = $user->id;
                
                $u->department = company_user::get_department_name($user->id);

                // add to revised user array
                $users[$user->id] = $u;
            }
            $datum->users = $users;
            $datum->completed = $numcomplete;
            $datum->numusers = $numusers;
            $datum->started = $numnotstarted;
            $datum->inprogress = $numprogress;
         
            $data[ $courseid ] = $datum;
        }

        // make the data for the graph
        $graphdata = array('notstarted'=>$notstarted,'inprogress'=>$inprogress,'completed'=>$completed);

        // make return object
        $returnobj = null;
        $returnobj->data = $data;
        $returnobj->graphdata = $graphdata;

        return $returnobj;
    }

    // draw the pie chart
    // not config.php has NOT been included so
    // act accordingly!!
    static function drawchart($data) {

        // include the chart libraries
        $plib = '../iomad/pchart';
        require_once( "$plib/class/pDraw.class.php" );
        require_once( "$plib/class/pPie.class.php" );
        require_once( "$plib/class/pImage.class.php" );
        require_once( "$plib/class/pData.class.php" );

        // chart data
        $chartData = new pData();
        $chartData->addPoints( array($data->notstarted,$data->inprogress,$data->completed),"Value" );

        // labels
        $chartData->addPoints(array('Not started','In progress','Completed'),"Legend");
        $chartData->setAbscissa("Legend");

        // chart object
        $chart = new pImage(350,180,$chartData );

        // pie chart object
        $pie = new pPie($chart, $chartData);
        $chart->setShadow(FALSE);
        $chart->setFontProperties(array("FontName"=>"$plib/fonts/GeosansLight.ttf","FontSize"=>11));
        $pie->setSliceColor(0,array("R"=>200,"G"=>0,"B"=>0));
        $pie->setSliceColor(1,array("R"=>200,"G"=>200,"B"=>0));
        $pie->setSliceColor(2,array("R"=>0,"G"=>200,"B"=>0));
        $pie->draw3Dpie(175,100,
            array(
                "Radius"=>80,
                "DrawLabels"=>TRUE,
                "DataGapAngle"=>10,
                "DataGapRadius"=>6,
                "Border"=>TRUE
            )
        );

        // display the chart
        $chart->stroke();
    }

	/** 
	 * Sort array of objects by field. 
	 * 
	 * @param array $objects Array of objects to sort. 
	 * @param string $on Name of field. 
	 * @param string $order (ASC|DESC) 
	 */ 
	function sort_on_field(&$objects, $on, $order ='ASC') { 
	    $comparer = ($order === 'DESC') 
	        ? "return -strcmp(\$a->{$on},\$b->{$on});" 
	        : "return strcmp(\$a->{$on},\$b->{$on});"; 
	    usort($objects, create_function('$a,$b',$comparer)); 
	} 
}

