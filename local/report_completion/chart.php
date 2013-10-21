<?php

// this cannot call Moodle's config.php
// which makes things interesting

require_once('lib.php' );

// get the data
$data = new stdClass;
$data->notstarted = $_GET['notstarted'];
$data->inprogress = $_GET['inprogress'];
$data->completed = $_GET['completed'];

comprep::drawChart($data);
