<?php

namespace local_academy_events\event;

defined('MOODLE_INTERNAL') || die();

class companymanager_unassigned extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'role';
    }

    public static function get_name() {
        return get_string('eventcompanymanagerunassigned', 'local_academy_events');
    }

    public function get_description() {
        $username = isset($this->other['username']) ? $this->other['username'] : 'user';
        $email = isset($this->other['email']) ? " (email: {$this->other['email']})" : '';
        $deletion = isset($this->other['is_deletion']) && $this->other['is_deletion'] ? ' due to user deletion' : '';

        return "The user with id '{$this->userid}' unassigned the company manager role (id '{$this->objectid}') from {$username}{$email} (id '{$this->relateduserid}'){$deletion}.";
    }

    public function get_url() {
        return new \moodle_url('/user/view.php', ['id' => $this->relateduserid]);
    }
}
