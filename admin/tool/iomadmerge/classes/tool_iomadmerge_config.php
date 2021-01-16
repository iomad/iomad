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
 * @package tool
 * @subpackage iomadmerge
 * @author Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 Servei de Recursos Educatius (http://www.sre.urv.cat)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Wrapper class for the configuration settings of the merge user utility.
 *
 * This class loads the standard settings from the file:
 * <code>lib/config.php</code> and then loads the local settings from the file:
 * <code>lib/config.local.php</code> if it exists. Note that local settings overwrite
 * default settings.
 *
 * These files must have a content similar to this one:
 * <pre>
 * return array(
 *     'gathering' => 'ClassName',
 *     'exceptions' => array('tablename1', 'tablename2'), //table names without $CFG->prefix
 *     'compoundindexes' => array( //table names without $CFG->prefix
 *         'tablename' => array(
 *             'userfield' => 'user-related_fieldname_on_tablename',
 *             'otherfield' => 'other_fieldname_on_tablename',
 *             ['both' => true,]
 *         ),
 *     ),
 *     'userfieldnames' => array( //table names without $CFG->prefix
 *         'tablename' => array('user-realted-fieldname1', 'user-related-fieldname2'),
 *     ),
 * );
 * </pre>
 *
 * If the key 'both' appears, means that both columns are user-related and must be searched for
 * both. See the README.txt for more details on special cases.
 */
class tool_iomadmerge_config {
    /**
     * @var tool_iomadmerge_config singleton instance.
     */
    private static $instance = null;

    /**
     * @var array settings
     */
    private $config;

    /**
     * Private constructor for the singleton.
     */
    private function __construct() {
        $config = include dirname(__DIR__) . '/config/config.php';

        if (file_exists(dirname(__DIR__) . '/config/config.local.php')) {
            $localconfig = include dirname(__DIR__) . '/config/config.local.php';
            $config = array_replace_recursive($config, $localconfig);
        }
        $this->config = $config;
    }

    /**
     * Singleton method.
     * @return tool_iomadmerge_config singleton instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new tool_iomadmerge_config();
        }
        return self::$instance;
    }

    /**
     * Accessor to properties from the current config as attributes of an standard object.
     * @param string $name name of attribute; by now only:
     * 'gathering', 'exceptions', 'compoundindexes', 'userfieldnames'.
     * @return mixed null if $name is not a valid property name of the current configuration;
     * string or array having the value of the $name property.
     */
    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }
}
