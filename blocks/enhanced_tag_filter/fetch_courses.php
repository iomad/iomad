<?php
// fetch_courses.php
require_once('../../config.php'); // Adjust the path as per your Moodle plugin structure
$companyid = iomad::get_my_companyid(context_system::instance(), false); // get company

// Ensure that only AJAX requests are processed
if (!is_ajax()) {
    die('Unauthorized access');
}

// Check if the tag ID is provided in the request
if (!isset($_POST['tag_id'])) {
    die('Tag ID is missing');
}

$tagId = $_POST['tag_id'];

// Fetch courses related to the clicked tag
$sql = "SELECT c.id, c.fullname
        FROM {course} c
        JOIN {tag_instance} ti ON ti.itemid = c.id
        LEFT JOIN {company_course} ac ON ac.courseid = c.id -- get company course assignments
        WHERE ti.tagid = :tag_id
        AND ac.companyid = :companyid"; // restrict to assigned courses

$params = array(
    'tag_id' => $tagId,
    'companyid' => $companyid,
);

$courses = $DB->get_records_sql($sql, $params);

// Output JSON response
header('Content-Type: application/json');
echo json_encode($courses);

// Helper function to check if the request is AJAX
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
?>
