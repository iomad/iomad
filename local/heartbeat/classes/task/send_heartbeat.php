<?php
namespace local_heartbeat\task;

defined('MOODLE_INTERNAL') || die();

class send_heartbeat extends \core\task\scheduled_task {
    public function get_name() {
        return 'Send heartbeat to BetterStack';
    }

    public function execute() {
        $url = get_config('local_heartbeat', 'heartbeat_url');
        if (empty($url)) {
            mtrace('Heartbeat URL not configured');
            return false;
        }
        $curl = new \curl();
        $curl->get($url);
        return true;
    }
}
