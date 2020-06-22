<?php

defined('MOODLE_INTERNAL') || die();

class htm_quicklinks implements renderable {

    /* Name of the slider */
    public $name;

    /* Type of slide transition, slide or fade */
    public $type;

    /* Array of htm_slide objects */
    public $items = [];

    /* Number of slides */
    public $count = 0;

    public $status;

    public $bgcolor;
    

    public function __construct($name = null, $type = 1) {
        if ($name) {
            $this->name = $name;
        } else {
            print_error('noslidename', 'theme_iomadarmm');
        }
        if (is_numeric($type)) {
            $this->type = $type;
        }
    }

    public function add_status($status = false) {
        if ($status) {
            $this->status = $status;
        }
    }

    public function add_item($item) {
        $this->count++;
        array_push($this->items, $item);
    }

    public function set_type($type) {
        if(!empty($type)) {
            $this->type = $type;
        } else {
            // TODO: print error here
        }
    }

    public function get_type() {
        return $this->type;
    }

}

class htm_quicklink_item {
    public $i;
    public $itemid;
    public $data;

    public function __construct($data = false, $i) {
        if ($data) {
            $this->data = $data;
        } else {
            //print_error('noslidename', 'theme_boilerplate');
        }
        $this->itemid = $i;
        $this->i = ($this->itemid - 1);
        if ($this->itemid == 1) {
            $this->first = true;
        }
    }

}