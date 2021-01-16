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
 * Profile field API library file.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define ('PROFILE_VISIBLE_ALL',     '2'); // Only visible for users with moodle/user:update capability.
define ('PROFILE_VISIBLE_PRIVATE', '1'); // Either we are viewing our own profile or we have moodle/user:update capability.
define ('PROFILE_VISIBLE_NONE',    '0'); // Only visible for moodle/user:update capability.

/**
 * Base class for the customisable profile fields.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_base {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $userid;

    /** @var stdClass */
    public $field;

    /** @var string */
    public $inputname;

    /** @var mixed */
    public $data;

    /** @var string */
    public $dataformat;

    /** @var string name of the user profile category */
    protected $categoryname;

    /**
     * Constructor method.
     * @param int $fieldid id of the profile from the user_info_field table
     * @param int $userid id of the user for whom we are displaying data
     * @param object $fielddata optional data for the field object plus additional fields 'hasuserdata', 'data' and 'dataformat'
     *    with user data. (If $fielddata->hasuserdata is empty, user data is not available and we should use default data).
     *    If this parameter is passed, constructor will not call load_data() at all.
     */
    public function __construct($fieldid=0, $userid=0, $fielddata=null) {
        global $CFG;

        if ($CFG->debugdeveloper) {
            // In Moodle 3.4 the new argument $fielddata was added to the constructor. Make sure that
            // plugin constructor properly passes this argument.
            $backtrace = debug_backtrace();
            if (isset($backtrace[1]['class']) && $backtrace[1]['function'] === '__construct' &&
                    in_array(self::class, class_parents($backtrace[1]['class']))) {
                // If this constructor is called from the constructor of the plugin make sure that the third argument was passed through.
                if (count($backtrace[1]['args']) >= 3 && count($backtrace[0]['args']) < 3) {
                    debugging($backtrace[1]['class'].'::__construct() must support $fielddata as the third argument ' .
                        'and pass it to the parent constructor', DEBUG_DEVELOPER);
                }
            }
        }

        $this->set_fieldid($fieldid);
        $this->set_userid($userid);
        if ($fielddata) {
            $this->set_field($fielddata);
            if ($userid > 0 && !empty($fielddata->hasuserdata)) {
                $this->set_user_data($fielddata->data, $fielddata->dataformat);
            }
        } else {
            $this->load_data();
        }
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function profile_field_base($fieldid=0, $userid=0) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($fieldid, $userid);
    }

    /**
     * Abstract method: Adds the profile field to the moodle form class
     * @abstract The following methods must be overwritten by child classes
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        print_error('mustbeoveride', 'debug', '', 'edit_field_add');
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        $options = new stdClass();
        $options->para = false;
        return format_text($this->data, FORMAT_MOODLE, $options);
    }

    /**
     * Print out the form field in the edit profile page
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_field($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {

            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_after_data($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $usernew data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($usernew) {
        global $DB;

        if (!isset($usernew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new stdClass();

        $usernew->{$this->inputname} = $this->edit_save_data_preprocess($usernew->{$this->inputname}, $data);

        $data->userid  = $usernew->id;
        $data->fieldid = $this->field->id;
        $data->data    = $usernew->{$this->inputname};

        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $data->userid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        global $DB;

        $errors = array();
        // Get input value.
        if (isset($usernew->{$this->inputname})) {
            if (is_array($usernew->{$this->inputname}) && isset($usernew->{$this->inputname}['text'])) {
                $value = $usernew->{$this->inputname}['text'];
            } else {
                $value = $usernew->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && (($value !== '') || $this->is_required())) {
            $data = $DB->get_records_sql('
                    SELECT id, userid
                      FROM {user_info_data}
                     WHERE fieldid = ?
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text('?', 255),
                    array($this->field->id, $value));
            if ($data) {
                $existing = false;
                foreach ($data as $v) {
                    if ($v->userid == $usernew->id) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $errors[$this->inputname] = get_string('valuealreadyused');
                }
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;
        if ($this->is_required() && ($this->userid == $USER->id || isguestuser())) {
            $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param stdClass $data
     * @param stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * Loads a user object with data for this field ready for the edit profile
     * form
     * @param stdClass $user a user object
     */
    public function edit_load_user_data($user) {
        if ($this->data !== null) {
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the user object
     * By default it is, but for field types where the data may be potentially
     * large, the child class should override this and return false
     * @return bool
     */
    public function is_user_object_data() {
        return true;
    }

    /**
     * Accessor method: set the userid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $userid id from the user table
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $fieldid id from the user_info_field table
     */
    public function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Sets the field object and default data and format into $this->data and $this->dataformat
     *
     * This method should be called before {@link self::set_user_data}
     *
     * @param stdClass $field
     * @throws coding_exception
     */
    public function set_field($field) {
        global $CFG;
        if ($CFG->debugdeveloper) {
            $properties = ['id', 'shortname', 'name', 'datatype', 'description', 'descriptionformat', 'categoryid', 'sortorder',
                'required', 'locked', 'visible', 'forceunique', 'signup', 'defaultdata', 'defaultdataformat', 'param1', 'param2',
                'param3', 'param4', 'param5'];
            foreach ($properties as $property) {
                if (!property_exists($field, $property)) {
                    debugging('The \'' . $property . '\' property must be set.', DEBUG_DEVELOPER);
                }
            }
        }
        if ($this->fieldid && $this->fieldid != $field->id) {
            throw new coding_exception('Can not set field object after a different field id was set');
        }
        $this->fieldid = $field->id;
        $this->field = $field;
        $this->inputname = 'profile_field_' . $this->field->shortname;
        $this->data = $this->field->defaultdata;
        $this->dataformat = FORMAT_HTML;
    }

    /**
     * Sets user id and user data for the field
     *
     * @param mixed $data
     * @param int $dataformat
     */
    public function set_user_data($data, $dataformat) {
        $this->data = $data;
        $this->dataformat = $dataformat;
    }

    /**
     * Set the name for the profile category where this field is
     *
     * @param string $categoryname
     */
    public function set_category_name($categoryname) {
        $this->categoryname = $categoryname;
    }

    /**
     * Returns the name of the profile category where this field is
     *
     * @return string
     */
    public function get_category_name() {
        global $DB;
        if ($this->categoryname === null) {
            $this->categoryname = $DB->get_field('user_info_category', 'name', ['id' => $this->field->categoryid]);
        }
        return $this->categoryname;
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     *
     * @internal This method should not generally be overwritten by child classes.
     */
    public function load_data() {
        global $DB;

        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('user_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->set_field($field);
        }

        if (!empty($this->field) && $this->userid > 0) {
            $params = array('userid' => $this->userid, 'fieldid' => $this->fieldid);
            if ($data = $DB->get_record('user_info_data', $params, 'data, dataformat')) {
                $this->set_user_data($data->data, $data->dataformat);
            }
        } else {
            $this->data = null;
        }
    }

    /**
     * Check if the field data is visible to the current user
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_visible() {
        global $USER;

        $context = ($this->userid > 0) ? context_user::instance($this->userid) : context_system::instance();

        switch ($this->field->visible) {
            case PROFILE_VISIBLE_ALL:
                return true;
            case PROFILE_VISIBLE_PRIVATE:
                if ($this->userid == $USER->id) {
                    return true;
                } else {
                    return has_capability('moodle/user:viewalldetails', $context);
                }
            default:
                return has_capability('moodle/user:viewalldetails', $context);
        }
    }

    /**
     * Check if the field data is considered empty
     * @internal This method should not generally be overwritten by child classes.
     * @return boolean
     */
    public function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Check if the field should appear on the signup page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_signup_field() {
        return (boolean)$this->field->signup;
    }

    /**
     * Return the field settings suitable to be exported via an external function.
     * By default it return all the field settings.
     *
     * @return array all the settings
     * @since Moodle 3.2
     */
    public function get_field_config_for_external() {
        return (array) $this->field;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_RAW, NULL_NOT_ALLOWED);
    }
}

/**
 * Returns an array of all custom field records with any defined data (or empty data), for the specified user id.
 * @param int $userid
 * @return profile_field_base[]
 */
function profile_get_user_fields_with_data($userid) {
    global $DB, $CFG;

    // Join any user info data present with each user info field for the user object.
    $sql = 'SELECT uif.*, uic.name AS categoryname ';
    if ($userid > 0) {
        $sql .= ', uind.id AS hasuserdata, uind.data, uind.dataformat ';
    }
    $sql .= 'FROM {user_info_field} uif ';
    $sql .= 'LEFT JOIN {user_info_category} uic ON uif.categoryid = uic.id ';
    if ($userid > 0) {
        $sql .= 'LEFT JOIN {user_info_data} uind ON uif.id = uind.fieldid AND uind.userid = :userid ';
    }
    $params = array('userid' => $userid);
    // IOMAD - Filter the categories
    if (!iomad::has_capability('block/iomad_company_admin:company_view_all', context_system::instance())) {
        $sql .= " AND (uif.categoryid IN (
                  SELECT c.profileid FROM {company} c JOIN {company_users} cu ON (c.id = cu.companyid AND cu.userid = :companyuserid))
                  OR uif.categoryid IN (
                  SELECT id FROM {user_info_category} WHERE id NOT IN (SELECT profileid from {company}))) ";
        $params['companyuserid'] = $userid;
    }
    $sql .= 'ORDER BY uic.sortorder ASC, uif.sortorder ASC ';
    $fields = $DB->get_records_sql($sql, $params);
    $data = [];
    foreach ($fields as $field) {
        require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
        $classname = 'profile_field_' . $field->datatype;
        $field->hasuserdata = !empty($field->hasuserdata);
        /** @var profile_field_base $fieldobject */
        $fieldobject = new $classname($field->id, $userid, $field);
        $fieldobject->set_category_name($field->categoryname);
        unset($field->categoryname);
        $data[] = $fieldobject;
    }
    return $data;
}

/**
 * Returns an array of all custom field records with any defined data (or empty data), for the specified user id, by category.
 * @param int $userid
 * @return profile_field_base[][]
 */
function profile_get_user_fields_with_data_by_category($userid) {
    $fields = profile_get_user_fields_with_data($userid);
    $data = [];
    foreach ($fields as $field) {
        $data[$field->field->categoryid][] = $field;
    }
    return $data;
}

/**
 * Loads user profile field data into the user object.
 * @param stdClass $user
 */
function profile_load_data($user) {
    global $CFG;

    $fields = profile_get_user_fields_with_data($user->id);
    foreach ($fields as $formfield) {
        $formfield->edit_load_user_data($user);
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    $categories = profile_get_user_fields_with_data_by_category($userid);
    foreach ($categories as $categoryid => $fields) {
        // Check first if *any* fields will be displayed.
        $display = false;
        foreach ($fields as $formfield) {
            if ($formfield->is_visible()) {
                $display = true;
            }
        }

        // Display the header and the fields.
        if ($display or $update) {
            $mform->addElement('header', 'category_'.$categoryid, format_string($formfield->get_category_name()));
            foreach ($fields as $formfield) {
                $formfield->edit_field($mform);
            }
        }
    }
}

/**
 * Adds profile fields to user edit forms.
 * @param moodleform $mform
 * @param int $userid
 */
function profile_definition_after_data($mform, $userid) {
    global $CFG;

    $userid = ($userid < 0) ? 0 : (int)$userid;

    $fields = profile_get_user_fields_with_data($userid);
    foreach ($fields as $formfield) {
        $formfield->edit_after_data($mform);
    }
}

/**
 * Validates profile data.
 * @param stdClass $usernew
 * @param array $files
 * @return array
 */
function profile_validation($usernew, $files) {
    global $CFG;

    $err = array();
    $fields = profile_get_user_fields_with_data($usernew->id);
    foreach ($fields as $formfield) {
        $err += $formfield->edit_validate_field($usernew, $files);
    }
    return $err;
}

/**
 * Saves profile data for a user.
 * @param stdClass $usernew
 */
function profile_save_data($usernew) {
    global $CFG;

    $fields = profile_get_user_fields_with_data($usernew->id);
    foreach ($fields as $formfield) {
        $formfield->edit_save_data($usernew);
    }
}

/**
 * Display profile fields.
 * @param int $userid
 */
function profile_display_fields($userid) {
    global $CFG, $USER, $DB;

    $categories = profile_get_user_fields_with_data_by_category($userid);
    foreach ($categories as $categoryid => $fields) {
        foreach ($fields as $formfield) {
            if ($formfield->is_visible() and !$formfield->is_empty()) {
                echo html_writer::tag('dt', format_string($formfield->field->name));
                echo html_writer::tag('dd', $formfield->display_data());
            }
        }
    }
}

/**
 * Retrieves a list of profile fields that must be displayed in the sign-up form.
 *
 * @return array list of profile fields info
 * @since Moodle 3.2
 */
function profile_get_signup_fields() {
    global $CFG, $DB;

    $profilefields = array();
    // Only retrieve required custom fields (with category information)
    // results are sort by categories, then by fields.
    $sql = "SELECT uf.id as fieldid, ic.id as categoryid, ic.name as categoryname, uf.datatype
                FROM {user_info_field} uf
                JOIN {user_info_category} ic
                ON uf.categoryid = ic.id AND uf.signup = 1 AND uf.visible<>0
                ORDER BY ic.sortorder ASC, uf.sortorder ASC";

    if ($fields = $DB->get_records_sql($sql)) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $fieldobject = new $newfield($field->fieldid);

            $profilefields[] = (object) array(
                'categoryid' => $field->categoryid,
                'categoryname' => $field->categoryname,
                'fieldid' => $field->fieldid,
                'datatype' => $field->datatype,
                'object' => $fieldobject
            );
        }
    }
    return $profilefields;
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param moodleform $mform moodle form object
 */
function profile_signup_fields($mform) {

    if ($fields = profile_get_signup_fields()) {
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            };
            $field->object->edit_field($mform);
        }
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * @param integer $userid
 * @param bool $onlyinuserobject True if you only want the ones in $USER.
 * @return stdClass
 */
function profile_user_record($userid, $onlyinuserobject = true) {
    global $CFG;

    $usercustomfields = new stdClass();

    $fields = profile_get_user_fields_with_data($userid);
    foreach ($fields as $formfield) {
        if (!$onlyinuserobject || $formfield->is_user_object_data()) {
            $usercustomfields->{$formfield->field->shortname} = $formfield->data;
        }
    }

    return $usercustomfields;
}

/**
 * Obtains a list of all available custom profile fields, indexed by id.
 *
 * Some profile fields are not included in the user object data (see
 * profile_user_record function above). Optionally, you can obtain only those
 * fields that are included in the user object.
 *
 * To be clear, this function returns the available fields, and does not
 * return the field values for a particular user.
 *
 * @param bool $onlyinuserobject True if you only want the ones in $USER
 * @return array Array of field objects from database (indexed by id)
 * @since Moodle 2.7.1
 */
function profile_get_custom_fields($onlyinuserobject = false) {
    global $DB, $CFG;

    // Get all the fields.
    $fields = $DB->get_records('user_info_field', null, 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            require_once($CFG->dirroot . '/user/profile/field/' .
                    $field->datatype . '/field.class.php');
            $newfield = 'profile_field_' . $field->datatype;
            $formfield = new $newfield();
            if (!$formfield->is_user_object_data()) {
                unset($fields[$id]);
            }
        }
    }

    return $fields;
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param stdClass $user user object
 */
function profile_load_custom_fields($user) {
    $user->profile = (array)profile_user_record($user->id);
}

/**
 * Trigger a user profile viewed event.
 *
 * @param stdClass  $user user  object
 * @param stdClass  $context  context object (course or user)
 * @param stdClass  $course course  object
 * @since Moodle 2.9
 */
function profile_view($user, $context, $course = null) {

    $eventdata = array(
        'objectid' => $user->id,
        'relateduserid' => $user->id,
        'context' => $context
    );

    if (!empty($course)) {
        $eventdata['courseid'] = $course->id;
        $eventdata['other'] = array(
            'courseid' => $course->id,
            'courseshortname' => $course->shortname,
            'coursefullname' => $course->fullname
        );
    }

    $event = \core\event\user_profile_viewed::create($eventdata);
    $event->add_record_snapshot('user', $user);
    $event->trigger();
}

/**
 * Does the user have all required custom fields set?
 *
 * Internal, to be exclusively used by {@link user_not_fully_set_up()} only.
 *
 * Note that if users have no way to fill a required field via editing their
 * profiles (e.g. the field is not visible or it is locked), we still return true.
 * So this is actually checking if we should redirect the user to edit their
 * profile, rather than whether there is a value in the database.
 *
 * @param int $userid
 * @return bool
 */
function profile_has_required_custom_fields_set($userid) {
    global $DB;

    $sql = "SELECT f.id
              FROM {user_info_field} f
         LEFT JOIN {user_info_data} d ON (d.fieldid = f.id AND d.userid = ?)
             WHERE f.required = 1 AND f.visible > 0 AND f.locked = 0 AND d.id IS NULL";

    if ($DB->record_exists_sql($sql, [$userid])) {
        return false;
    }

    return true;
}
