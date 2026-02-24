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
 * IOMAD my courses main render class
 * @package   block_mycourses
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourses\output;

use context_system;
use block_mycourses\helper;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use local_iomad\iomad;

/**
 * Class containing data for my overview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /** @var string The tab to display. */
    public $tab;

    /**
     * Constructor.
     *
     * @param string $tab The tab to display.
     */
    public function __construct($tab) {
        $this->tab = $tab;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $DB, $USER, $PAGE;

        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        // Get the sorting params.
        $sort = optional_param('sort', 'coursefullname', PARAM_CLEAN);
        $dir = optional_param('dir', 'ASC', PARAM_CLEAN);
        $tab = optional_param('tab', 'inprogress#mycourses_inprogress_view', PARAM_CLEAN);
        $view = optional_param('view', $CFG->mycourses_defaultview, PARAM_CLEAN);
        $mandatoryonly = optional_param('mandatoryonly', false, PARAM_BOOL);

        // Get the completion info.
        $myinprogress = helper::get_my_inprogress($sort, $dir, $mandatoryonly);
        $myavailable = helper::get_my_available($sort, $dir, $mandatoryonly);
        $myarchive = helper::get_my_archive($sort, $dir, $mandatoryonly);

        $availableview = new available_view($myavailable);
        $inprogressview = new inprogress_view($myinprogress);
        $completedview = new completed_view($myarchive);

        // Are we showing the download certificates button?
        $downloadcerts = false;
        $downloadcertslink = "";
        if (iomad::has_capability('block/iomad_company_admin:downloadmycertificates', context_system::instance())) {
            // Does the user have any certificates to download?
            if ($DB->get_records_sql("SELECT lit.id FROM {local_iomad_tracks} lit
                                      JOIN {local_iomad_track_certs} litc ON (lit.id = litc.trackid)
                                      WHERE lit.userid = :userid
                                      AND lit.companyid = :companyid",
                                     ['userid' => $USER->id,
                                      'companyid' => $companyid])) {
                $downloadcertslinkurl = new moodle_url('/local/report_completion/index.php',
                                                       ['certusers' => $USER->id,
                                                        'action' => 'downloadcerts',
                                                        'sesskey' => sesskey()]);
                $downloadcertslink = $downloadcertslinkurl->out(false);
                $downloadcerts = true;
            }
        }

        // Are mandatory courses enabled?
        $mandatoryselecturl = "";
        $mandatoryselectuse = false;
        if (get_config('local_iomad', 'use_mandatory_courses')) {
            $mandatoryselecturl = new moodle_url($PAGE->url->out(false), ['sort' => $sort,
                                                                          'dir' => $dir,
                                                                          'tab' => $this->tab,
                                                                          'view' => $view,
                                                                          'mandatoryonly' => !$mandatoryonly]);
            $mandatoryselectuse = true;
        }

        // Now, set the tab we are going to be viewing.
        $viewingavailable = false;
        $viewinginprogress = false;
        $viewingcompleted = false;
        if ($this->tab == 'available') {
            $viewingavailable = true;
        } else if ($this->tab == 'completed') {
            $viewingcompleted = true;
        } else {
            $viewinginprogress = true;
        }

        // Set the default for no courses.
        $nocoursesurl = $output->image_url('courses', 'block_mycourses')->out();

        // Set up the sort URL links.
        $sortnameurl = new moodle_url($PAGE->url->out(false), ['sort' => 'coursefullname',
                                                               'dir' => $dir,
                                                               'tab' => $this->tab,
                                                               'mandatoryonly' => $mandatoryonly,
                                                               'view' => $view]);
        $sortdateurl = new moodle_url($PAGE->url->out(false), ['sort' => 'timestarted',
                                                               'dir' => $dir,
                                                               'tab' => $this->tab,
                                                               'mandatoryonly' => $mandatoryonly,
                                                               'view' => $view]);
        $sortascurl = new moodle_url($PAGE->url->out(false), ['sort' => $sort,
                                                              'dir' => 'ASC',
                                                              'tab' => $this->tab,
                                                              'mandatoryonly' => $mandatoryonly,
                                                              'view' => $view]);
        $sortdescurl = new moodle_url($PAGE->url->out(false), ['sort' => $sort,
                                                               'dir' => 'DESC',
                                                               'tab' => $this->tab,
                                                               'mandatoryonly' => $mandatoryonly,
                                                               'view' => $view]);
        $listviewurl = new moodle_url($PAGE->url->out(false), ['sort' => $sort,
                                                               'dir' => $dir,
                                                               'tab' => $this->tab,
                                                               'mandatoryonly' => $mandatoryonly,
                                                               'view' => 'list']);
        $cardviewurl = new moodle_url($PAGE->url->out(false), ['sort' => $sort,
                                                               'dir' => $dir,
                                                               'tab' => $this->tab,
                                                               'mandatoryonly' => $mandatoryonly,
                                                               'view' => 'card']);

        // Set the type of view being used.
        $viewlist = false;
        $viewcard = false;
        if ($view == 'list') {
            $viewlist = true;
        }
        if ($view == 'card') {
            $viewcard = true;
        }

        // Set up the JSON output.
        return [
            'midnight' => usergetmidnight(time()),
            'nocourses' => $nocoursesurl,
            'availableview' => $availableview->export_for_template($output),
            'inprogressview' => $inprogressview->export_for_template($output),
            'completedview' => $completedview->export_for_template($output),
            'viewingavailable' => $viewingavailable,
            'viewinginprogress' => $viewinginprogress,
            'viewingcompleted' => $viewingcompleted,
            'sortnameurl' => $sortnameurl->out(false),
            'sortdateurl' => $sortdateurl->out(false),
            'sortascurl' => $sortascurl->out(false),
            'sortdescurl' => $sortdescurl->out(false),
            'listviewurl' => $listviewurl->out(false),
            'cardviewurl' => $cardviewurl->out(false),
            'downloadcertslink' => $downloadcertslink,
            'downloadcerts' => $downloadcerts,
            'mandatoryselecturl' => $mandatoryselecturl,
            'mandatoryselectuse' => $mandatoryselectuse,
            'mandatoryonly' => $mandatoryonly,
            'viewlist' => $viewlist,
            'viewcard' => $viewcard,
        ];
    }
}
