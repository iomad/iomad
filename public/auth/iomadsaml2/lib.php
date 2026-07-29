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
 * Main file
 *
 * @package auth_iomadsaml2
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Catalyst IT
 */

/**
 * Callback before HTTP headers are sent.
 *
 * This is called on every page.
 */
function auth_iomadsaml2_before_http_headers() {
    \auth_iomadsaml2\auto_login::process();
}

/**
 * Add service status checks
 *
 * @return array of check objects
 */
function auth_iomadsaml2_status_checks(): array {
    global $iomadsaml2auth;
    require_once(__DIR__ . '/setup.php');

    // Only if saml is configured then check certificate expiry.
    if ($iomadsaml2auth->is_configured()) {
        return [
            new \auth_iomadsaml2\check\certificateexpiry(),
        ];
    }
    return [];
}
