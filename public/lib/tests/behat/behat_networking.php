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
 * Networking steps definitions
 *
 * @package    core
 * @category   test
 * @copyright  2025 Sam Smucker <sam.smucker@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../behat/behat_base.php');

/**
 * Steps definitions to trigger Javascript events in the network AMD module.
 *
 * @package    core
 * @category   test
 * @copyright  2025 Sam Smucker <sam.smucker@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_networking extends behat_base {
    /**
     * Trigger a custom Javascript event indicating network instability.
     *
     * @When my network is unstable
     */
    public function my_network_is_unstable() {
        $this->getSession()
            ->getDriver()
            ->evaluateScript("window.dispatchEvent(new CustomEvent('network:unstableconnection'));");
    }

    /**
     * Trigger a custom Javascript event indicating that the session has been touched.
     *
     * @When the session is touched
     */
    public function the_session_is_touched() {
        $this->getSession()
            ->getDriver()
            ->evaluateScript("window.dispatchEvent(new CustomEvent('network:sessiontouched'));");
    }
}
