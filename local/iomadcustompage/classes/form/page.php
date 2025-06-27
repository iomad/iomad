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

use coding_exception;
use context;
use context_system;
use core\exception\moodle_exception;
use core\invalid_persistent_exception;
use core_form\dynamic_form;
use dml_exception;
use invalid_parameter_exception;
use local_iomadcustompage\local\helpers\page as pagehelper;
use local_iomadcustompage\local\models\page as page_model;
use local_iomadcustompage\manager;
use local_iomadcustompage\permission;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Page details form
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends dynamic_form {
  /**
   * Return the context for the form, it should be that of the custom page itself, or system when creating a new page
   *
   * @return context
   * @throws dml_exception
   */
    public function get_context_for_dynamic_submission(): context {
        if ($page = $this->get_custom_page()) {
            return $page->get_context();
        } else {
            return context_system::instance();
        }
    }

    /**
     * Return instance of the custom page we are editing, or null when creating a new page
     *
     * @return page_model|null
     */
    protected function get_custom_page(): ?page_model {
        if ($pageid = $this->optional_param('id', 0, PARAM_INT)) {
          return manager::get_page_from_id($pageid);
        }
        return null;
    }
    /**
     * if action button is needed
     * @return bool
     */
    protected function need_action_buttons(): bool {
        if ($needactionbuttons = $this->optional_param('needactionbuttons', 0, PARAM_INT)) {
            return (bool)$needactionbuttons;
        }
        return false;
    }

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 150), 'maxlength', 150);

        $mform->addElement('text', 'title', get_string('title', 'local_iomadcustompage'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('maximumchars', '', 50), 'maxlength', 50);
        if ($this->need_action_buttons()) {
            $this->add_action_buttons(false);
        }
    }

  /**
   * Process the form submission
   *
   * @return string The URL to advance to upon completion
   * @throws coding_exception
   * @throws invalid_persistent_exception
   * @throws invalid_parameter_exception
   * @throws moodle_exception
   */
    public function process_dynamic_submission() {
        $data = $this->get_data();

        require_sesskey();

        if ($data->id) {
            $pagepersistent = pagehelper::update_page($data);
        } else {
            $pagepersistent = pagehelper::create_page($data);
        }

        return (new moodle_url('/local/iomadcustompage/edit.php', ['id' => $pagepersistent->get('id')]))->out(false);
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        if ($page = $this->get_custom_page()) {
            $this->set_data($page->to_record());
        }
    }

    /**
     * URL of the page using this form
     *
     * @return moodle_url
     */
    public function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/iomadcustompage/index.php');
    }

  /**
   * Perform some extra moodle validation
   *
   * @param array $data
   * @param array $files
   * @return array
   * @throws coding_exception
   */
    public function validation($data, $files): array {
        $errors = [];

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Ensure current user is able to use this form
     *
     * A {@see \local_iomadcustompage\page_access_exception} will be thrown if they can't
     */
    protected function check_access_for_dynamic_submission(): void {
        $page = $this->get_custom_page();

        if ($page) {
            permission::require_can_edit_page($page);
        } else {
            permission::require_can_create_page();
        }
    }
}
