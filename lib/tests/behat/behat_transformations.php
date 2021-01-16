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
 * Behat arguments transformations.
 *
 * This methods are used by Behat CLI command.
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Transformations to apply to steps arguments.
 *
 * This methods are applied to the steps arguments that matches
 * the regular expressions specified in the @Transform tag.
 *
 * @package   core
 * @category  test
 * @copyright 2013 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_transformations extends behat_base {

    /**
     * Transformations for TableNode arguments.
     *
     * Transformations applicable to TableNode arguments should also
     * be applied, adding them in a different method for Behat API restrictions.
     *
     * @deprecated since Moodle 3.2 MDL-56335 - please do not use this function any more.
     * @param TableNode $tablenode
     * @return TableNode The transformed table
     */
    public function prefixed_tablenode_transformations(TableNode $tablenode) {
        debugging('prefixed_tablenode_transformations() is deprecated. Please use tablenode_transformations() instead.',
            DEBUG_DEVELOPER);

        return $this->tablenode_transformations($tablenode);
    }

    /**
     * Removes escaped argument delimiters.
     *
     * We use double quotes as arguments delimiters and
     * to add the " as part of an argument we escape it
     * with a backslash, this method removes this backslash.
     *
     * @Transform /^((.*)"(.*))$/
     * @param string $string
     * @return string The string with the arguments fixed.
     */
    public function arg_replace_slashes($string) {
        if (!is_scalar($string)) {
            return $string;
        }
        return str_replace('\"', '"', $string);
    }

    /**
     * Replaces $NASTYSTRING vars for a nasty string.
     *
     * @Transform /^((.*)\$NASTYSTRING(\d)(.*))$/
     * @param string $argument The whole argument value.
     * @return string
     */
    public function arg_replace_nasty_strings($argument) {
        if (!is_scalar($argument)) {
            return $argument;
        }
        return $this->replace_nasty_strings($argument);
    }

    /**
     * Convert string time to timestamp.
     * Use ::time::STRING_TIME_TO_CONVERT::DATE_FORMAT::
     *
     * @Transform /^##(.*)##$/
     * @param string $time
     * @return int timestamp.
     */
    public function arg_time_to_string($time) {
        return $this->get_transformed_timestamp($time);
    }

    /**
     * Transformations for TableNode arguments.
     *
     * Transformations applicable to TableNode arguments should also
     * be applied, adding them in a different method for Behat API restrictions.
     *
     * @Transform table:*
     * @param TableNode $tablenode
     * @return TableNode The transformed table
     */
    public function tablenode_transformations(TableNode $tablenode) {
        // Walk through all values including the optional headers.
        $rows = $tablenode->getRows();
        foreach ($rows as $rowkey => $row) {
            foreach ($row as $colkey => $value) {

                // Transforms vars into nasty strings.
                if (preg_match('/\$NASTYSTRING(\d)/', $rows[$rowkey][$colkey])) {
                    $rows[$rowkey][$colkey] = $this->replace_nasty_strings($rows[$rowkey][$colkey]);
                }

                // Transform time.
                if (preg_match('/^##(.*)##$/', $rows[$rowkey][$colkey], $match)) {
                    if (isset($match[1])) {
                        $rows[$rowkey][$colkey] = $this->get_transformed_timestamp($match[1]);
                    }
                }
            }
        }

        // Return the transformed TableNode.
        unset($tablenode);
        $tablenode = new TableNode($rows);

        return $tablenode;
    }

    /**
     * Replaces $NASTYSTRING vars for a nasty string.
     *
     * Method reused by TableNode tranformation.
     *
     * @param string $string
     * @return string
     */
    public function replace_nasty_strings($string) {
        return preg_replace_callback(
            '/\$NASTYSTRING(\d)/',
            function ($matches) {
                return nasty_strings::get($matches[0]);
            },
            $string
        );
    }

    /**
     * Return timestamp for the time passed.
     *
     * @param string $time time to convert
     * @return string
     */
    protected function get_transformed_timestamp($time) {
        $timepassed = explode('##', $time);

        // If not a valid time string, then just return what was passed.
        if ((($timestamp = strtotime($timepassed[0])) === false)) {
            return $time;
        }

        $count = count($timepassed);
        if ($count === 2) {
            // If timestamp with spcified format, then retrun date.
            return date($timepassed[1], $timestamp);
        } else if ($count === 1) {
            return $timestamp;
        } else {
            // If not a valid time string, then just return what was passed.
            return $time;
        }
    }
}
