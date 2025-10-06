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

declare(strict_types=1);

namespace local_iomadcustompage\form;

use context;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use local_iomadcustompage\local\audiences\base;
use local_iomadcustompage\output\audience_heading_editable;
use local_iomadcustompage\permission;
use moodle_url;
use stdClass;

/**
 * Dynamic audience form
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience extends dynamic_form {
    /**
     * The audience we are working with. The instance is created based on the current
     * page and the "id" parameter in the URL. If the "id" parameter is not provided,
     * a new instance is created.
     *
     * @return base
     */
    protected function get_audience(): base {
        $id = $this->optional_param('id', 0, PARAM_INT);

        $record = new stdClass();
        if (!$id) {
            // New instance, pre-define page id and classname.
            $record->pageid = $this->optional_param('pageid', null, PARAM_INT);
            $record->classname = $this->optional_param('classname', null, PARAM_RAW_TRIMMED);
        }
        return base::instance($id, $record);
    }

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $mform->addElement('hidden', 'classname');
        $mform->setType('classname', PARAM_RAW_TRIMMED);

        // Embed form defined in audience class.
        $audience = $this->get_audience();
        $audience->get_config_form($mform);

        $this->add_action_buttons();
    }

    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $audience = $this->get_audience();
        return $audience->validate_config_form($data);
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return $this->get_audience()->get_persistent()->get_page()->get_context();
    }

    /**
     * Ensure current user is able to use this form
     *
     * A {@see \local_iomadcustompage\page_access_exception} will be thrown if they can't
     */
    protected function check_access_for_dynamic_submission(): void {
        $audience = $this->get_audience();

        $page = $audience->get_persistent()->get_page();
        permission::require_can_edit_page($page);

        // Check whether we are able to add/edit the current audience.
        $audience->get_persistent()->get('id') === 0
            ? $audience->require_user_can_add()
            : $audience->require_user_can_edit();
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     */
    public function process_dynamic_submission() {
        global $PAGE;

        require_sesskey();

        $formdata = $this->get_data();
        $audience = $this->get_audience();

        $configdata = $audience::retrieve_configdata($formdata);
        if (!$formdata->id) {
            // New audience.
            $audience = $audience::create($formdata->pageid, $configdata);
        } else {
            // Editing audience.
            $audience->update_configdata($configdata);
        }

        $persistent = $audience->get_persistent();
        $editable = new audience_heading_editable(0, $persistent);

        return [
            'instanceid' => $persistent->get('id'),
            'heading' => $editable->render($PAGE->get_renderer('core')),
            'description' => $audience->get_description(),
        ];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $audience = $this->get_audience();
        $persistent = $audience->get_persistent();

        // Populate form data based on whether we are editing/creating an audience.
        if ($persistent->get('id') !== 0) {
            $formdata = [
                'id' => $persistent->get('id'),
                'pageid' => $persistent->get('pageid'),
                'classname' => $persistent->get('classname'),
            ] + $audience->get_configdata();
        } else {
            $formdata = [
                'pageid' => $this->optional_param('pageid', null, PARAM_INT),
                'classname' => $this->optional_param('classname', null, PARAM_RAW_TRIMMED),
            ];
        }

        $this->set_data($formdata);
    }

  /**
   * Page url
   *
   * @return moodle_url
   * @throws moodle_exception
   */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/iomadcustompage/edit.php', ['id' => $this->optional_param('pageid', 0, PARAM_INT)]);
    }
}
