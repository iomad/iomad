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
 * Local IOMAD
 *
 * @package    local_iomad
 * @copyright  e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use core\hook\after_config;
use local_iomad\custom_context\context_company;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/iomadcustompage/lib.php');

/**
 * Local IOMAD hook callbacks class
 *
 * @package    local_iomad
 * @copyright  e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Listener for the after_config hook.
     *
     * @param after_config $hook
     */
    public static function after_config(after_config $hook): void {
        global $CFG;

        // Set up the company context - if not already.
        if (!defined('CONTEXT_COMPANY')) {
            define('CONTEXT_COMPANY', 13);
        }

        // Define our custom contexts.
        $customcontextclasses = [
            CONTEXT_COMPANY => 'local_iomad\\custom_context\\context_company',
        ];

        // Are there already some defined in CFG?
        if (isset($CFG->custom_context_classes)) {
            $CFG->custom_context_classes = $CFG->custom_context_classes + $customcontextclasses;
        } else {
            $CFG->custom_context_classes = $customcontextclasses;
        }
    }
}
