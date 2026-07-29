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
 * Plugin installation script.
 *
 * @package auth_iomadoidc
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft, Inc. (http://microsoft.com/)
 */

/**
 * Installation script.
 */
function xmldb_auth_iomadoidc_install() {
    global $DB;

    // Set the default value for the bindingusernameclaim setting.
    $bindingusernameclaimconfig = get_config('auth_iomadoidc', 'bindingusernameclaim');
    if (empty($bindingusernameclaimconfig)) {
        set_config('bindingusernameclaim', 'auto', 'auth_iomadoidc');
    }

    // Create unique constraint on (iomadoidcuniqid, tokenresource) to prevent duplicate tokens.
    // Use CREATE UNIQUE INDEX which works on both MySQL and PostgreSQL.
    // Note: PostgreSQL doesn't support column length prefixes, so we use full columns.
    // For MySQL, the columns are naturally short enough (GUID + resource URL).
    $sql = 'CREATE UNIQUE INDEX idx_iomadoidc_unique ON {auth_iomadoidc_token} (iomadoidcuniqid, tokenresource)';
    $DB->execute($sql);
}
