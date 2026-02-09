<?php
require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

if (!isset($_GET['userid'])) {
    die('Please provide userid parameter: ?userid=XXX');
}

$userid = required_param('userid', PARAM_INT);

echo "<h2>Debugging User Assignment for User ID: $userid</h2>";

global $DB;

$user = $DB->get_record('user', ['id' => $userid]);
if (!$user) {
    die('User not found');
}

echo "<h3>User Details:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>ID</td><td>{$user->id}</td></tr>";
echo "<tr><td>Username</td><td>{$user->username}</td></tr>";
echo "<tr><td>Email</td><td>{$user->email}</td></tr>";
echo "<tr><td>Auth</td><td>{$user->auth}</td></tr>";
echo "<tr><td>Firstname</td><td>{$user->firstname}</td></tr>";
echo "<tr><td>Lastname</td><td>{$user->lastname}</td></tr>";
echo "<tr><td>Time Created</td><td>" . userdate($user->timecreated) . "</td></tr>";
echo "</table>";

echo "<h3>Company Assignment Check:</h3>";

$companyuser = $DB->get_record('company_users', ['userid' => $userid]);
if ($companyuser) {
    $company = $DB->get_record('company', ['id' => $companyuser->companyid]);
    echo "<p style='color: green;'><strong>✓ User IS assigned to company:</strong> {$company->name} (ID: {$company->id})</p>";
} else {
    echo "<p style='color: red;'><strong>✗ User is NOT assigned to any company</strong></p>";
}

echo "<h3>Email Domain Check:</h3>";
$emaildomain = substr(strrchr($user->email, "@"), 1);
echo "<p>Email domain: <strong>$emaildomain</strong></p>";

$sql = "SELECT c.*, cd.domain 
        FROM {company} c
        JOIN {company_domains} cd ON cd.companyid = c.id
        WHERE cd.domain = :domain";
$companies = $DB->get_records_sql($sql, ['domain' => $emaildomain]);

if ($companies) {
    echo "<p style='color: green;'>✓ Found " . count($companies) . " company(ies) with this domain:</p>";
    echo "<ul>";
    foreach ($companies as $company) {
        echo "<li>{$company->name} (ID: {$company->id})</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ No companies found with domain: $emaildomain</p>";
}

echo "<h3>iomad_signup Configuration:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Plugin Enabled</td><td>" . (get_config('local_iomad_signup', 'enable') ? 'YES' : 'NO') . "</td></tr>";
echo "<tr><td>Allowed Auth Types</td><td>" . get_config('local_iomad_signup', 'auth') . "</td></tr>";
echo "</table>";

echo "<h3>Event Logs (last 10 user_created events):</h3>";
$events = $DB->get_records('logstore_standard_log', 
    ['eventname' => '\core\event\user_created'], 
    'timecreated DESC', 
    '*', 
    0, 
    10
);

if ($events) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Time</th><th>User ID</th><th>Related User</th></tr>";
    foreach ($events as $event) {
        $eventdata = unserialize($event->other);
        echo "<tr>";
        echo "<td>" . userdate($event->timecreated) . "</td>";
        echo "<td>{$event->userid}</td>";
        echo "<td>{$event->relateduserid}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No user_created events found</p>";
}

echo "<h3>Manual Test: Trigger iomad_signup for this user</h3>";
require_once($CFG->dirroot . '/local/iomad_signup/lib.php');

echo "<p>Calling local_iomad_signup_user_created() manually...</p>";
try {
    local_iomad_signup_user_created($user);
    echo "<p style='color: green;'><strong>✓ Function executed successfully</strong></p>";
    
    $companyuser = $DB->get_record('company_users', ['userid' => $userid]);
    if ($companyuser) {
        $company = $DB->get_record('company', ['id' => $companyuser->companyid]);
        echo "<p style='color: green;'><strong>✓ User NOW assigned to company:</strong> {$company->name}</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ User still NOT assigned</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error:</strong> " . $e->getMessage() . "</p>";
}
