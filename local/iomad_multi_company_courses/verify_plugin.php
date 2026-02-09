<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin structure verification script
 *
 * @package   local_iomad_multi_company_courses
 * @copyright 2025 Thomas Schlienger
 * @author    Thomas Schlienger
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This script can be run from the command line to verify plugin structure
// Usage: php verify_plugin.php

$required_files = [
    'version.php',
    'README.md',
    'index.php',
    'classes/multi_company_courses_form.php',
    'classes/privacy/provider.php',
    'lang/en/local_iomad_multi_company_courses.php',
    'db/access.php',
    'db/iomadmenu.php',
    'db/install.xml'
];

$plugin_dir = dirname(__FILE__);
$missing_files = [];
$found_files = [];

echo "IOMAD Multi-Company Courses Plugin Structure Verification\n";
echo "=========================================================\n\n";

foreach ($required_files as $file) {
    $filepath = $plugin_dir . '/' . $file;
    if (file_exists($filepath)) {
        $found_files[] = $file;
        echo "✓ $file\n";
    } else {
        $missing_files[] = $file;
        echo "✗ $file (MISSING)\n";
    }
}

echo "\nSummary:\n";
echo "Found: " . count($found_files) . " files\n";
echo "Missing: " . count($missing_files) . " files\n";

if (empty($missing_files)) {
    echo "\n✓ Plugin structure is complete!\n";
    echo "You can now install this plugin in your Moodle instance.\n";
} else {
    echo "\n✗ Plugin structure is incomplete.\n";
    echo "Missing files need to be created before installation.\n";
}

// Check if we can load the version info
if (file_exists($plugin_dir . '/version.php')) {
    $plugin = new stdClass();
    include($plugin_dir . '/version.php');
    
    echo "\nPlugin Information:\n";
    echo "Component: " . $plugin->component . "\n";
    echo "Version: " . $plugin->version . "\n";
    echo "Release: " . $plugin->release . "\n";
    echo "Maturity: " . $plugin->maturity . "\n";
    
    if (!empty($plugin->dependencies)) {
        echo "Dependencies:\n";
        foreach ($plugin->dependencies as $dep => $version) {
            echo "  - $dep (version $version)\n";
        }
    }
}

echo "\n";