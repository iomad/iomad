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
 * The abstract custom fields handler
 *
 * @package   core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_customfield;

use core_customfield\output\field_data;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * Base class for custom fields handlers
 *
 * This handler provides callbacks for field configuration form and also allows to add the fields to the instance editing form
 *
 * Every plugin that wants to use custom fields must define a handler class:
 * <COMPONENT_OR_PLUGIN>\customfield\<AREA>_handler extends \core_customfield\handler
 *
 * To initiate a class use an appropriate static method:
 * - <handlerclass>::create - to create an instance of a known handler
 * - \core_customfield\handler::get_handler - to create an instance of a handler for given component/area/itemid
 *
 * Also handler is automatically created when the following methods are called:
 * - \core_customfield\api::get_field($fieldid)
 * - \core_customfield\api::get_category($categoryid)
 *
 * @package core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class handler {

    /**
     * The component this handler handles
     *
     * @var string $component
     */
    private $component;

    /**
     * The area within the component
     *
     * @var string $area
     */
    private $area;

    /**
     * The id of the item within the area and component

     * @var int $itemid
     */
    private $itemid;

    /**
     * @var category_controller[]
     */
    protected $categories = null;

    /**
     * Handler constructor.
     *
     * @param int $itemid
     */
    protected final function __construct(int $itemid = 0) {
        if (!preg_match('|^(\w+_[\w_]+)\\\\customfield\\\\([\w_]+)_handler$|', static::class, $matches)) {
            throw new \coding_exception('Handler class name must have format: <PLUGIN>\\customfield\\<AREA>_handler');
        }
        $this->component = $matches[1];
        $this->area = $matches[2];
        $this->itemid = $itemid;
    }

    /**
     * Returns an instance of the handler
     *
     * Some areas may choose to use singleton/caching here
     *
     * @param int $itemid
     * @return handler
     */
    public static function create(int $itemid = 0) : handler {
        return new static($itemid);
    }

    /**
     * Returns an instance of handler by component/area/itemid
     *
     * @param string $component component name of full frankenstyle plugin name
     * @param string $area name of the area (each component/plugin may define handlers for multiple areas)
     * @param int $itemid item id if the area uses them (usually not used)
     * @return handler
     */
    public static function get_handler(string $component, string $area, int $itemid = 0) : handler {
        $classname = $component . '\\customfield\\' . $area . '_handler';
        if (class_exists($classname) && is_subclass_of($classname, self::class)) {
            return $classname::create($itemid);
        }
        $a = ['component' => s($component), 'area' => s($area)];
        throw new \moodle_exception('unknownhandler', 'core_customfield', (object)$a);
    }

    /**
     * Get component
     *
     * @return string
     */
    public function get_component() : string {
        return $this->component;
    }

    /**
     * Get area
     *
     * @return string
     */
    public function get_area() : string {
        return $this->area;
    }

    /**
     * Context that should be used for new categories created by this handler
     *
     * @return \context
     */
    abstract public function get_configuration_context() : \context;

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url
     */
    abstract public function get_configuration_url() : \moodle_url;

    /**
     * Context that should be used for data stored for the given record
     *
     * @param int $instanceid id of the instance or 0 if the instance is being created
     * @return \context
     */
    abstract public function get_instance_context(int $instanceid = 0) : \context;

    /**
     * Get itemid
     *
     * @return int|null
     */
    public function get_itemid() : int {
        return $this->itemid;
    }

    /**
     * Uses categories
     *
     * @return bool
     */
    public function uses_categories() : bool {
        return true;
    }

    /**
     * The form to create or edit a field
     *
     * @param field_controller $field
     * @return field_config_form
     */
    public function get_field_config_form(field_controller $field) : field_config_form {
         $form = new field_config_form(null, ['field' => $field]);
         $form->set_data(api::prepare_field_for_config_form($field));
         return $form;
    }

    /**
     * Generates a name for the new category
     *
     * @param int $suffix
     * @return string
     */
    protected function generate_category_name($suffix = 0) : string {
        if ($suffix) {
            return get_string('otherfieldsn', 'core_customfield', $suffix);
        } else {
            return get_string('otherfields', 'core_customfield');
        }
    }

    /**
     * Creates a new category and inserts it to the database
     *
     * @param string $name name of the category, null to generate automatically
     * @return int id of the new category
     */
    public function create_category(string $name = null) : int {
        global $DB;
        $params = ['component' => $this->get_component(), 'area' => $this->get_area(), 'itemid' => $this->get_itemid()];

        if (empty($name)) {
            for ($suffix = 0; $suffix < 100; $suffix++) {
                $name = $this->generate_category_name($suffix);
                if (!$DB->record_exists(category::TABLE, $params + ['name' => $name])) {
                    break;
                }
            }
        }

        $category = category_controller::create(0, (object)['name' => $name], $this);
        api::save_category($category);
        $this->clear_configuration_cache();
        return $category->get('id');
    }

    /**
     * Validate that the given category belongs to this handler
     *
     * @param category_controller $category
     * @return category_controller
     * @throws \moodle_exception
     */
    protected function validate_category(category_controller $category) : category_controller {
        $categories = $this->get_categories_with_fields();
        if (!array_key_exists($category->get('id'), $categories)) {
            throw new \moodle_exception('categorynotfound', 'core_customfield');
        }
        return $categories[$category->get('id')];
    }

    /**
     * Validate that the given field belongs to this handler
     *
     * @param field_controller $field
     * @return field_controller
     * @throws \moodle_exception
     */
    protected function validate_field(field_controller $field) : field_controller {
        if (!array_key_exists($field->get('categoryid'), $this->get_categories_with_fields())) {
            throw new \moodle_exception('fieldnotfound', 'core_customfield');
        }
        $category = $this->get_categories_with_fields()[$field->get('categoryid')];
        if (!array_key_exists($field->get('id'), $category->get_fields())) {
            throw new \moodle_exception('fieldnotfound', 'core_customfield');
        }
        return $category->get_fields()[$field->get('id')];
    }

    /**
     * Change name for a field category
     *
     * @param category_controller $category
     * @param string $name
     */
    public function rename_category(category_controller $category, string $name) {
        $this->validate_category($category);
        $category->set('name', $name);
        api::save_category($category);
        $this->clear_configuration_cache();
    }

    /**
     * Change sort order of the categories
     *
     * @param category_controller $category category that needs to be moved
     * @param int $beforeid id of the category this category needs to be moved before, 0 to move to the end
     */
    public function move_category(category_controller $category, int $beforeid = 0) {
        $category = $this->validate_category($category);
        api::move_category($category, $beforeid);
        $this->clear_configuration_cache();
    }

    /**
     * Permanently delete category, all fields in it and all associated data
     *
     * @param category_controller $category
     * @return bool
     */
    public function delete_category(category_controller $category) : bool {
        $category = $this->validate_category($category);
        $result = api::delete_category($category);
        $this->clear_configuration_cache();
        return $result;
    }

    /**
     * Deletes all data and all fields and categories defined in this handler
     */
    public function delete_all() {
        $categories = $this->get_categories_with_fields();
        foreach ($categories as $category) {
            api::delete_category($category);
        }
        $this->clear_configuration_cache();
    }

    /**
     * Permanently delete a custom field configuration and all associated data
     *
     * @param field_controller $field
     * @return bool
     */
    public function delete_field_configuration(field_controller $field) : bool {
        $field = $this->validate_field($field);
        $result = api::delete_field_configuration($field);
        $this->clear_configuration_cache();
        return $result;
    }

    /**
     * Change fields sort order, move field to another category
     *
     * @param field_controller $field field that needs to be moved
     * @param int $categoryid category that needs to be moved
     * @param int $beforeid id of the category this category needs to be moved before, 0 to move to the end
     */
    public function move_field(field_controller $field, int $categoryid, int $beforeid = 0) {
        $field = $this->validate_field($field);
        api::move_field($field, $categoryid, $beforeid);
        $this->clear_configuration_cache();
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool
     */
    abstract public function can_configure() : bool;

    /**
     * The current user can edit given custom fields on the given instance
     *
     * Called to filter list of fields displayed on the instance edit form
     *
     * Capability to edit/create instance is checked separately
     *
     * @param field_controller $field
     * @param int $instanceid id of the instance or 0 if the instance is being created
     * @return bool
     */
    abstract public function can_edit(field_controller $field, int $instanceid = 0) : bool;

    /**
     * The current user can view the value of the custom field for a given custom field and instance
     *
     * Called to filter list of fields returned by methods get_instance_data(), get_instances_data(),
     * export_instance_data(), export_instance_data_object()
     *
     * Access to the instance itself is checked by handler before calling these methods
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    abstract public function can_view(field_controller $field, int $instanceid) : bool;

    /**
     * Returns the custom field values for an individual instance
     *
     * The caller must check access to the instance itself before invoking this method
     *
     * The result is an array of data_controller objects
     *
     * @param int $instanceid
     * @param bool $returnall return data for all fields (by default only visible fields)
     * @return data_controller[] array of data_controller objects indexed by fieldid. All fields are present,
     *     some data_controller objects may have 'id', some not
     *     In the last case data_controller::get_value() and export_value() functions will return default values.
     */
    public function get_instance_data(int $instanceid, bool $returnall = false) : array {
        $fields = $returnall ? $this->get_fields() : $this->get_visible_fields($instanceid);
        return api::get_instance_fields_data($fields, $instanceid);
    }

    /**
     * Returns the custom fields values for multiple instances
     *
     * The caller must check access to the instance itself before invoking this method
     *
     * The result is an array of data_controller objects
     *
     * @param int[] $instanceids
     * @param bool $returnall return data for all fields (by default only visible fields)
     * @return data_controller[][] 2-dimension array, first index is instanceid, second index is fieldid.
     *     All instanceids and all fieldids are present, some data_controller objects may have 'id', some not.
     *     In the last case data_controller::get_value() and export_value() functions will return default values.
     */
    public function get_instances_data(array $instanceids, bool $returnall = false) : array {
        $result = api::get_instances_fields_data($this->get_fields(), $instanceids);

        if (!$returnall) {
            // Filter only by visible fields (list of visible fields may be different for each instance).
            $handler = $this;
            foreach ($instanceids as $instanceid) {
                $result[$instanceid] = array_filter($result[$instanceid], function(data_controller $d) use ($handler) {
                    return $handler->can_view($d->get_field(), $d->get('instanceid'));
                });
            }
        }
        return $result;
    }

    /**
     * Returns the custom field values for an individual instance ready to be displayed
     *
     * The caller must check access to the instance itself before invoking this method
     *
     * The result is an array of \core_customfield\output\field_data objects
     *
     * @param int $instanceid
     * @param bool $returnall
     * @return \core_customfield\output\field_data[]
     */
    public function export_instance_data(int $instanceid, bool $returnall = false) : array {
        return array_map(function($d) {
            return new field_data($d);
        }, $this->get_instance_data($instanceid, $returnall));
    }

    /**
     * Returns the custom field values for an individual instance ready to be displayed
     *
     * The caller must check access to the instance itself before invoking this method
     *
     * The result is a class where properties are fields short names and the values their export values for this instance
     *
     * @param int $instanceid
     * @param bool $returnall
     * @return stdClass
     */
    public function export_instance_data_object(int $instanceid, bool $returnall = false) : stdClass {
        $rv = new stdClass();
        foreach ($this->export_instance_data($instanceid, $returnall) as $d) {
            $rv->{$d->get_shortname()} = $d->get_value();
        }
        return $rv;
    }

    /**
     * Display visible custom fields.
     * This is a sample implementation that can be overridden in each handler.
     *
     * @param data_controller[] $fieldsdata
     * @return string
     */
    public function display_custom_fields_data(array $fieldsdata) : string {
        global $PAGE;
        $output = $PAGE->get_renderer('core_customfield');
        $content = '';
        foreach ($fieldsdata as $data) {
            $fd = new field_data($data);
            $content .= $output->render($fd);
        }

        return $content;
    }

    /**
     * Returns array of categories, each of them contains a list of fields definitions.
     *
     * @return category_controller[]
     */
    public function get_categories_with_fields() : array {
        if ($this->categories === null) {
            $this->categories = api::get_categories_with_fields($this->get_component(), $this->get_area(), $this->get_itemid());
        }
        $handler = $this;
        array_walk($this->categories, function(category_controller $c) use ($handler) {
            $c->set_handler($handler);
        });
        return $this->categories;
    }

    /**
     * Clears a list of categories with corresponding fields definitions.
     */
    protected function clear_configuration_cache() {
        $this->categories = null;
    }

    /**
     * Checks if current user can backup a given field
     *
     * Capability to backup the instance does not need to be checked here
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    protected function can_backup(field_controller $field, int $instanceid) : bool {
        return $this->can_view($field, $instanceid) || $this->can_edit($field, $instanceid);
    }

    /**
     * Get raw data associated with all fields current user can view or edit
     *
     * @param int $instanceid
     * @return array
     */
    public function get_instance_data_for_backup(int $instanceid) : array {
        $finalfields = [];
        $data = $this->get_instance_data($instanceid, true);
        foreach ($data as $d) {
            if ($d->get('id') && $this->can_backup($d->get_field(), $instanceid)) {
                $finalfields[] = [
                    'id' => $d->get('id'),
                    'shortname' => $d->get_field()->get('shortname'),
                    'type' => $d->get_field()->get('type'),
                    'value' => $d->get_value(),
                    'valueformat' => $d->get('valueformat')];
            }
        }
        return $finalfields;
    }

    /**
     * Form data definition callback.
     *
     * This method is called from moodleform::definition_after_data and allows to tweak
     * mform with some data coming directly from the field plugin data controller.
     *
     * @param \MoodleQuickForm $mform
     * @param int $instanceid
     */
    public function instance_form_definition_after_data(\MoodleQuickForm $mform, int $instanceid = 0) {
        $editablefields = $this->get_editable_fields($instanceid);
        $fields = api::get_instance_fields_data($editablefields, $instanceid);

        foreach ($fields as $formfield) {
            $formfield->instance_form_definition_after_data($mform);
        }
    }

    /**
     * Prepares the custom fields data related to the instance to pass to mform->set_data()
     *
     * Example:
     *   $instance = $DB->get_record(...);
     *   // .... prepare editor, filemanager, add tags, etc.
     *   $handler->instance_form_before_set_data($instance);
     *   $form->set_data($instance);
     *
     * @param stdClass $instance the instance that has custom fields, if 'id' attribute is present the custom
     *    fields for this instance will be added, otherwise the default values will be added.
     */
    public function instance_form_before_set_data(stdClass $instance) {
        $instanceid = !empty($instance->id) ? $instance->id : 0;
        $fields = api::get_instance_fields_data($this->get_editable_fields($instanceid), $instanceid);

        foreach ($fields as $formfield) {
            $formfield->instance_form_before_set_data($instance);
        }
    }

    /**
     * Saves the given data for custom fields, must be called after the instance is saved and id is present
     *
     * Example:
     *   if ($data = $form->get_data()) {
     *     // ... save main instance, set $data->id if instance was created.
     *     $handler->instance_form_save($data);
     *     redirect(...);
     *   }
     *
     * @param stdClass $instance data received from a form
     * @param bool $isnewinstance if this is call is made during instance creation
     */
    public function instance_form_save(stdClass $instance, bool $isnewinstance = false) {
        if (empty($instance->id)) {
            throw new \coding_exception('Caller must ensure that id is already set in data before calling this method');
        }
        if (!preg_grep('/^customfield_/', array_keys((array)$instance))) {
            // For performance.
            return;
        }
        $editablefields = $this->get_editable_fields($isnewinstance ? 0 : $instance->id);
        $fields = api::get_instance_fields_data($editablefields, $instance->id);
        foreach ($fields as $data) {
            if (!$data->get('id')) {
                $data->set('contextid', $this->get_instance_context($instance->id)->id);
            }
            $data->instance_form_save($instance);
        }
    }

    /**
     * Validates the given data for custom fields, used in moodleform validation() function
     *
     * Example:
     *   public function validation($data, $files) {
     *     $errors = [];
     *     // .... check other fields.
     *     $errors = array_merge($errors, $handler->instance_form_validation($data, $files));
     *     return $errors;
     *   }
     *
     * @param array $data
     * @param array $files
     * @return array validation errors
     */
    public function instance_form_validation(array $data, array $files) {
        $instanceid = empty($data['id']) ? 0 : $data['id'];
        $editablefields = $this->get_editable_fields($instanceid);
        $fields = api::get_instance_fields_data($editablefields, $instanceid);
        $errors = [];
        foreach ($fields as $formfield) {
            $errors += $formfield->instance_form_validation($data, $files);
        }
        return $errors;
    }

    /**
     * Adds custom fields to instance editing form
     *
     * Example:
     *   public function definition() {
     *     // ... normal instance definition, including hidden 'id' field.
     *     $handler->instance_form_definition($this->_form, $instanceid);
     *     $this->add_action_buttons();
     *   }
     *
     * @param \MoodleQuickForm $mform
     * @param int $instanceid id of the instance, can be null when instance is being created
     * @param string $headerlangidentifier If specified, a lang string will be used for field category headings
     * @param string $headerlangcomponent
     */
    public function instance_form_definition(\MoodleQuickForm $mform, int $instanceid = 0,
            ?string $headerlangidentifier = null, ?string $headerlangcomponent = null) {

        $editablefields = $this->get_editable_fields($instanceid);
        $fieldswithdata = api::get_instance_fields_data($editablefields, $instanceid);
        $lastcategoryid = null;
        foreach ($fieldswithdata as $data) {
            $categoryid = $data->get_field()->get_category()->get('id');
            if ($categoryid != $lastcategoryid) {
                $categoryname = format_string($data->get_field()->get_category()->get('name'));

                // Load category header lang string if specified.
                if (!empty($headerlangidentifier)) {
                    $categoryname = get_string($headerlangidentifier, $headerlangcomponent, $categoryname);
                }

                $mform->addElement('header', 'category_' . $categoryid, $categoryname);
                $lastcategoryid = $categoryid;
            }
            $data->instance_form_definition($mform);
            $field = $data->get_field()->to_record();
            if (strlen($field->description)) {
                // Add field description.
                $context = $this->get_configuration_context();
                $value = file_rewrite_pluginfile_urls($field->description, 'pluginfile.php',
                    $context->id, 'core_customfield', 'description', $field->id);
                $value = format_text($value, $field->descriptionformat, ['context' => $context]);
                $mform->addElement('static', 'customfield_' . $field->shortname . '_static', '', $value);
            }
        }
    }

    /**
     * Get field types array
     *
     * @return array
     */
    public function get_available_field_types() :array {
        return api::get_available_field_types();
    }

    /**
     * Options for processing embedded files in the field description.
     *
     * Handlers may want to extend it to disable files support and/or specify 'noclean'=>true
     * Context is not necessary here
     *
     * @return array
     */
    public function get_description_text_options() : array {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => $this->get_configuration_context()
        ];
    }

    /**
     * Save the field configuration with the data from the form
     *
     * @param field_controller $field
     * @param stdClass $data data from the form
     */
    public function save_field_configuration(field_controller $field, stdClass $data) {
        if ($field->get('id')) {
            $field = $this->validate_field($field);
        } else {
            $this->validate_category($field->get_category());
        }
        api::save_field_configuration($field, $data);
        $this->clear_configuration_cache();
    }

    /**
     * Creates or updates custom field data for a instanceid from backup data.
     *
     * The handlers have to override it if they support backup
     *
     * @param \restore_task $task
     * @param array $data
     */
    public function restore_instance_data_from_backup(\restore_task $task, array $data) {
        throw new \coding_exception('Must be implemented in the handler');
    }

    /**
     * Returns list of fields defined for this instance as an array (not groupped by categories)
     *
     * Fields are sorted in the same order they would appear on the instance edit form
     *
     * Note that this function returns all fields in all categories regardless of whether the current user
     * can view or edit data associated with them
     *
     * @return field_controller[]
     */
    public function get_fields() : array {
        $categories = $this->get_categories_with_fields();
        $fields = [];
        foreach ($categories as $category) {
            foreach ($category->get_fields() as $field) {
                $fields[$field->get('id')] = $field;
            }
        }
        return $fields;
    }

    /**
     * Get visible fields
     *
     * @param int $instanceid
     * @return field_controller[]
     */
    protected function get_visible_fields(int $instanceid) : array {
        $handler = $this;
        return array_filter($this->get_fields(),
            function($field) use($handler, $instanceid) {
                return $handler->can_view($field, $instanceid);
            }
        );
    }

    /**
     * Get editable fields
     *
     * @param int $instanceid
     * @return field_controller[]
     */
    public function get_editable_fields(int $instanceid) : array {
        $handler = $this;
        return array_filter($this->get_fields(),
            function($field) use($handler, $instanceid) {
                return $handler->can_edit($field, $instanceid);
            }
        );
    }

    /**
     * Allows to add custom controls to the field configuration form that will be saved in configdata
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform) {
    }

    /**
     * Deletes all data related to all fields of an instance.
     *
     * @param int $instanceid
     */
    public function delete_instance(int $instanceid) {
        $fielddata = api::get_instance_fields_data($this->get_fields(), $instanceid, false);
        foreach ($fielddata as $data) {
            $data->delete();
        }
    }

    /**
     * Set up page customfield/edit.php
     *
     * Handler should override this method and set page context
     *
     * @param field_controller $field
     * @return string page heading
     */
    public function setup_edit_page(field_controller $field) : string {
        global $PAGE;

        // Page context.
        $context = $this->get_configuration_context();
        if ($context->contextlevel == CONTEXT_MODULE) {
            list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid, '', $context->get_course_context()->instanceid);
            require_login($course, false, $cm);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            require_login($context->instanceid, false);
        } else {
            $PAGE->set_context(null); // This will set to system context only if the context was not set before.
            if ($PAGE->context->id != $context->id) {
                // In case of user or block context level this method must be overridden.
                debugging('Handler must override setup_edit_page() and set the page context before calling parent method.',
                    DEBUG_DEVELOPER);
            }
        }

        // Set up url and title.
        if ($field->get('id')) {
            $field = $this->validate_field($field);
        } else {
            $this->validate_category($field->get_category());
        }
        $url = new \moodle_url('/customfield/edit.php',
            ['id' => $field->get('id'), 'type' => $field->get('type'), 'categoryid' => $field->get('categoryid')]);

        $PAGE->set_url($url);
        $typestr = get_string('pluginname', 'customfield_' . $field->get('type'));
        if ($field->get('id')) {
            $title = get_string('editingfield', 'core_customfield',
                $field->get_formatted_name());
        } else {
            $title = get_string('addingnewcustomfield', 'core_customfield', $typestr);
        }
        $PAGE->set_title($title);
        return $title;
    }
}
