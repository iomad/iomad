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

/*
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;
use moodle_url;

/**
 * Class to handle SCIM User response.
 */
class scimuserresponse extends scimresponse {

    /**
     * User object
     * @user object
     */
    private $user;
    /**
     * User active status
     * @active bool
     */
    private $active = null;
    /**
     * Append user schema info.
     * @appendschemainfo bool
     */
    private $appendschemainfo = false;

    /**
     * Returns SCIM User response constructor.
     *
     * @param object $userobject User object
     * @param bool $active User status
     * @param bool $appendschemainfo Append User schema info.
     */
    public function __construct(object $userobject, bool $active, bool $appendschemainfo) {
        $this->set_response_type('User');
        $this->user = $userobject;
        $this->active = $active;
        $this->appendschemainfo = $appendschemainfo;
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data() : array {

        $manageridnumber = '';
        $manager = '';
        $refurl = '';
        if ($this->user->managerid) {
            $manageridnumber = $this->user->manageridnumber;
            $manager = $this->user->managerfirstname . ' ' . $this->user->managerlastname;
            $refurl = (new moodle_url('/user/profile.php', array('id' => $this->user->managerid)))->out();
        }
        $returnvar = array(
            'id' => $this->user->idnumber,
            'externalId' => $this->user->idnumber,
            'userName' => $this->user->username,
            'displayName' => $this->user->alternatename,
            'name' => array (
                'givenName' => $this->user->firstname,
                'familyName' => $this->user->lastname
            ),
            'emails' => array(
                            array(
                                'value' => $this->user->email,
                                'type' => 'work',
                                'primary' => true
                            )
                        ),
            'preferredLanguage' => $this->user->lang,
            'addresses' => array(
                                array(
                                    'locality' => $this->user->city,
                                    'country' => $this->user->country,
                                    'type' => 'work',
                                    'primary' => true
                                )
                            ),
            'title' => $this->user->title,
            'department' => $this->user->department,
            'active' => $this->active,
            static::SCIM2_ENTERPRISE_USER_EXT => array(
                                                    'manager' => array(
                                                                    'value' => $manageridnumber,
                                                                    '$ref' => $refurl,
                                                                    'displayName' => $manager
                                                                )
                                                ),
            static::SCIM2_CUSTOM_USER_URN => array(
                                                'auth' => $this->user->auth,
                                                'team' => $this->user->team
                                            ),
            'meta' => $this->get_meta()
        );

        if ($this->appendschemainfo) {
            $returnvar = array('schemas' => static::SCIM2_SCHEMAS) + $returnvar;
        }

        return $returnvar;
    }

    /**
     * Return metadata as part of this SCIM response.
     *
     * @return array
     */
    protected function get_meta() : array {
        global $CFG;
        return array(
            'resourceType' => 'User',
            'created' => gmdate("Y-m-d\TH:i:s\Z", $this->user->timecreated),
            'lastModified' => gmdate("Y-m-d\TH:i:s\Z", $this->user->timemodified),
            'location' => $CFG->wwwroot . '/user/view.php?id=' . $this->user->id,
            'version' => static::SCIM2_VERSION
        );
    }
}
