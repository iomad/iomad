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
 *
 * @package   theme_iomadmoon
 * @copyright 2022 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 *
 */

/**
 * @copyright  2015 Jeremy Hopkins (Coventry University)
 * @copyright  2015 Fernando Acedo (3-bits.com)
 * @copyright  2020 G J Barnard
 *               {@link https://moodle.org/user/profile.php?id=442195}
 *               {@link https://gjbarnard.co.uk}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

/**
 * Class to configure html editor for admin settings allowing use of repositories.
 *
 * TODO: Does not remove old files when no longer in use!  No separate file area for each setting.
 *
 * Special thanks to Iban Cardona i Subiela (http://icsbcn.blogspot.com.es/2015/03/use-image-repository-in-theme-settings.html)
 * This post laid the ground work for most of the code featured in this file.
 */
class iomadmoon_setting_confightmleditor extends admin_setting_configtext {

    /** @var int number of rows */
    private $rows;

    /** @var int number of columns */
    private $cols;

    /** @var string filearea - filearea within Moodle repository API */
    private $filearea;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param mixed $defaultsetting string or array
     * @param mixed $paramtype
     * @param int $cols
     * @param int $rows
     * @param string $filearea
     */
    public function __construct(
        $name,
        $visiblename,
        $description,
        $defaultsetting,
        $paramtype = PARAM_RAW,
        $cols = '60',
        $rows = '8',
        $filearea = 'spacesettingsimgs'
    ) {
        $this->rows = $rows;
        $this->cols = $cols;
        $this->filearea = $filearea;
        $this->nosave = (during_initial_install() || CLI_SCRIPT);
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype);
        editors_head_setup();
    }

    /**
     * Gets the file area options.
     *
     * @param context_user $ctx
     * @return array
     */
    private function get_options(context_user $ctx) {
        $default = [];
        $default['noclean'] = false;
        $default['context'] = $ctx;
        $default['maxbytes'] = 0;
        $default['maxfiles'] = -1;
        $default['forcehttps'] = false;
        $default['subdirs'] = false;
        $default['changeformat'] = 0;
        $default['areamaxbytes'] = FILE_AREA_MAX_BYTES_UNLIMITED;
        $default['return_types'] = (FILE_INTERNAL | FILE_EXTERNAL);

        return $default;
    }

    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query = '') {
        if (PHPUNIT_TEST) {
            $userid = 2;  // Admin user.
        } else {
            global $USER;
            $userid = $USER->id;
        }

        $default = $this->get_defaultsetting();

        $defaultinfo = $default;
        if (!is_null($default) && $default !== '') {
            $defaultinfo = "\n" . $default;
        }

        $ctx = context_user::instance($userid);
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $options = $this->get_options($ctx);
        $draftitemid = file_get_unused_draft_itemid();
        $component = is_null($this->plugin) ? 'core' : $this->plugin;
        $data = file_prepare_draft_area(
            $draftitemid,
            $options['context']->id,
            $component,
            $this->get_full_name() . '_draftitemid',
            $draftitemid,
            $options,
            $data
        );

        $fpoptions = [];
        $args = new stdClass();

        // Need these three to filter repositories list.
        $args->accepted_types = ['web_image'];
        $args->return_types = $options['return_types'];
        $args->context = $ctx;
        $args->env = 'filepicker';

        // Advimage plugin.
        $imageoptions = initialise_filepicker($args);
        $imageoptions->context = $ctx;
        $imageoptions->client_id = uniqid();
        $imageoptions->maxbytes = $options['maxbytes'];
        $imageoptions->areamaxbytes = $options['areamaxbytes'];
        $imageoptions->env = 'editor';
        $imageoptions->itemid = $draftitemid;

        // Moodlemedia plugin.
        $args->accepted_types = ['video', 'audio'];
        $mediaoptions = initialise_filepicker($args);
        $mediaoptions->context = $ctx;
        $mediaoptions->client_id = uniqid();
        $mediaoptions->maxbytes = $options['maxbytes'];
        $mediaoptions->areamaxbytes = $options['areamaxbytes'];
        $mediaoptions->env = 'editor';
        $mediaoptions->itemid = $draftitemid;

        // Advlink plugin.
        $args->accepted_types = '*';
        $linkoptions = initialise_filepicker($args);
        $linkoptions->context = $ctx;
        $linkoptions->client_id = uniqid();
        $linkoptions->maxbytes = $options['maxbytes'];
        $linkoptions->areamaxbytes = $options['areamaxbytes'];
        $linkoptions->env = 'editor';
        $linkoptions->itemid = $draftitemid;

        $fpoptions['image'] = $imageoptions;
        $fpoptions['media'] = $mediaoptions;
        $fpoptions['link'] = $linkoptions;

        $editor->use_editor($this->get_id(), $options, $fpoptions);

        return format_admin_setting(
            $this,
            $this->visiblename,
            '<div class="form-textarea">
                    <textarea rows="' . $this->rows .
                '" cols="' .
                $this->cols .
                '" id="' .
                $this->get_id() .
                '" name="' .
                $this->get_full_name() .
                '"spellcheck="true">' . s($data) . '
                    </textarea>
                </div>
                <input value="' .
                $draftitemid .
                '" name="' .
                $this->get_full_name() .
                '_draftitemid" type="hidden" />',
            $this->description,
            true,
            '',
            $defaultinfo,
            $query
        );
    }

    /**
     * Writes the setting to the database.
     *
     * @param mixed $data
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function write_setting($data) {
        global $CFG, $USER;

        if ($this->nosave) {
            return '';
        }

        if ($this->paramtype === PARAM_INT && $data === '') {
            // Do not complain if '' used instead of 0 !
            $data = 0;
        }
        // ... $data is a string.
        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }

        $options = $this->get_options(context_user::instance($USER->id));
        $fs = get_file_storage();
        $component = is_null($this->plugin) ? 'core' : $this->plugin;
        $wwwroot = $CFG->wwwroot;
        if ($options['forcehttps']) {
            $wwwroot = str_replace('http://', 'https://', $wwwroot);
        }

        $draftitemidname = sprintf('%s_draftitemid', $this->get_full_name());
        if (PHPUNIT_TEST || !isset($_REQUEST[$draftitemidname])) {
            $draftitemid = 0;
        } else {
            $draftitemid = $_REQUEST[$draftitemidname];
        }

        $hasfiles = false;
        $draftfiles = $fs->get_area_files($options['context']->id, 'user', 'draft', $draftitemid, 'id');
        foreach ($draftfiles as $file) {
            if (!$file->is_directory()) {
                $urlfilename = rawurlencode($file->get_filename());
                $strtosearch = "$wwwroot/draftfile.php/" . $options['context']->id . "/user/draft/$draftitemid/" . $urlfilename;
                if (stripos($data, $strtosearch) !== false) {
                    $filerecord = [
                        'contextid' => context_system::instance()->id,
                        'component' => $component,
                        'filearea' => $this->filearea,
                        'filename' => $file->get_filename(),
                        'filepath' => '/',
                        'itemid' => 0,
                        'timemodified' => time(),
                    ];
                    if (
                        !$filerec = $fs->get_file(
                            $filerecord['contextid'],
                            $filerecord['component'],
                            $filerecord['filearea'],
                            $filerecord['itemid'],
                            $filerecord['filepath'],
                            $filerecord['filename']
                        )
                    ) {
                        $filerec = $fs->create_file_from_storedfile($filerecord, $file);
                    }
                    $url = moodle_url::make_pluginfile_url(
                        $filerec->get_contextid(),
                        $filerec->get_component(),
                        $filerec->get_filearea(),
                        $filerec->get_itemid(),
                        $filerec->get_filepath(),
                        $filerec->get_filename()
                    );
                    $data = str_ireplace($strtosearch, $url, $data);
                    $hasfiles = true;
                }
            }
        }
        if (!$hasfiles) {
            if (trim(html_to_text($data)) === '') {
                $data = '';
            }
        }

        return ($this->config_write($this->name, $data) ? '' : get_string('errorsetting', 'admin'));
    }
}

/**
 * No setting - just heading and text.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomadmoon_setting_specialsettingheading extends admin_setting {

    /**
     * not a setting, just text
     * @param string $name unique ascii name, either 'mysetting' for settings that in config,
     * or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($name, $heading, $information) {
        $this->nosave = true;
        parent::__construct($name, $heading, $information, '');
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;
        $title = $this->visiblename;
        $description = $this->description;
        $descriptionformatted = highlight($query, markdown_to_html($this->description));

        $output = '<div class="rui-setting-heading-wrapper--special">
        <h3 class="lead-2 mb-2">' . $title . '</h3><div class="rui-setting-desc pb-3 mb-4">' .
            $descriptionformatted .
            '</div></div>';

        return $output;
    }
}
