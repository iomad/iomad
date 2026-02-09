<?php

namespace local_academy_events\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Company manager assigned event.
 *
 * @package    local_academy_events
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class companymanager_assigned extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'role';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcompanymanagerassigned', 'local_academy_events');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' assigned the company manager role (id '{$this->objectid}') to the user with id '{$this->relateduserid}' in the context with id '{$this->contextid}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/user/view.php', ['id' => $this->relateduserid]);
    }
}
