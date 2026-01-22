<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block IOMAD eCommerce class definition
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_commerce {

    /**
     * Update remote company handler
     *
     * @param object $company
     * @param object $oldcompany
     * @return void
     */
    public static function update_company($company, $oldcompany) {

        $call = 'updateCompany';
        $payload = [
            'origname' => $oldcompany->name,
            'newname' => $company->name,
        ];

        return self::docall($call, $payload, $company->id);
    }

    /**
     * Update remote user handler
     *
     * @param object $user
     * @param id $companyid
     * @return void
     */
    public static function update_user($user, $companyid) {

        $call = 'updateUser';
        if (empty($user->company)) {
            $user->company = 'Registered';
        }
        if (empty($user->manager)) {
            $user->manager = 0;
        }
        $payload = [
            'userid' => $user->id,
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'company' => $user->company,
            'password' => $user->password,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
            'manager' => $user->manager,
        ];

        if (!empty($user->extragroup->name) && !empty($user->extragroup->action)) {
            $payload['extragroup'] = $user->extragroup->name;
            $payload['extragroupaction'] = $user->extragroup->action;
        } else {
            $payload['extragroup'] = null;
            $payload['extragroupaction'] = null;
        }

        return self::docall($call, $payload, $companyid);
    }

    /**
     * Assign user to company remote handler
     *
     * @param object $user
     * @param string $companyname
     * @param integer $companyid
     * @return void
     */
    public static function assign_user($user, $companyname="", $companyid=0) {

        $call = 'updateUser';
        if (empty($user->manager)) {
            $user->manager = 'no';
        }
        if (empty($companyname) && !empty($user->company)) {
            $companyname = $user->company;
        }
        if (empty($companyname)) {
            $companyname = 'Registered';
        }
        $payload = [
            'userid' => $user->id,
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'company' => $companyname,
            'password' => $user->password,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
            'manager' => $user->manager,
        ];

        if (!empty($user->extragroup->name) && !empty($user->extragroup->action)) {
            $payload['extragroup'] = $user->extragroup->name;
            $payload['extragroupaction'] = $user->extragroup->action;
        }

        return self::docall($call, $payload, $companyid);
    }

    /**
     * Delete user handler for remote
     *
     * @param string $username
     * @param integer $companyid
     * @return void
     */
    public static function delete_user($username, $companyid) {

        $call = 'deleteUser';
        $payload = ['username' => $username];

        return self::docall($call, $payload, $companyid);
    }

    /**
     * Make the remote webservice call
     *
     * @param string $call
     * @param array $payload
     * @param int $companyid
     * @return void
     */
    private static function docall($call, $payload, $companyid) {
        global $CFG;

        $opts = [
            'http' => [
                'user_agent' => 'PHPSoapClient',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
        $soapcontext = stream_context_create($opts);

        $checkname = "commerce_externalshop_url_$companyid";
        if (!empty($CFG->$checkname)) {
            $mainurl = $CFG->$checkname;
        } else {
            $mainurl = $CFG->commerce_externalshop_url;
        }
        $wsdlurl = $mainurl . '/wp-content/plugins/wpiomadsoap/wsdl/wpiomadsoap.wsdl';
        $soapserverurl = $mainurl . '/?api=soap&version=v1&wsdl';

        $client = new SoapClient($wsdlurl, [
            'stream_context' => $soapcontext,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => 1,
        ]);

        try {
            $client->__setLocation($soapserverurl);
            $response = $client->__soapCall($call, $payload);
            return $response;
        } catch (SoapFault $e) {
            return $e->getMessage();
        }
        return $response;
    }
}
