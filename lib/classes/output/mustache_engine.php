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
 * Custom Moodle engine for mustache.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

/**
 * Custom Moodle engine for mustache.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_engine extends \Mustache_Engine {
    /**
     * @var mustache_helper_collection
     */
    private $helpers;

    /**
     * @var string[] Names of helpers that aren't allowed to be called within other helpers.
     */
    private $blacklistednestedhelpers = [];

    /**
     * Mustache engine constructor.
     *
     * This provides an additional option to the parent \Mustache_Engine implementation:
     * $options = [
     *      // A list of helpers (by name) to prevent from executing within the rendering
     *      // of other helpers.
     *      'blacklistednestedhelpers' => ['js']
     * ];
     * @param array $options [description]
     */
    public function __construct(array $options = []) {
        if (isset($options['blacklistednestedhelpers'])) {
            $this->blacklistednestedhelpers = $options['blacklistednestedhelpers'];
        }

        parent::__construct($options);
    }

    /**
     * Get the current set of Mustache helpers.
     *
     * @see Mustache_Engine::setHelpers
     *
     * @return \Mustache_HelperCollection
     */
    public function getHelpers()
    {
        if (!isset($this->helpers)) {
            $this->helpers = new mustache_helper_collection(null, $this->blacklistednestedhelpers);
        }

        return $this->helpers;
    }
}
