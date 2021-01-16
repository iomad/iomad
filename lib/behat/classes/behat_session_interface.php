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
 * The Interface for a behat root context.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The Interface for a behat root context.
 *
 * This interface should be implemented by the behat_base context, and behat form fields, and it should be paired with
 * the behat_session_trait.
 *
 * It should not be necessary to implement this interface, and the behat_session_trait trait in normal circumstances.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface behat_session_interface {
    /**
     * Small timeout.
     *
     * A reduced timeout for cases where self::TIMEOUT is too much
     * and a simple $this->getSession()->getPage()->find() could not
     * be enough.
     *
     * @deprecated since Moodle 3.7 MDL-64979 - please use get_reduced_timeout() instead
     * @todo MDL-64982 This will be deleted in Moodle 3.11
     * @see behat_base::get_reduced_timeout()
     */
    const REDUCED_TIMEOUT = 2;

    /**
     * The timeout for each Behat step (load page, wait for an element to load...).
     *
     * @deprecated since Moodle 3.7 MDL-64979 - please use get_timeout() instead
     * @todo MDL-64982 This will be deleted in Moodle 3.11
     * @see behat_base::get_timeout()
     */
    const TIMEOUT = 6;

    /**
     * And extended timeout for specific cases.
     *
     * @deprecated since Moodle 3.7 MDL-64979 - please use get_extended_timeout() instead
     * @todo MDL-64982 This will be deleted in Moodle 3.11
     * @see behat_base::get_extended_timeout()
     */
    const EXTENDED_TIMEOUT = 10;

    /**
     * The JS code to check that the page is ready.
     *
     * The document must be complete and either M.util.pending_js must be empty, or it must not be defined at all.
     */
    const PAGE_READY_JS = "document.readyState === 'complete' && " .
        "(typeof M !== 'object' || typeof M.util !== 'object' || " .
        "typeof M.util.pending_js === 'undefined' || M.util.pending_js.length === 0)";

    /**
     * Returns the Mink session.
     *
     * @param   string|null $name name of the session OR active session will be used
     * @return  \Behat\Mink\Session
     */
    public function getSession($name = null);
}
