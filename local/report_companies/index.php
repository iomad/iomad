<?php

require_once('../../config.php');
require_once('lib.php');

// Check permissions
require_login($SITE);
$context=get_context_instance(CONTEXT_SYSTEM);
require_capability('local/report_companies:view', $context);


// url stuff
$url = new moodle_url('/local/report_companies/index.php');

//page stuff:
$strcompletion = get_string('pluginname','local_report_companies');
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($strcompletion);
//$PAGE->set_heading($course->fullname);
$PAGE->requires->css("/local/report_companies/styles.css");
//$PAGE->navbar->add(get_string('reports'), new moodle_url('/course/report.php', array('id'=>$course->id)));
$PAGE->navbar->add(get_string('pluginname','local_report_companies'), $url );

// Navigation and header
echo $OUTPUT->header();
echo $OUTPUT->heading( get_string('pluginname','local_report_companies') );

// ajax odds and sods
$PAGE->requires->js_init_call( 'M.local_report_companies.init');

// get the company list
$companies = companyrep::companylist( $USER );
companyrep::addmanagers( $companies );
companyrep::addusers( $companies );
companyrep::addcourses( $companies );

// iterate over companies
foreach ($companies as $company) {
    echo "<div class=\"iomad_company\" />\n";
    echo "<h2>{$company->name}</h2>";

    // managers
    echo "<div class=\"iomad_managers\" />\n";
    if (empty($company->managers)) {
        echo "<strong>".get_string('nomanagers','local_report_companies')."</strong>";
    }
    else {
        echo "<h4>".get_string('coursemanagers','local_report_companies')."</h4>\n";
        companyrep::listusers( $company->managers );
    }
    echo "</div>\n";

    // users
    echo "<div class=\"iomad_users\" />\n";
    if (empty($company->users)) {
        echo "<strong>".get_string('nousers','local_report_companies')."</strong>";
    }
    else {
        echo "<h4>".get_string('courseusers','local_report_companies')."</h4>\n";
        // companyrep::listusers( $company->users );
        echo get_string('totalusercount', 'local_report_companies') . count($company->users) . ' <a href="' .
        new moodle_url('/local/report_users/index.php').'">'.
        get_string('completionreportlink', 'local_report_companies') . '</a>';
    }
    echo "</div>\n";

    // Courses
    echo "<div class=\"iomad_courses\" />\n";
    if (empty($company->courses)) {
        echo "<strong>".get_string('nocourses','local_report_companies')."</strong>";
    }
    else {
        echo "<h4>".get_string('courses','local_report_companies')."</h4>\n";
        //companyrep::listusers( $company->users );
        echo get_string('totalcoursecount', 'local_report_companies'). count($company->courses) . ' <a href="' .
        new moodle_url('/local/report_completion/index.php').'">'.
        get_string('completionreportlink', 'local_report_companies') . '</a>';
    }
    echo "</div>\n";

    // Theme
    echo "<div class=\"iomad_Theme\" />\n";
    if (empty($company->theme)) {
        echo "<strong>".get_string('notheme','local_report_companies')."</strong>";
    }
    else {
        echo "<h4>".get_string('themeinfo','local_report_companies')."</h4>\n";
        echo get_string('themedetails', 'local_report_companies'). $company->theme;
        $screenshotpath = new moodle_url('/theme/image.php', array('theme'=>$company->theme, 'image'=>'screenshot','component'=>'theme'));
        echo '<p>'.html_writer::empty_tag('img', array('src'=>$screenshotpath, 'alt'=>$company->theme)) .'</p>';
    }
    echo "</div>\n";

    echo "</div>\n";
}

echo $OUTPUT->footer();
