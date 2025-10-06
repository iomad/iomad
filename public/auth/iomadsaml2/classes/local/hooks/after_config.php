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

namespace auth_iomadsaml2\local\hooks;

/**
 * after_config hook callback for auth_iomadsaml2
 *
 * @package    auth_iomadsaml2
 * @copyright  2024 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_config {
    /**
     * Callback executed from setup.php, available from Moodle 3.8 in core
     *
     * @param \core\hook\after_config $hook
     */
    public static function callback(\core\hook\after_config $hook): void {
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }

        try {
            $iomadsaml = optional_param('iomadsaml', null, PARAM_BOOL);
            if ($iomadsaml == 1) {
                if (isguestuser()) {
                    // We want to force users to log in with a real account, so log guest users out.
                    require_logout();
                }
                // We have the saml=on param set. Disable guest access (in memory -
                // not saved in database) to force the login with saml for this request.
                unset($CFG->autologinguests);
            }
        } catch (\Exception $exception) {
            debugging('auth_iomadsaml2_after_config error', DEBUG_DEVELOPER, $exception->getTrace());
        }
    }
}
