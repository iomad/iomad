<?php

defined('MOODLE_INTERNAL') || die();

class htm_slider implements renderable {

    /* Name of the slider */
    public $name;

    /* Amount of time between each slide */
    public $interval = null;

    /* If arrows should be displayed */
    public $showcontrols = false;

    /* If dots should be displayed */
    public $showpager = false;

    /* Type of slide transition, slide or fade */
    public $type = 'slide';

    /* Array of htm_slide objects */
    public $slides = [];

    /* Number of slides */
    public $count = 0;

    public function __construct($name = null) {
        if ($name) {
            $this->name = $name;
        } else {
            print_error('noslidename', 'theme_boilerplate');
        }
    }

    public function add_status($status = false) {
        if ($status) {
            $this->status = $status;
        }
    }
    
    public function add_setting($property = null, $value) {
        if (!is_null($property)) {
            switch ($property) {
                case 'transition':
                    $this->type = $value;
                    break;
                case 'interval':
                    $this->interval = $value;
                    break;
                case 'controls':
                    $this->showcontrols = $value;
                    break;
                case 'pager':
                    $this->showpager = $value;
            }
        } else {
            //TODO:
            //print error here
        }
    }

    public function add_slide($slide) {
        if ($slide->data->hasimg) {
            $this->count++;
        }
        array_push($this->slides, $slide);
    }

}

class htm_slide {
    public $i;
    public $first;
    public $slideid;
    public $data;

    public function __construct($data = false, $i) {
        if ($data) {
            $this->data = $data;
        } else {
            //print_error('noslidename', 'theme_boilerplate');
        }
        $this->slideid = $i;
        $this->i = ($this->slideid - 1);
        if ($this->slideid == 1) {
            $this->first = true;
        }
    }

}