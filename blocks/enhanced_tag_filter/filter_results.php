<?php

use core_course_list_element;
use stdClass;
// Load Moodle
require_once('../../config.php');
global $DB;
$companyid = iomad::get_my_companyid(context_system::instance(), false); // get company

// Ensure user is logged in
require_login();



try {
    // Validate incoming data
    $tags = urldecode(optional_param('tags', null, PARAM_TEXT));
    $array = json_decode($tags);

    // Prepare placeholders for each tag
    $tagsArray = [];
    foreach ($array as $element) {
        // Remove [, and ,] from each element in the array
        $element = trim($element, '[]"');
        // Split the tags by comma
        $tags = explode(',', $element);
        // Append the tags to the $tagsArray
        foreach ($tags as $tag) {
            $tagsArray[] = trim($tag);
        }
    }

    // Prepare placeholders for each value
    $placeholders = implode(', ', array_fill(0, count($tagsArray), '?'));
    $count = count($tagsArray);
    


    $sql = "SELECT c.id, c.fullname, cat.name as category, parentcat.name as parentcategory
    FROM {course} c
    JOIN {tag_instance} ti ON ti.itemid = c.id
    LEFT JOIN {company_course} ac ON ac.courseid = c.id -- get company course assignments
    LEFT JOIN {course_categories} cat ON cat.id = c.category -- get category
    LEFT JOIN {course_categories} parentcat ON parentcat.id = cat.parent -- get parent category
    -- JOIN {tag} t ON t.id = ti.tagid
    WHERE ti.tagid IN ($placeholders)
    AND ac.companyid = $companyid -- restrict to assigned courses
    GROUP BY  ti.itemid
    HAVING COUNT(DISTINCT ti.tagid) = $count";


    // $sql = "SELECT c.id, c.fullname, t.name AS tag_name
    // FROM {course} c
    // JOIN {tag_instance} ti ON ti.itemid = c.id
    // JOIN {tag} t ON t.id = ti.tagid
    // WHERE t.id IN ($placeholders)
    // GROUP BY c.id, c.fullname, t.name
    // HAVING COUNT(DISTINCT t.name) >= ?";

    // Prepare parameters array with named placeholders
    $params = $tagsArray;
    $params[] = 1;

    $c=0;
    foreach ($params as $param) {
        if(strpos($param,']'))
        {
        
            $params[$c] = "[" . $param;
        }else{
            
        }
        $c++;
    }
    // unset($param); 
    
    //array_push($params, array('companyid' => $companyid,)); //add companyid to params

     // Execute the SQL query with selected tags
    $filteredResults = $DB->get_records_sql($sql, $params);
    // Organize courses by tag name
    $taggedCourses = [];
    foreach ($filteredResults as $course) {
        $tagName = $course->tag_name;
        if (!isset($taggedCourses[$tagName])) {
            $taggedCourses[$tagName] = [];
        }
        $taggedCourses[$tagName][] = $course;
        if (isset($course->parentcategory) && $course->parentcategory !== '') {
            $course->parentcategory = format_string($course->parentcategory, $striplinks = false); // if parent category is available, take its multilingual string
        } else {
            $course->parentcategory = format_string($course->category, $striplinks = false); // if not, take the multilingual category string
        }
    }

    // Output filtered results in desired format (e.g., HTML)
    $htmlCourse = '';	$gototext = get_string('stringaccess', 'theme_iomadmoon');
    foreach ($taggedCourses as $tagName => $courses) {
        $tagName = ucfirst($tagName); // Capitalize the first letter of the tag name
        $htmlCourse .= '<fieldset class="taggeditems fieldset-styled" style="border: 0px; padding: 10px;">'; // Pre-fieldset
	$htmlCourse .= '<div class="courses course-search-result course-search-result-tagid">'; // Container for each tag
        $htmlCourse .= '<div class="rui-course-card-deck mt-2">'; // Grid layout for courses
        foreach ($courses as $course) {
	    $MultiCourseName = format_string($course->fullname, $striplinks = false);
            $htmlCourse .= '<div class="rui-course-card rui-progress-0" role="listitem" data-region="course-content" data-course-id='.$course->id.'>
                <div class="rui-course-card-wrapper position-relative">
                    <a href='.$CFG->wwwroot.'/course/view.php?id='.$course->id.' tabindex="-1">
                      <figure class="rui-course-card-img-top" style="background-image: url('.course_image($course->id).');" alt="Course image"><span class="sr-only">Course image"  '.$course->fullname.'</span></figure>
                    </a>
                    <div class="rui-course-card-body">
                        <div class="d-flex flex-wrap">
                            <span class="sr-only">Course image</span>
                        </div>
                        <div class="d-flex mb-1">
                            <h4 class="rui-course-card-title mb-1">
                                <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" class="aalink coursename">
                                    <span class="sr-only">Course name</span>
                                    '.$MultiCourseName.'
                                </a>
                            </h4>
                        </div>
                        <div class="rui-course-card-text">
                            <span class="sr-only">Course summary text:</span>
                        </div>
                        <div class="d-inline-flex mt-2">
                            <div class="rui-course-cat-badge">
                                <div class="text-truncate">'.$course->parentcategory.'</div> <!--show course category badge-->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rui-course-card-footer">
                    <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" class="rui-course-card-link btn btn-primary">
                        <span class="rui-course-card-link-text">'.$gototext.'</span>
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.75 6.75L19.25 12L13.75 17.25"></path>
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H4.75"></path>
                        </svg>
                    </a>
                </div>
            </div>';
        }
        $htmlCourse .= '</div>'; // Close grid layout
        $htmlCourse .= '</div>'; // Close container for each tag
	$htmlCourse .= '</fieldset>'; // Close fieldset
    }
    echo $htmlCourse;
} catch (dml_exception $e) {
    // Log detailed error message
    //error_log('Database error: ' . $e->getMessage());
    // Return generic error message
    //echo json_encode(['error' => $e->getMessage()]);
}

//  function generated_image_for_id($id) {
//     global $CFG, $OUTPUT;

//     $theme = \theme_config::load('iomadmoon');
    
//     // Add custom course cover.
//     $customcover = $theme->setting_file_url('defaultcourseimg', 'defaultcourseimg');

//     if (!empty(($customcover))) {
//         $urlreplace = preg_replace('|^https?://|i', '//', $CFG->wwwroot);
//         $customcover = str_replace($urlreplace, '', $customcover);
//         $txt = new moodle_url($customcover);
//         return strval($txt);
//     } else {
//         $color = $OUTPUT->get_generated_color_for_id($id);
//         $pattern = new \core_geopattern();
//         $pattern->setColor($color);
//         $pattern->patternbyid($id);
//         return $pattern->datauri();
//     }
// }

function course_image($courseid = null, $course = null)
{
    global $DB, $CFG, $OUTPUT;

    $courserecord = $course ?: $DB->get_record('course', array('id' => $courseid));

    if ($courserecord instanceof stdClass) {
        $course = new core_course_list_element($courserecord);
        try {
            foreach ($course->get_course_overviewfiles() as $file) {
                if ($file->is_valid_image()) {
                    return moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        null,
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(false);
                    // Use the first image found.
                    break;
                }
            }
        } catch (\Throwable $th) {
            // Handle any exceptions if needed.
        }
    }

    return $OUTPUT->get_generated_image_for_id($courseid);
}
