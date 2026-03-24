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
 * IOMAD Dashboard edit users table class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\tables;

use action_menu_link_secondary;
use action_menu;
use block_iomad_company_admin\output\{user_departments_editable, user_roles_editable};
use core\output\notification;
use html_writer;
use local_iomad\{company, company_user, iomad};
use moodle_url;
use table_sql;

/**
 * IOMAD Dashboard edit users table class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editusers_table extends table_sql {

    /** @var array list of departments */
    protected $departments;

    /** @var array list of available departments */
    protected $assignabledepartments;

    /** @var array list of user roles */
    protected $usertypes;

    /**
     * Generate the display of the user's| fullname
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $companycontext;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));

        // Deal with suspended users.
        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            $return = html_writer::start_tag('span', ['class' => 'dimmed_text']);
        } else {
            $return = "";
        }

        // Can we see a link?
        if (has_capability('block/iomad_company_admin:editusers', $companycontext) ||
            has_capability('block/iomad_company_admin:editallusers', $companycontext)) {
            $profileurl = new moodle_url('/user/profile.php', ['id' => $row->id]);
            $return .= html_writer::tag('a', $name, ['href' => $profileurl]);
        } else {
            $return .= $name;
        }

        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            $return .= html_writer::end_tag('span');
        }

        return $return;
    }

    /**
     * Generate the display of the user's departments
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_department($row) {
        global $DB, $USER, $selectedcompanyid, $company, $OUTPUT, $companycontext;

        // Only show departments if they are in the current company.
        if ($company->id == $selectedcompanyid) {
            $userdepartments = array_keys($DB->get_records('local_iomad_company_users', ['companyid' => $company->id,
                                                                             'userid' => $row->id],
                                                                             '',
                                                                             'departmentid'));
            // Deal with suspended users.
            if (!empty($row->suspended) ||
                !empty($row->companysuspended)) {
                $return = html_writer::start_tag('span', ['class' => 'dimmed_text']);
            } else {
                $return = "";
            }

            if ($row->managertype == 1 || empty($USER->editing)) {

                // Format the company departments.
                $return .= company_user::get_department_name($row->id, $row->companyid, ',<br>', true);

                if (!empty($row->suspended) ||
                    !empty($row->companysuspended)) {
                    $return .= html_writer::end_tag('span');
                }
                return $return;

            } else {
                // If there are no departments available to the current user then return a empty string.
                if (empty($userdepartments)) {
                    // Deal with suspended users.
                    if (!empty($row->suspended) ||
                        !empty($row->companysuspended)) {
                        $return .= html_writer::end_tag('span');
                    }

                    return $return;
                }

                $editable = new user_departments_editable(
                    $company,
                    $companycontext,
                    $row,
                    $userdepartments,
                    $this->departments,
                    $this->assignabledepartments
                );

                $return .= $OUTPUT->render_from_template('core/inplace_editable', $editable->export_for_template($OUTPUT));

                // Deal with suspended users.
                if (!empty($row->suspended) ||
                    !empty($row->companysuspended)) {
                    $return .= html_writer::end_tag('span');
                }

                return $return;
            }
        } else {
            // Deal with suspended users.
            if (!empty($row->suspended) ||
                !empty($row->companysuspended)) {
                $return = html_writer::start_tag('span', ['class' => 'dimmed_text']);
            } else {
                $return = "";
            }

            // Format the company departments.
            $return .= company_user::get_department_name($row->id, $selectedcompanyid, ',<br>', true);

            if (!empty($row->suspended) ||
                !empty($row->companysuspended)) {
                $return .= html_writer::end_tag('span');
            }

            return $return;
        }
    }

    /**
     * Generate the display of the user's company roles
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_managertype($row) {
        global $DB, $USER, $selectedcompanyid, $company, $OUTPUT, $companycontext;

        // Deal with suspended users.
        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            $returnstr = html_writer::start_tag('span', ['class' => 'dimmed_text']);
        } else {
            $returnstr = "";
        }

        if (empty($USER->editing) || $selectedcompanyid != $company->id) {

            // Add the user type in too.
            $returnstr .= $this->usertypes[$row->managertype];
            if (!empty($row->educator) && empty(get_config('local_iomad', 'autoenrol_managers'))) {
                $returnstr .= ",<br>" . $this->usertypes[3];
            }

            // Deal with suspended users.
            if (!empty($row->suspended) ||
                !empty($row->companysuspended)) {
                $returnstr .= html_writer::end('span');
            }

            return $returnstr;
        } else {
            // Can't be a company manager if you are in more than one department
            // or the department you are in is not the top level department.
            $userdepartments = array_keys($DB->get_records('local_iomad_company_users', ['companyid' => $company->id,
                                                                             'userid' => $row->id],
                                                                             '',
                                                                             'departmentid'));
            $usertypeselect = $this->usertypeselect;
            if (count($userdepartments) > 1 ||
                isset($userdepartments[0]) && $userdepartments[0] != $this->parentlevel->id) {
                unset($usertypeselect[10]);
                unset($usertypeselect[11]);
            }

            // Set up the current value for the inplace form and display it.
            if (empty(get_config('local_iomad', 'autoenrol_managers'))) {
                $currentvalue = ($row->managertype * 10) + $row->educator;
                $iseducator = $DB->get_records('local_iomad_company_users', ['userid' => $row->id,
                                                                 'companyid' => $company->id,
                                                                 'educator' => 1]);
                $canassigneducators = iomad::has_capability('block/iomad_company_admin:assign_educator', $companycontext);
                if (!$iseducator && !$canassigneducators) {
                    unset($usertypeselect[1]);
                    unset($usertypeselect[11]);
                    unset($usertypeselect[21]);
                    unset($usertypeselect[41]);
                }
                if ($iseducator || $canassigneducators) {
                    if (!$canassigneducators) {
                        unset($usertypeselect[0]);
                    }
                }
                if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $companycontext) &&
                    ($iseducator || $canassigneducators)) {
                    if (!$canassigneducators) {
                        unset($usertypeselect[20]);
                    }
                }
                if (iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $companycontext) &&
                    ($iseducator || $canassigneducators)) {
                    if (!$canassigneducators) {
                        unset($usertypeselect[40]);
                    }
                }
            } else {
                $currentvalue = $row->managertype * 10;
            }
            // Added due to value mismatch when editing under certain circumstances.
            if (empty($currentvalue)) {
                $currentvalue = 0;
            }
            // If there are no departments for the current user then output their role as text.
            if (empty($userdepartments)) {
                $returnstr .= $usertypeselect[$currentvalue];

                // Deal with suspended users.
                if (!empty($row->suspended) ||
                    !empty($row->companysuspended)) {
                    $returnstr .= html_writer::end_tag('span');
                }

                return $returnstr;
            }

            $editable = new user_roles_editable(
                $company,
                $companycontext,
                $row,
                $currentvalue,
                $usertypeselect
            );

            $returnstr .= $OUTPUT->render_from_template(
                'core/inplace_editable',
                $editable->export_for_template($OUTPUT)
            );

            // Deal with suspended users.
            if (!empty($row->suspended) ||
                !empty($row->companysuspended)) {
                $returnstr .= html_writer::end_tag('span');
            }

            return $returnstr;
        }
    }

    /**
     * Generate the display of the user's email
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_email($row) {

        // Deal with suspended users.
        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            return html_writer::tag('span', $row->email, ['class' => 'dimmed_text']);
        } else {
            return $row->email;
        }
    }

    /**
     * Generate the display of the user's last access datetime
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_lastaccess($row) {

        // Set up the value.
        if (!empty($row->lastaccess)) {
            $return = userdate($row->lastaccess, get_config('local_iomad', 'date_format'));
        } else {
            $return = get_string('never');
        }

        // Deal with suspended users.
        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            return html_writer::tag('span', $return, ['class' => 'dimmed_text']);
        } else {
            return $return;
        }
    }

    /**
     * Generate the display of the user's company name
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_company($row) {

        // Deal with suspended users.
        if (!empty($row->suspended) ||
            !empty($row->companysuspended)) {
            return html_writer::tag('span', format_string($row->companyname), ['class' => 'dimmed_text']);
        } else {
            return format_string($row->companyname);
        }
    }

    /**
     * Generate the actions column
     * @param object $user the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_actions($row) {
        global $USER, $output, $companycontext, $DB, $companyid;

        // User actions.
        $actions = [];
        $ajaxurl = new moodle_url('#');

        if ($row->username == 'guest') {
            return; // Do not display dummy new user and guest here.
        }

        if (!empty($USER->editing)) {
            if ((iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)
                 || iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext))
                 || $row->id == $USER->id && !is_mnet_remote_user($row)) {
                if ($row->id != $USER->id &&
                    $DB->get_records_select('local_iomad_company_users',
                                            'companyid =:company AND managertype IN (1,2) AND userid = :userid',
                                            ['company' => $row->companyid, 'userid' => $row->id])
                    && !iomad::has_capability('block/iomad_company_admin:editmanagers', $companycontext)) {
                    $canedit = false;
                } else {
                    $url = new moodle_url('/blocks/iomad_company_admin/editadvanced.php', [
                        'id' => $row->id,
                    ]);
                    $actions['edit'] = new action_menu_link_secondary(
                        $url,
                        null,
                        get_string('edit')
                    );
                    if (iomad::has_capability('block/iomad_company_admin:edituserpassword', $companycontext)) {
                        $actions['password'] = new action_menu_link_secondary(
                            $ajaxurl,
                            null,
                            get_string('resetpassword', 'block_iomad_company_admin'),
                            [
                                'data-action' => 'show-resetuserprompt',
                                'data-username' => fullname(
                                    $row,
                                    has_capability('moodle/site:viewfullnames', $this->get_context())
                                ),
                                'data-userid' => $row->id,
                                'data-companyid' => $companyid,
                            ]
                        );
                    }
                }
            }

            if ($row->id != $USER->id) {
                if ((iomad::has_capability('block/iomad_company_admin:editusers', $companycontext)
                     || iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext))) {
                    if ($DB->get_records_select(
                        'local_iomad_company_users',
                        "companyid = :company
                         AND managertype <> 0
                         AND userid = :userid",
                        ['company' => $companyid,
                         'userid' => $row->id])
                    && !iomad::has_capability('block/iomad_company_admin:editmanagers', $companycontext)) {
                        $canedit = false;
                    } else {
                        if (iomad::has_capability('block/iomad_company_admin:deleteuser', $companycontext)) {
                            $actions['delete'] = new action_menu_link_secondary(
                                $ajaxurl,
                                null,
                                get_string('delete'),
                                [
                                    'data-action' => 'show-deleteuserprompt',
                                    'data-username' => fullname(
                                        $row,
                                        has_capability('moodle/site:viewfullnames', $this->get_context())
                                    ),
                                    'data-userid' => $row->id,
                                    'data-companyid' => $companyid,
                                ]
                            );
                        }
                        if (iomad::has_capability('block/iomad_company_admin:suspenduser', $companycontext)) {
                            if (!empty($row->suspended) ||
                                !empty($row->companysuspended)) {
                                $actionstring = get_string('unsuspend', 'block_iomad_company_admin');
                                $suspended = true;
                            } else {
                                $actionstring = get_string('suspend', 'block_iomad_company_admin');
                                $suspended = false;
                            }
                            $actions['unsuspend'] = new action_menu_link_secondary(
                                $ajaxurl,
                                null,
                                $actionstring,
                                [
                                    'data-action' => 'show-suspenduserprompt',
                                    'data-username' => fullname(
                                        $row,
                                        has_capability('moodle/site:viewfullnames', $this->get_context())
                                    ),
                                    'data-userid' => $row->id,
                                    'data-suspended' => $suspended,
                                    'data-companyid' => $companyid,
                                ]
                            );
                        }
                    }
                }
            }
        }

        if ((iomad::has_capability('block/iomad_company_admin:company_course_users', $companycontext)
             || iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext))
             && ($row->id == $USER->id || !is_siteadmin($row)
             && !is_mnet_remote_user($row))) {
            $url = new moodle_url('/blocks/iomad_company_admin/company_users_course_form.php', [
                'userid' => $row->id,
            ]);
            $actions['enrolment'] = new action_menu_link_secondary(
                $url,
                null,
                get_string('userenrolments', 'block_iomad_company_admin')
            );
        }

        if ((iomad::has_capability('block/iomad_company_admin:company_license_users', $companycontext)
             || iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext))
             && ($row->id == $USER->id || !is_siteadmin($row))
             && !is_mnet_remote_user($row)) {
            $url = new moodle_url('/blocks/iomad_company_admin/company_users_licenses_form.php', [
                'userid' => $row->id,
            ]);
            $actions['userlicense'] = new action_menu_link_secondary(
                $url,
                null,
                get_string('userlicenses', 'block_iomad_company_admin')
            );
        }

        if (iomad::has_capability('local/report_users:view', $companycontext)) {
            $url = new moodle_url('/local/report_users/userdisplay.php', [
                'userid' => $row->id,
            ]);
            $actions['userreport'] = new action_menu_link_secondary(
                $url,
                null,
                get_string('report_users_title', 'local_report_users')
            );
        }

        $menu = new action_menu();
        $menu->set_owner_selector('.iomad_editusers-actionmenu');
        $menu->set_menu_left();
        $menu->set_menu_trigger(get_string('usercontrols', 'block_iomad_company_admin'));
        foreach ($actions as $action) {
            $menu->add($action);
        }

        return $output->render($menu);
    }

    /**
     * Override print_nothing_to_display to ensure that column headers are always added.
     */
    public function print_nothing_to_display() {
        global $OUTPUT;

        $this->start_html();
        $this->print_headers();
        echo html_writer::end_tag('table');
        echo html_writer::end_tag('div');
        $this->wrap_html_finish();

        $notificationmsg = get_string('nousersfound', 'block_iomad_company_admin');
        $notificationtype = notification::NOTIFY_INFO;

        $notification = (new notification($notificationmsg, $notificationtype, false))
            ->set_extra_classes(['mt-3']);
        echo $OUTPUT->render($notification);

        echo $this->get_dynamic_table_html_end();
    }

    /** @var int company ID */
    protected $companyid;

    /** @var int parent department ID */
    protected $parentlevel;

    /** @var array list of departments */
    protected $departmentsmenu;

    /** @var array list of available roles */
    protected $usertypeselect;
    /**
     * Constructor
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct($uniqueid) {
        global $DB, $companyid, $companycontext;

        $this->uniqueid = $uniqueid;
        $this->request  = [
            TABLE_VAR_SORT   => 'tsort',
            TABLE_VAR_HIDE   => 'thide',
            TABLE_VAR_SHOW   => 'tshow',
            TABLE_VAR_IFIRST => 'tifirst',
            TABLE_VAR_ILAST  => 'tilast',
            TABLE_VAR_PAGE   => 'page',
            TABLE_VAR_RESET  => 'treset',
            TABLE_VAR_DIR    => 'tdir',
        ];

        $this->companyid = $companyid;

        $this->usertypes = ['0' => get_string('user', 'block_iomad_company_admin'),
                            '1' => get_string('companymanager', 'block_iomad_company_admin'),
                            '2' => get_string('departmentmanager', 'block_iomad_company_admin'),
                            '3' => get_string('educator', 'block_iomad_company_admin'),
                            '4' => get_string('companyreporter', 'block_iomad_company_admin')];

        $this->departments = $DB->get_records('local_iomad_company_departments', ['companyid' => $companyid], 'name', 'id,name');

        $parentlevel = company::get_company_parentnode($companyid);
        $this->parentlevel = $parentlevel;
        $userlevels = [$parentlevel->id => $parentlevel->id];

        $departmenttree = [];
        foreach ($userlevels as $userlevelid => $userlevel) {
            $departmenttree[] = company::get_all_subdepartments_raw($userlevelid);
        }

        $this->assignabledepartments = company::array_flatten(company::get_department_list($departmenttree[0]));

        $this->departmentsmenu = $DB->get_records_menu(
            'local_iomad_company_departments',
            ['companyid' => $companyid],
            'name',
            'id,name'
        );

        // Deal with role selector.
        $this->usertypeselect = ['0' => get_string('user', 'block_iomad_company_admin')];
        if (iomad::has_capability('block/iomad_company_admin:assign_company_manager', $companycontext)) {
            $this->usertypeselect[10] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $companycontext)) {
            $this->usertypeselect[20] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        if (iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $companycontext)) {
            $this->usertypeselect[40] = get_string('companyreporter', 'block_iomad_company_admin');
        }
        if (!get_config('local_iomad', 'autoenrol_managers')) {
            $this->usertypeselect[1] = get_string('educator', 'block_iomad_company_admin');
            if (iomad::has_capability('block/iomad_company_admin:assign_company_manager', $companycontext)) {
                $this->usertypeselect[10] = get_string('companymanager', 'block_iomad_company_admin');
                $this->usertypeselect[11] = get_string('companymanager', 'block_iomad_company_admin') . '
                                             + ' .
                                             get_string('educator', 'block_iomad_company_admin');
            }
            if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $companycontext)) {
                $this->usertypeselect[20] = get_string('departmentmanager', 'block_iomad_company_admin');
                $this->usertypeselect[21] = get_string('departmentmanager', 'block_iomad_company_admin') .
                                            ' + ' .
                                            get_string('educator', 'block_iomad_company_admin');
            }
            if (iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $companycontext)) {
                $this->usertypeselect[40] = get_string('companyreporter', 'block_iomad_company_admin');
                $this->usertypeselect[41] = get_string('companyreporter', 'block_iomad_company_admin') .
                                            ' + ' .
                                            get_string('educator', 'block_iomad_company_admin');
            }
        }
        ksort($this->usertypeselect);
    }
}
