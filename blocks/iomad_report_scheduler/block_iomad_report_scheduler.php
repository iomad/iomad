<?php

class block_iomad_report_scheduler extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_iomad_report_scheduler');
    }

    function hide_header() {
        return true;
    }

    function applicable_formats() {
        return array('site' => true);
    }

    function get_content() {
        global $USER, $CFG, $DB, $OUTPUT;

        // Only display if you have the correct capability
        if (!has_capability('block/iomad_report_scheduler:view', get_context_instance(CONTEXT_SYSTEM))) {
            return;
        }
        
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '<div id="iomad_report_scheduler" style="width: 100%; height: 200px; position:relative; 
                                margin: 0 auto; overflow: hidden">';
        $this->content->text .= '<div id="iomad_report_scheduler_main1" style="width: 100%; height: 200px; position:relative; 
                                margin: 0 auto; ">
                                <h3>Iomad Report Scheduler</h3></br>';
        $this->content->text .= '<a id="ELDMSRS" href="'.
                                    new moodle_url('/blocks/iomad_report_scheduler/reports_view.php'). '"><img src="'.
                                    new moodle_url('/blocks/iomad_report_scheduler/images/report.png') .
                                    '"></br>View Reports</a>';


        return $this->content;
    }
}


