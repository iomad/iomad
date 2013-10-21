<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once ("lib.php");

header('Content-type: text/css');

$css = file_get_contents("style/company.css");

echo iomad_process_company_css($css,null);
