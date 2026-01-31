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
 * Main plugin class
 *
 * @package   local_iomad_oidc_sync
 * @copyright 2024 Derick Turner
 * @author    Derick Turner
 * Based on code provided by Jacob Kindle @ Cofense https://cofense.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad_oidc_sync;

use core\event\user_updated;
use local_iomad\{company, company_user, iomad, observer};

/**
 * Class definition
 */
class oidc_sync {

    /**
     * Function which runs the sync for all configured companies, getting the users
     * from Microsoft and creating them in the company if they do not exist.
     *
     **/
    public static function run_sync() {
        global $DB;

        // Disable the signup handler to prevent it from interfering with company assignments.
        // OIDC sync handles company assignment explicitly via company_user::create().
        observer::$disable_handler = true;

        try {
            // Get the list of configured companies.
            mtrace("getting the list of configured companies");
            $oidccompanies = self::get_oidc_companies();

            // Process them.
            foreach ($oidccompanies as $company) {
                mtrace("Processessing company $company->name ($company->id)");
                $postfix = "_" . $company->id;

                // Get the company config.
                $clientid = get_config('auth_iomadoidc', 'clientid' . $postfix);
                $tenantid = $company->tenantnameorguid;
                $clientsecret = get_config('auth_iomadoidc', 'clientsecret' . $postfix);

                // Is it all configured?
                if (!empty($clientid) && !empty($tenantid) && !empty($clientsecret)) {
                    // We are good to go!
                    // Get the accesstoken.
                    if ($accesstoken = self::get_accesstoken($tenantid, $clientid, $clientsecret)) {
                        // So far so good - get the users.
                        $users = self::get_users($accesstoken, $company->syncgroupid);

                        // Do we have a list of email domains?
                        $companydomains = $DB->get_records_menu('company_domains', ['companyid' => $company->id], '', 'id,domain');

                        if (!empty($users)) {
                            // Process them.
                            self::process_users($company->id,
                                                $users,
                                                $company->useroption,
                                                $company->unsuspendonsync,
                                                $companydomains);
                        }
                    } else {
                        mtrace("Failed getting the access token for companyID " . $company->id);
                        $DB->set_field('local_iomad_oidc_sync', 'approved', 0, ['companyid' => $company->id]);
                        $DB->set_field('local_iomad_oidc_sync', 'enabled', 0, ['companyid' => $company->id]);
                    }
                } else {
                    mtrace("Company is not fully configured");
                }
            }
        } finally {
            // Always re-enable the signup handler, even if an exception occurs.
            observer::$disable_handler = false;
        }
    }

    /**
     * Function which gets the list of all configured companies
     *
     * Returns (array)
     **/
    private static function get_oidc_companies() {
        global $DB;

        // Set up the SQL.
        $selectsql = "c.*, lios.useroption, lios.tenantnameorguid, lios.syncgroupid, lios.unsuspendonsync";
        $fromsql = "{company} c JOIN {config_plugins} cp JOIN {local_iomad_oidc_sync} lios ON (c.id = lios.companyid)";
        $wheresql = "lios.approved = 1
                     AND lios.enabled = 1
                     AND cp.plugin = 'auth_iomadoidc'
                     AND cp.name = CONCAT('clientid_', c.id)
                     AND cp.value !=''";

        // Get the records.
        $companies = $DB->get_records_sql("SELECT $selectsql FROM $fromsql WHERE $wheresql");

        return $companies;
    }

    /**
     * Function which processess all of the users, checks if they already exist
     * and, if not, creates them an assigns them to the company.
     *
     **/
    private static function process_users($companyid, $users, $useroption, $unsuspendonsync, $companydomains) {
        global $DB, $CFG;

        $postfix = "_$companyid";
        $authplugin = get_auth_plugin('iomadoidc');
        $userfields = $authplugin->userfields;

        // Get all of the profile field categories.
        $profilecategories = iomad::iomad_filter_profile_categories($DB->get_records('user_info_category'), 0, $companyid);
        $customfields = [];
        if (!empty($profilecategories)) {
            $customfields = $DB->get_records_sql_menu("SELECT id,concat('profile_field_',shortname)
                                                  FROM {user_info_field}
                                                  WHERE categoryid IN (" . implode(',', array_keys($profilecategories)) . ")");
            $customfields = array_values($customfields);
        }
        if (!empty($customfields)) {
            $userfields = array_merge($userfields, $customfields);
        }
        $companyiomadoidcdata = get_config('auth_iomadoidc');
        $mappedfields = [];
        foreach ($userfields as $field) {
            $fieldname = "field_map_{$field}{$postfix}";
            if ($field == 'firstname' ||
                $field == 'lastname' ||
                $field == 'email') {
                continue;
            }
            if (!empty($companyiomadoidcdata->$fieldname)) {
                $mappedfields[$field] = $companyiomadoidcdata->$fieldname;
            }
        }

        // Get the mappings for the client.
        $firstnamename = !empty(get_config('auth_iomadoidc', 'field_map_firstname' . $postfix)) ?
                                get_config('auth_iomadoidc', 'field_map_firstname' . $postfix) :
                                get_config('auth_iomadoidc', 'field_map_firstname');
        $lastnamename = !empty(get_config('auth_iomadoidc', 'field_map_lastname' . $postfix)) ?
                               get_config('auth_iomadoidc', 'field_map_lastname' . $postfix) :
                               get_config('auth_iomadoidc', 'field_map_lastname');
        $emailname = !empty(get_config('auth_iomadoidc', 'field_map_email' . $postfix)) ?
                            get_config('auth_iomadoidc', 'field_map_email' . $postfix) :
                            get_config('auth_iomadoidc', 'field_map_email');

        // Start the list of users we are keeping, if needed later.
        $foundusers = [];

        mtrace("Processing " . count($users) . " users from OIDC connection");

        // Need to set up the company so we can check it's ok to add new users.
        $company = new company($companyid);
        $hitlimit = false;

        // Process the users.
        foreach ($users as $aduser) {
            if ($CFG->debug > DEBUG_NONE) {
                mtrace("Dealing with passed data " . print_r($aduser, true));
            }

            // Are we restricting by email domain?
            if (!empty($companydomains)) {
                // Default set to fail.
                $domainok = false;

                // Check if the mail has the domain at the end.
                foreach ($companydomains as $companydomain) {
                    if (str_ends_with(strtolower($aduser['mail']), '@' . strtolower($companydomain))) {
                        // It's a company domain.
                        $domainok = true;
                    }
                }

                // Did it match any of them?
                if (!$domainok) {
                    if ($CFG->debug > DEBUG_NONE) {
                        mtrace("Not a company user - skipping");
                    }
                    continue;
                }
            }

            // Process the user.
            $userrec = (object) [];
            $userrec->username = strtolower($aduser['userPrincipalName']);

            // Only want to add new users.
            if (!$founduser = $DB->get_record('user', (array) $userrec)) {
                if (!$company->check_usercount(1)) {
                    $hitlimit = true;
                    continue;
                }
                if ($CFG->debug > DEBUG_NONE) {
                    mtrace("Creating user $userrec->username");
                }
                $userrec->auth = 'iomadoidc';
                $userrec->companyid = $companyid;
                $userrec->firstname = $aduser[$firstnamename];
                $userrec->lastname = $aduser[$lastnamename];
                $userrec->email = $aduser[$emailname];
                $userrec->sendnewpasswordemails = false;

                // Check the information is valid.
                if (empty($userrec->email) ||
                    empty($userrec->firstname) ||
                    empty($userrec->lastname) ||
                    empty($userrec->username) ||
                    !validate_email($userrec->email)) {
                    continue;
                }

                // Create the user.
                if ($CFG->debug > DEBUG_NONE) {
                    mtrace("Adding as a new user");
                }
                if (!$userid = company_user::create($userrec, $companyid)) {
                    mtrace("failed to create user " . $userrec->username);
                    continue;
                }
                $userrec->id = $userid;

                // Save custom profile fields data and fire the creation.
                foreach ($mappedfields as $profilefield => $mapping) {
                    if ($CFG->debug > DEBUG_NONE) {
                        mtrace("Checking mapping $mapping");
                    }
                    // Is this a manager field?
                    if (str_starts_with($mapping, 'manager')) {
                        // Need to get the value from the manager sub-array.
                        [$first, $second] = explode('.', $mapping);
                        if (!empty($aduser[$first][$second])) {
                            if ($CFG->debug > DEBUG_NONE) {
                                mtrace("Setting profile field $profilefield to " . $aduser[$first][$second]);
                            }
                            $userrec->$profilefield = $aduser[$first][$second];
                        }
                    } else if (!empty($aduser[$mapping])) {
                            if ($CFG->debug > DEBUG_NONE) {
                                mtrace("Setting profile field $profilefield to " . $aduser[$mapping]);
                            }
                        $userrec->$profilefield = $aduser[$mapping];
                    }
                }

                user_update_user($userrec, false, false);
                profile_save_data($userrec);
                user_updated::create_from_userid($userid)->trigger();

                // Store this for later.
                $foundusers[] = $userid;
            } else {
                if ($founduser->suspended == 1 && $unsuspendonsync) {
                    // We want to unsuspend them.
                    company_user::unsuspend($founduser->id, $companyid);
                }

                // Sync the profile data.
                foreach ($mappedfields as $profilefield => $mapping) {
                    if (str_starts_with($mapping, 'manager')) {
                        // Need to get the value from the manager sub-array.
                        [$first, $second] = explode('.', $mapping);
                        if (!empty($aduser[$first][$second])) {
                            $founduser->$profilefield = $aduser[$first][$second];
                        }
                    } else if (!empty($aduser[$mapping])) {
                        $founduser->$profilefield = $aduser[$mapping];
                    }
                }

                user_update_user($founduser, false, false);
                profile_save_data($founduser);

                // Store this for later.
                $foundusers[] = $founduser->id;
            }
        }

        // Did we hit the maximum?
        if ($hitlimit) {
            mtrace("No more users added due to reaching allowed maximum for the company");
        }

        // Are we doing anything else?
        if (!empty($useroption) && !empty($foundusers)) {

            // Are we suspending or deleting?
            $suspendsql = "";
            if ($useroption == 1) {
                // Only want users who are not suspended.
                $suspendsql = "AND u.suspended = 0";
            }

            // Find if there are any users in the company where auth is iomadoidc and are not in this list.
            $missingselect = "SELECT u.id FROM {user} u
                              JOIN {company_users} cu ON (u.id = cu.userid)
                              WHERE u.deleted = 0
                              $suspendsql
                              AND u.auth = :authtype
                              AND cu.companyid = :companyid
                              AND u.id NOT IN (" . implode(',', $foundusers) . ")";
            $missingparams = ['companyid' => $companyid,
                              'authtype' => 'iomadoidc'];

            // Do we have anyone to process?
            if ($missingusers = $DB->get_records_sql($missingselect, $missingparams)) {
                if ($useroption == 1) {
                    mtrace("Suspending " . count($missingusers) . " accounts which no longer exist");
                    foreach ($missingusers as $missinguser) {
                        if ($CFG->debug > DEBUG_NONE) {
                            mtrace("Suspending userid $missinguser->id from companyid $companyid");
                        }
                        company_user::suspend($missinguser->id, $companyid);

                    }
                } else if ($useroption == 2) {
                    mtrace("Deleting " . count($missingusers) . " accounts which no longer exist");
                    foreach ($missingusers as $missinguser) {
                        if ($CFG->debug > DEBUG_NONE) {
                            mtrace("Deleting userid $missinguser->id from companyid $companyid");
                        }
                        company_user::delete($missinguser->id, $companyid);
                    }
                }
            }
        }
    }

    /**
     * Function which gets the access token from Microsoft for the company
     * given the company OIDC connection settings.
     *
     **/
    private static function get_accesstoken($tenantid, $clientid, $clientsecret) {
        // Using .default to request the static list of permissions defined in the app registration.
        $scope = 'https://graph.microsoft.com/.default';
        $tokenurl = "https://login.microsoftonline.com/" . $tenantid . "/oauth2/v2.0/token";

        // Prepare the POST fields.
        $fields = [
            'client_id' => $clientid,
            'scope' => $scope,
            'client_secret' => $clientsecret,
            'grant_type' => 'client_credentials', // Indicates the Client Credentials flow.
        ];

        // Initialize cURL session.
        $curl = curl_init();

        // Set cURL options.
        curl_setopt_array($curl, [
            CURLOPT_URL => $tokenurl,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields, '', '&'),
            CURLOPT_VERBOSE => true,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        // Execute the cURL session and capture the response.
        $response = curl_exec($curl);
        $error = curl_error($curl);

        // Close cURL session.
        curl_close($curl);

        // Check for errors or process the response.
        if ($error) {
            mtrace("error getting access token");
        } else {
            // Decode the response.
            $responsearray = json_decode($response, true);
            if (!empty($responsearray['access_token'])) {
                return $responsearray['access_token'];
            } else {
                mtrace("no access token was returned");
                return false;
            }
        }
    }

    /**
     * Function which gets the full list of users from Microsoft using
     * the accesstoken previously created.
     *
     **/
    private static function get_users($accesstoken, $syncgroupid = "") {

        $userlist = [];

        // Get the correct URL for the Microsoft Graph API call to list users.
        if (empty($syncgroupid)) {
            $graphurl = 'https://graph.microsoft.com/v1.0/users?$expand=manager&$top=500';
        } else {
            $graphurl = 'https://graph.microsoft.com/v1.0/groups/' . $syncgroupid . '/members?$expand=manager&$top=500';
        }

        // Setup the HTTP headers.
        $headers = [
            "Authorization: Bearer $accesstoken",
            "Content-Type: application/json",
            "ConsistencyLevel: eventual",
        ];

        $process = true;
        while ($process) {
            // Initialize a cURL session.
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $graphurl,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
            ]);

            // Execute the cURL session and capture the response.
            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                mtrace("Error getting users code - $error");
                $process = false;
            } else {
                // Decode the response.
                $responsearray = json_decode($response, true);
                if (isset($responsearray['error'])) {
                    mtrace("Response error - " . $responsearray['error']['code'] . ": " . $responsearray['error']['message']);
                    $process = false;
                } else if (isset($responsearray['value'])) {
                    $userlist = array_merge(array_values($userlist), array_values($responsearray['value']));
                    if (isset($responsearray['@odata.nextLink'])) {
                        $graphurl = $responsearray['@odata.nextLink'];
                    } else {
                        $process = false;
                    }
                } else {
                    $process = false;
                }
            }
        }

        return $userlist;
    }
}
