<?php
require_once('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

global $CFG;

echo "<h2>IOMAD Signup Setup Script</h2>";

echo "<h3>Current Configuration:</h3>";
echo "Enable: " . (get_config('local_iomad_signup', 'enable') ? 'YES' : 'NO') . "<br>";
echo "Auth types: " . (get_config('local_iomad_signup', 'auth') ?: 'NOT SET') . "<br>";

echo "<hr>";

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    echo "<h3>Applying Configuration...</h3>";
    
    set_config('enable', 1, 'local_iomad_signup');
    echo "✓ Enabled plugin<br>";
    
    set_config('auth', 'iomadoidc,iomadsaml2', 'local_iomad_signup');
    echo "✓ Set authentication types to: iomadoidc,iomadsaml2<br>";
    
    set_config('autoenrol', 1, 'local_iomad_signup');
    echo "✓ Enabled auto-enrolment<br>";
    
    echo "<hr>";
    echo "<h3>New Configuration:</h3>";
    $enable_status = get_config('local_iomad_signup', 'enable') ? '<strong style="color:green">YES</strong>' : 'NO';
    echo "Enable: " . $enable_status . "<br>";

    $auth_types = get_config('local_iomad_signup', 'auth');
    echo "Auth types: <strong style=\"color:green\">" . htmlspecialchars($auth_types) . "</strong><br>";

    $autoenrol_status = get_config('local_iomad_signup', 'autoenrol') ? '<strong style="color:green">YES</strong>' : 'NO';
    echo "Auto-enrol: " . $autoenrol_status . "<br>";
    
    echo "<hr>";
    echo "<p><strong style='color:green'>✓ Configuration complete!</strong></p>";
    echo "<p><a href='debug_signup.php'>View Diagnostics</a></p>";
    
} else {
    echo "<h3>This script will:</h3>";
    echo "<ol>";
    echo "<li>Enable the IOMAD Signup plugin</li>";
    echo "<li>Configure authentication types: <strong>iomadoidc, iomadsaml2</strong></li>";
    echo "<li>Enable auto-enrolment</li>";
    echo "</ol>";
    
    echo "<p><strong>This will allow users created via SCIM with iomadoidc or iomadsaml2 authentication to be automatically assigned to companies based on their email domain.</strong></p>";
    
    echo "<hr>";
    echo "<p><a href='?confirm=yes' style='background:green;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Apply Configuration</a></p>";
    echo "<p><a href='debug_signup.php'>Cancel and view diagnostics</a></p>";
}
