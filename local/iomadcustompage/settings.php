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
 * This file contains the settings for the custom page plugin.
 *
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use /**
 * Determines access permission for the external page in the core_admin module.
 *
 * This callback function is used to check whether a user has the appropriate permissions
 * to access a specific external page within the admin section of the application.
 *
 * The function should evaluate the user's current role, capabilities, and any other
 * contextual information required to determine access permission.
 *
 * @param stdClass $user The user object representing the currently logged-in user.
 * @param string $page The identifier for the external page being accessed.
 * @return bool Returns true if the user has access to the page, false otherwise.
 */
  core_admin\local\externalpage\accesscallback;
use local_iomadcustompage\permission;

defined('MOODLE_INTERNAL') || die();

  $ADMIN->add(
      'appearance',
      new admin_category('iomadcustompages', new lang_string('iomadcustompages', 'local_iomadcustompage')),
      'themes'
  );

  $ADMIN->add(
      'iomadcustompages',
      new accesscallback(
          'manageiomadcustompages',
          get_string('manageiomadcustompages', 'local_iomadcustompage'),
          (new moodle_url('/local/iomadcustompage/index.php'))->out(),
          static function (accesscallback $accesscallback): bool {
            return permission::can_view_pages_list();
          }
      )
  );
