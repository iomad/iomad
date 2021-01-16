<?php
// This file is NOT a part of Moodle - http://moodle.org/
//
// This client for Moodle 2 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//

/**
 * XML-RPC client for Moodle 2
 *
 * @authorr Jerome Mouneyrac
 */

require(dirname(__FILE__) . '/config.php');

$functionname = 'block_iomad_company_admin_assign_courses';



/// PARAMETERS
//$company1 = new stdClass;
//$company1->name = 'Sproatly Sprockets';
//$company1->shortname = 'sproatly';
//$company1->city = 'Glasgow';
//$company1->country = 'UK';

//$licenses->name = "lic-name";
//$licenses->allocation = 7;
//$licenses->validlength = 30;
//$licenses->expirydate = time()+ strtotime('+1 week');  
//$licenses->used = 0;
//$licenses->companyid = 12;
//$licenses->parentid = 0;
//$licenses->courses->courseid = 4;

$courses = [
                "courseid" => 2,                
                "companyid" => 11,                
                "owned" => 1,                
                "licensed" => 0,                
            ];

$params = array($courses);
var_dump($courses);    

// XML-RPC CALL
$serverurl = $domainname . '/webservice/xmlrpc/server.php'. '?wstoken=' . $token;
require_once('./curl.php');
$curl = new curl;
$post = xmlrpc_encode_request($functionname, array($params));
var_dump($post); die;
$resp = xmlrpc_decode($curl->post($serverurl, $post));
print_r($resp);
