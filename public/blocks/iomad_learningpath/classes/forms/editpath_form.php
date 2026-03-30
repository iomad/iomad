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
 * Edit/create path form definition for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_learningpath\forms;

use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\event\learningpath_created;
use block_iomad_learningpath\event\learningpath_updated;
use context;
use context_system;
use context_user;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Edit/create path form definition for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editpath_form extends dynamic_form {

    /**
     * Usual form definition stuff
     */
    public function definition() {

        // Seet up the form.
        $mform = $this->_form;

        // Learning Path Id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Learning Path Id.
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        // Learning path name.
        $mform->addElement('text', 'name', get_string('name', 'block_iomad_learningpath'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'name', 'block_iomad_learningpath');
        $mform->addRule('name', get_string('required'), 'required');

        // Description.
        $mform->addElement('editor', 'description', get_string('description'));
        $mform->setType('description', PARAM_RAW);
        $mform->addHelpButton('description', 'description', 'block_iomad_learningpath');
        $mform->addRule('description', get_string('required'), 'required');

        // Active.
        $mform->addElement('selectyesno', 'active', get_string('active', 'block_iomad_learningpath'));
        $mform->setType('active', PARAM_INT);
        $mform->addHelpButton('active', 'active', 'block_iomad_learningpath');

        // Picture.
        $mform->addElement('filemanager', 'picture', get_string('picture', 'block_iomad_learningpath'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => [
                'gif',
                'jpe',
                'jpeg',
                'jpg',
                'png',
            ],
        ]);
        $mform->addHelpButton('picture', 'picture', 'block_iomad_learningpath');

    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $companyid = $data['companyid'];

        $errors = [];
        if ($data['id'] != 0
        && $DB->record_exists('block_iomad_learningpath', ['id' => $data['id']])) {
            if (!empty($DB->get_record_sql(
                "SELECT id, name, companyid
                FROM {block_iomad_learningpath}
                WHERE id <> ?
                AND name = ?
                AND companyid = ?",
                [$data['id'], $data['name'], $companyid]))) {
                $errors['name'] = get_string('learningpathnameused', 'block_iomad_learningpath');
            } else {
                return $errors;
            }
        } else if (!empty($DB->get_record_sql(
            "SELECT companyid, name
            FROM {block_iomad_learningpath}
            WHERE companyid = ?
            AND name = ?",
            [$companyid, $data['name']]))) {
            $errors['name'] = get_string('learningpathnameused', 'block_iomad_learningpath');
        }
        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;

        // Get the info from the form.
        $data = $this->get_data();

        // Set some defaults.
        $systemcontext = context_system::instance();
        $companycontext = context_company::instance($data->companyid);
        $companypaths = new companypaths($data->companyid, $systemcontext);
        $path = $companypaths->get_path($data->id);

        // Set the data from the form.
        $path->name = $data->name;
        $path->description = $data->description['text'];
        $path->active = $data->active;
        $path->timeupdated = time();

        // Are we creating or updating?
        if ($path->id == 0) {
            $path->timecreated = time();
            $path->active = 0;
            $path->id = $DB->insert_record('block_iomad_learningpath', $path);

            // Fire an event for this.
            $event = learningpath_created::create([
                'context' => $companycontext,
                'objectid' => $path->id,
                'userid' => $USER->id,
            ]);
        } else {
            $DB->update_record('block_iomad_learningpath', $path);

            // Fire an event for this.
            $event = learningpath_updated::create([
                'context' => $companycontext,
                'objectid' => $path->id,
                'userid' => $USER->id,
            ]);
        }

        // Check if a file has been uploaded.
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $data->picture, 'itemid', false);
        if (!empty($files)) {
            file_save_draft_area_files($data->picture, $systemcontext->id, 'block_iomad_learningpath', 'picture', $path->id,
                ['maxfiles' => 1]);
            // Resize image and create thumbnail.
            $companypaths->process_image($systemcontext, $path->id);
        } else {
            foreach (['mainpicture', 'thumbnail', 'picture'] as $filearea) {
                $companypaths->delete_file($systemcontext->id, 'block_iomad_learningpath', $filearea, $path->id, true);
            }
        }

        // Fire the event.
        $event->trigger();

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        // Set some defaults.
        $pathid = $this->optional_param('pathid', 0, PARAM_INT);
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $systemcontext = context_system::instance();
        $companypaths = new companypaths($companyid, $systemcontext);
        $companycontext = context_company::instance($companyid);

        // Are we allowed to do this?
        iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

        // Attempt to locate path.
        $path = $companypaths->get_path($pathid);

        // Check for default group.
        $companypaths->check_group($pathid);

        // Get the path information.
        $path->companyid = $companyid;
        $path->id = $pathid;

        // Set up picture draft area.
        $picturedraftid = file_get_submitted_draft_itemid('picture');
        file_prepare_draft_area(
            $picturedraftid,
            $systemcontext->id,
            'block_iomad_learningpath',
            'picture',
            $path->id,
            ['maxfiles' => 1]
        );

        $path->description = ['text' => $path->description];
        $path->picture = $picturedraftid;

        // Send it.
        $this->set_data($path);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_learningpath:manage', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_learningpath/manage.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_learningpath:manage',
                    'iomad_learningpath'
                )
            );
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_learningpath/manage.php');
    }
}

