<?php
require_once('../../config.php');
require_login();

$selectedTags = $_GET['tags'];
$selectedTags = !empty($selectedTags) ? json_decode($selectedTags, true) : [];

global $DB;

// Convert array to comma-separated string for the SQL query
$tagIdsString = implode(',', array_map(function ($tagId) {
    return $tagId;
}, (array)$selectedTags));

// Count the number of selected tags
$numSelectedTags = count($selectedTags);

// Construct the SQL query to select items that have all selected tags
$sql = "SELECT itemid
        FROM {tag_instance}
        WHERE tagid IN ($tagIdsString)
        GROUP BY itemid
        HAVING COUNT(DISTINCT tagid) = $numSelectedTags";

$results = $selectedTags ? $DB->get_records_sql($sql) : [];
$matchingItemIds = [];

foreach ($results as $result) {
    $matchingItemIds[] = $result->itemid;
}

// Retrieve the tags associated with the matching items
$matchingTags = [];
foreach ($matchingItemIds as $itemId) {
    $tagSql = "SELECT tagid 
               FROM {tag_instance} 
               WHERE itemid = :itemid";
    $tagResults = $DB->get_records_sql($tagSql, ['itemid' => $itemId]);
    foreach ($tagResults as $tagResult) {
        $matchingTags[] = $tagResult->tagid;
    }
}

// Remove duplicates and reindex the array starting from 0
$matchingTags = array_unique($matchingTags);
$matchingTags = array_values($matchingTags);

// Set the content type header to JSON
header('Content-Type: application/json');

// Echo the response array
echo json_encode([0 => $matchingTags]);

