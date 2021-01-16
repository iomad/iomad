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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains all necessary code to define a wiki file table form element
 *
 * @package mod_wiki
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Josep Arus
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('HTML/QuickForm/element.php');
require_once($CFG->dirroot.'/lib/filelib.php');

class MoodleQuickForm_wikifiletable extends HTML_QuickForm_element {

    private $_contextid;
    private $_filearea;
    private $_fileareaitemid;
    private $_fileinfo;
    private $_value = array();

    public function __construct($elementName = null, $elementLabel = null, $attributes = null, $fileinfo = null, $format = null) {

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_fileinfo = $fileinfo;
        $this->_format = $format;
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function MoodleQuickForm_wikifiletable($elementName = null, $elementLabel = null, $attributes = null, $fileinfo = null, $format = null) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($elementName, $elementLabel, $attributes, $fileinfo, $format);
    }

    function onQuickFormEvent($event, $arg, &$caller) {
        global $OUTPUT;

        switch ($event) {
            case 'addElement':
                $this->_contextid = $arg[3]['contextid'];
                $this->_filearea = $arg[3]['filearea'];
                $this->_fileareaitemid = $arg[3]['itemid'];
                $this->_format = $arg[4];
                break;
        }

        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    function setName($name) {
        $this->updateAttributes(array('name' => $name));
    }

    function getName() {
        return $this->getAttribute('name');
    }

    function setValue($value) {
        $this->_value = $value;
    }

    function getValue() {
        return $this->_value;
    }

    function toHtml() {
        global $CFG, $OUTPUT;

        $htmltable = new html_table();

        $htmltable->head = array(get_string('deleteupload', 'wiki'), get_string('uploadname', 'wiki'), get_string('uploadactions', 'wiki'));

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->_fileinfo['contextid'], 'mod_wiki', 'attachments', $this->_fileinfo['itemid']); //TODO: verify where this is coming from, all params must be validated (skodak)

        if (count($files) < 2) {
            return get_string('noattachments', 'wiki');
        }

        //get tags
        foreach (array('image', 'attach', 'link') as $tag) {
            $tags[$tag] = wiki_parser_get_token($this->_format, $tag);
        }

        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $checkbox = '<input type="checkbox" name="'.$this->_attributes['name'].'[]" value="'.$file->get_pathnamehash().'"';

                if (in_array($file->get_pathnamehash(), $this->_value)) {
                    $checkbox .= ' checked="checked"';
                }
                $checkbox .= " />";

                //actions
                $icon = file_file_icon($file);
                $file_url = file_encode_url($CFG->wwwroot.'/pluginfile.php', "/{$this->_contextid}/mod_wiki/attachments/{$this->_fileareaitemid}/".$file->get_filename());

                $action_icons = "";
                if(!empty($tags['attach'])) {
                    $action_icons .= "<a href=\"javascript:void(0)\" class=\"wiki-attachment-attach\" ".$this->printInsertTags($tags['attach'], $file->get_filename())." title=\"".get_string('attachmentattach', 'wiki')."\">".$OUTPUT->pix_icon($icon, "Attach")."</a>"; //TODO: localize
                }

                $action_icons .= "&nbsp;&nbsp;<a href=\"javascript:void(0)\" class=\"wiki-attachment-link\" ".$this->printInsertTags($tags['link'], $file_url)." title=\"".get_string('attachmentlink', 'wiki')."\">".$OUTPUT->pix_icon($icon, "Link")."</a>";

                if (file_mimetype_in_typegroup($file->get_mimetype(), 'web_image')) {
                    $action_icons .= "&nbsp;&nbsp;<a href=\"javascript:void(0)\" class=\"wiki-attachment-image\" ".$this->printInsertTags($tags['image'], $file->get_filename())." title=\"".get_string('attachmentimage', 'wiki')."\">".$OUTPUT->pix_icon($icon, "Image")."</a>"; //TODO: localize
                }

                $htmltable->data[] = array($checkbox, '<a href="'.$file_url.'">'.$file->get_filename().'</a>', $action_icons);
            }
        }

        return html_writer::table($htmltable);
    }

    private function printInsertTags($tags, $value) {
        return "onclick=\"javascript:insertTags('{$tags[0]}', '{$tags[1]}', '$value');\"";
    }
}

//register wikieditor
MoodleQuickForm::registerElementType('wikifiletable', $CFG->dirroot."/mod/wiki/editors/wikifiletable.php", 'MoodleQuickForm_wikifiletable');


