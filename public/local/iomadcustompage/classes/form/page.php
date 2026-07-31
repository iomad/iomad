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
use core\context;
use core\context\system;
use core_form\dynamic_form;
use dml_exception;
use Exception;
use local_iomadcustompage\constants;
use local_iomadcustompage\local\models\page as page_model;
use local_iomadcustompage\manager;
use local_iomadcustompage\page_access_exception;
use local_iomadcustompage\permission;
use moodle_url;
use local_iomadcustompage\local\helpers\page as pagehelper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Report details form
 *
 * @package     local_iomadcustompage
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends dynamic_form {
    /**
     * Return the context for the form, it should be that of the custom page itself, or system when creating a new page
     *
     * @return context
     */
    public function get_context_for_dynamic_submission(): \core\context {
        if ($page = $this->get_custom_page()) {
            return $page->get_context();
        } else {
            return \core\context\system::instance();
        }
    }

    /**
     * Return instance of the custom page we are editing, or null when creating a new page
     *
     * @return page_model|null
     */
    protected function get_custom_page(): ?page_model {
        if ($pageid = $this->optional_param('id', 0, PARAM_INT)) {
            /** @var page_model $iomadcustompage */
            $iomadcustompage = manager::get_page_from_id($pageid);
            return $iomadcustompage;
        }
        return null;
    }

    /**
     * Determine whether action buttons should be rendered.
     *
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
        $pageid = $this->_customdata['id'] ?? 0;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $editoroptions = pagehelper::get_page_editor_options($this->get_context_for_dynamic_submission());

        $mform->addElement('editor', 'name_editor', get_string('name'), ['rows' => 4], $editoroptions);
        $mform->setType('name_editor', PARAM_CLEANHTML);
        $mform->addRule('name_editor', null, 'required', null, 'client');

        $mform->addElement('editor', 'title_editor', get_string('title', 'local_iomadcustompage'), ['rows' => 4], $editoroptions);
        $mform->setType('title_editor', PARAM_CLEANHTML);

        // Container page checkbox.
        $mform->addElement(
            'advcheckbox',
            'iscontainer',
            get_string('iscontainer', 'local_iomadcustompage'),
            get_string('iscontainer_desc', 'local_iomadcustompage')
        );
        $mform->setType('iscontainer', PARAM_INT);
        $mform->addHelpButton('iscontainer', 'iscontainer', 'local_iomadcustompage');

        // Show in primary navigation checkbox.
        $mform->addElement(
            'advcheckbox',
            'showinprimarynav',
            get_string('showinprimarynav', 'local_iomadcustompage'),
            get_string('showinprimarynav_desc', 'local_iomadcustompage')
        );
        $mform->setType('showinprimarynav', PARAM_INT);
        $mform->addHelpButton('showinprimarynav', 'showinprimarynav', 'local_iomadcustompage');

        // Parent page selection.
        $parentoptions = $this->get_parent_options($pageid);
        if (!empty($parentoptions)) {
            $mform->addElement('select', 'parent', get_string('parentpage', 'local_iomadcustompage'), $parentoptions);
            $mform->setType('parent', PARAM_INT);
            $mform->addHelpButton('parent', 'parentpage', 'local_iomadcustompage');

            // Add note about container pages.
            $mform->addElement(
                'static',
                'containernote',
                '',
                get_string('containernote', 'local_iomadcustompage')
            );

            // Add note about depth limitation.
            $mform->addElement(
                'static',
                'depthnote',
                '',
                get_string('depthlimitation', 'local_iomadcustompage', constants::PAGE_MAX_DEPTH - 1)
            );

            // Hide elements when container is checked (consolidated for performance).
            $elementstohide = ['parent', 'containernote', 'depthnote'];
            foreach ($elementstohide as $element) {
                $mform->hideIf($element, 'iscontainer', 'checked');
            }
        }

        if ($this->need_action_buttons()) {
            $this->add_action_buttons(false);
        }
    }

    /**
     * Process the form submission
     *
     * @return string The URL to advance to upon completion
     */
    public function process_dynamic_submission() {

        require_sesskey();

        $data = $this->get_data();
        $context = $this->get_context_for_dynamic_submission();
        $editoroptions = pagehelper::get_page_editor_options($context);

        $data = file_postupdate_standard_editor($data, 'name', $editoroptions, $context);

        $data = file_postupdate_standard_editor($data, 'title', $editoroptions, $context);

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
            $context = $this->get_context_for_dynamic_submission();
            $editoroptions = pagehelper::get_page_editor_options($context);
            $pagedata = $page->to_record();
            $pagedata = file_prepare_standard_editor($pagedata, 'name', $editoroptions, $context);
            $pagedata = file_prepare_standard_editor($pagedata, 'title', $editoroptions, $context);
            $this->set_data($pagedata);
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
     * Get parent page options
     *
     * @param int $currentpageid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_parent_options(int $currentpageid = 0): array {
        global $DB;
        $options = [0 => get_string('noparent', 'local_iomadcustompage')];

        // Get all pages except current page and its descendants.
        // $excludeids = [$currentpageid].

        $conditions = ['iscontainer' => 1];
        $excludeids = [];

        if ($currentpageid > 0) {
            // Get all descendant IDs to exclude them from parent options.
            // $descendants = $this->get_descendant_ids($currentpageid).
            // $excludeids = array_merge($excludeids, $descendants).
            $excludeids[] = $currentpageid;
        }

        if (!empty($excludeids)) {
            [$notsql, $notparams] = $DB->get_in_or_equal($excludeids, SQL_PARAMS_NAMED, 'exclude', false);
            $sql = "SELECT id, name, depth, path
                FROM {local_iomadcustompages}
                WHERE id $notsql AND iscontainer = :iscontainer
                ORDER BY name ASC";
            $params = array_merge($notparams, ['iscontainer' => 1]);
            $pages = $DB->get_records_sql($sql, $params);
        } else {
            $pages = $DB->get_records('local_iomadcustompages', $conditions, 'name ASC', 'id, name, depth, path');
        }

        foreach ($pages as $page) {
            $pagepersistent = new page_model(0, $page);
            $options[$page->id] = $pagepersistent->get_formatted_name();
        }

        return $options;
    }

    /**
     * Get descendant page IDs
     *
     * @param int $pageid
     * @return array
     * @throws dml_exception
     */
    private function get_descendant_ids(int $pageid): array {
        global $DB;

        $descendants = [];
        $children = $DB->get_records('local_iomadcustompages', ['parent' => $pageid], '', 'id');

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->get_descendant_ids($child->id));
        }

        return $descendants;
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
        $errors = parent::validation($data, $files);

        if (empty($data['name_editor']['text'])) {
            $errors['name_editor'] = get_string('required');
        }

        // Container pages cannot have parents.
        if (!empty($data['iscontainer']) && !empty($data['parent'])) {
            $errors['iscontainer'] = get_string('containermustberoot', 'local_iomadcustompage');
        }

        // Validate parent selection for content pages.
        if (empty($data['iscontainer']) && !empty($data['parent'])) {
            if (!empty($data['id']) && $data['parent'] == $data['id']) {
                $errors['parent'] = get_string('cannotbeselfparent', 'local_iomadcustompage');
            }

            // Check that parent is a container page.
            try {
                $parent = new page_model($data['parent']);
                if (!$parent->is_container()) {
                    $errors['parent'] = get_string('parentmustbecontainer', 'local_iomadcustompage');
                }
            } catch (\Exception $e) {
                $errors['parent'] = get_string('invalidparent', 'local_iomadcustompage');
            }
        }

        // Check if trying to change container to content page when it has children.
        if (!empty($data['id']) && empty($data['iscontainer'])) {
            try {
                $currentpage = new page_model($data['id']);
                if ($currentpage->is_container() && $currentpage->has_children()) {
                    $errors['iscontainer'] = get_string('containerhaschildren', 'local_iomadcustompage');
                }
            } catch (\Exception $e) {
                $errors['id'] = get_string('invalidpageid', 'local_iomadcustompage');
            }
        }

        return $errors;
    }

    /**
     * Ensure current user is able to use this form
     *
     * A {@see \core_reportbuilder\report_access_exception} will be thrown if they can't
     *
     * @throws page_access_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        $page = $this->get_custom_page();

        if ($page) {
            permission::require_can_edit_page($page);
        } else {
            permission::require_can_create_page();
        }
    }

    /**
     * Check if setting parent would create circular reference
     *
     * @param int $parentid
     * @param int $pageid
     * @return bool
     */
    private function would_create_circular_reference(int $parentid, int $pageid): bool {
        try {
            $current = new page_model($parentid);

            while ($current) {
                if ($current->get('id') == $pageid) {
                    return true;
                }
                $current = $current->get_page_parent();
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}
