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
 * Condition main class.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_language;

/**
 * Condition main class.
 *
 * @package   availability_language
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string ID of language that this condition requires, or '' = any language */
    protected $languageid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get language id.
        if (!property_exists($structure, 'id')) {
            $this->languageid = '';
        } else if (is_string($structure->id)) {
            $this->languageid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for language condition');
        }
    }

    /**
     * Saves data back to a structure object.
     *
     * @return \stdClass Structure object
     */
    public function save() {
        $result = (object)['type' => 'language'];
        if ($this->languageid) {
            $result->id = $this->languageid;
        }
        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param string $languageid Not required language
     * @return stdClass Object representing condition
     */
    public static function get_json($languageid = '') {
        return (object)['type' => 'language', 'id' => $languageid];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $CFG, $USER;

        // If course has forced language.
        $course = $info->get_course();
        $allow = false;
        if (isset($course->lang) && $course->lang === $this->languageid) {
            $allow = true;
        } else {
            if ($userid === $USER->id) {
                // Checking the language of the currently logged in user, so do not
                // default to the account language, because the session language
                // or the language of the current course may be different.
                $language = current_language();
            } else {
                if (is_null($userid)) {
                    // Fall back to site language or English.
                    $language = $CFG->lang;
                } else {
                    // Checking access for someone else than the logged in user, so
                    // use the preferred language of that user account.
                    // This language is never empty as there is a not-null constraint.
                    $language = \core_user::get_user($userid)->lang;
                }
            }
            if ($language === $this->languageid) {
                $allow = true;
            }
        }
        if ($not) {
            return !($allow);
        }
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        if ($this->languageid != '') {
            $installedlangs = get_string_manager()->get_list_of_translations();
            if (array_key_exists($this->languageid, $installedlangs)) {
                $snot = $not ? 'not' : '';
                return get_string('getdescription' . $snot, 'availability_language', $installedlangs[$this->languageid]);
            }
        }
        return '';
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return $this->languageid ?? 'any';
    }
}
