<?php

/**
 * 
 */

class local_iomad_settings extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'local_iomad_settings');

    }

    function hide_header() {
        return true;
    }
}
