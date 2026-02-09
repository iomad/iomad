<?php

namespace local_companymanager_events\event;

defined('MOODLE_INTERNAL') || die();

class companymanager_assigned extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'role';
    }

    public static function get_name() {
        return get_string('eventcompanymanagerassigned', 'local_companymanager_events');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' assigned the company manager role (id '{$this->objectid}') to the user with id '{$this->relateduserid}' in the context with id '{$this->contextid}'.";
    }

    public function get_url() {
        return new \moodle_url('/user/view.php', ['id' => $this->relateduserid]);
    }
}
