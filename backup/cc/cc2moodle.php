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
 * @package   moodlecore
 * @subpackage backup-imscc
 * @copyright 2009 Mauro Rondinelli (mauro.rondinelli [AT] uvcms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/backup/cc/entities.class.php');
require_once($CFG->dirroot . '/backup/cc/entity.label.class.php');
require_once($CFG->dirroot . '/backup/cc/entity.resource.class.php');
require_once($CFG->dirroot . '/backup/cc/entity.forum.class.php');
require_once($CFG->dirroot . '/backup/cc/entity.quiz.class.php');

class cc2moodle {

    const CC_TYPE_FORUM              = 'imsdt_xmlv1p0';
    const CC_TYPE_QUIZ               = 'imsqti_xmlv1p2/imscc_xmlv1p0/assessment';
    const CC_TYPE_QUESTION_BANK      = 'imsqti_xmlv1p2/imscc_xmlv1p0/question-bank';
    const CC_TYPE_WEBLINK            = 'imswl_xmlv1p0';
    const CC_TYPE_WEBCONTENT         = 'webcontent';
    const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p0/learning-application-resource';
    const CC_TYPE_EMPTY              = '';

    public static $restypes = array('associatedcontent/imscc_xmlv1p0/learning-application-resource', 'webcontent');
    public static $forumns  = array('dt' => 'http://www.imsglobal.org/xsd/imsdt_v1p0');
    public static $quizns   = array('xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2');
    public static $resourcens = array('wl' => 'http://www.imsglobal.org/xsd/imswl_v1p0');
    /**
     *
     * @return array
     */
    public static function getquizns() {
        return static::$quizns;
    }

    /**
     *
     * @return array
     */
    public static function getforumns() {
        return static::$forumns;
    }

    /**
     *
     * @return array
     */
    public static function getresourcens() {
        return static::$resourcens;
    }

    public static function get_manifest($folder) {
        if (!is_dir($folder)) {
            return false;
        }

        // Before iterate over directories, try to find one manifest at top level
        if (file_exists($folder . '/imsmanifest.xml')) {
            return $folder . '/imsmanifest.xml';
        }

        $result = false;
        try {
            $dirIter = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
            $recIter = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($recIter as $info) {
                if ($info->isFile() && ($info->getFilename() == 'imsmanifest.xml')) {
                    $result = $info->getPathname();
                    break;
                }
            }
        } catch (Exception $e) {}

        return $result;
    }

    public static $instances = array();
    public static $manifest;
    public static $path_to_manifest_folder;

    public static $namespaces = array('imscc'    => 'http://www.imsglobal.org/xsd/imscc/imscp_v1p1',
                                      'lomimscc' => 'http://ltsc.ieee.org/xsd/imscc/LOM',
                                      'lom'      => 'http://ltsc.ieee.org/xsd/LOM',
                                      'voc'      => 'http://ltsc.ieee.org/xsd/LOM/vocab',
                                      'xsi'      => 'http://www.w3.org/2001/XMLSchema-instance',
                                      'cc'       => 'http://www.imsglobal.org/xsd/imsccauth_v1p0');

    function __construct ($path_to_manifest) {

        static::$manifest = new DOMDocument();
        static::$manifest->validateOnParse = false;

        static::$path_to_manifest_folder = dirname($path_to_manifest);

        static::log_action('Proccess start');
        static::log_action('Load the manifest file: ' . $path_to_manifest);

        if (!static::$manifest->load($path_to_manifest, LIBXML_NONET)) {
            static::log_action('Cannot load the manifest file: ' . $path_to_manifest, true);
        }
    }

    public function is_auth () {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $count_auth = $xpath->evaluate('count(/imscc:manifest/cc:authorizations)');

        if ($count_auth > 0) {
            $response = true;
        } else {
            $response = false;
        }

        return $response;
    }

    protected function get_metadata ($section, $key) {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $metadata = $xpath->query('/imscc:manifest/imscc:metadata/lomimscc:lom/lomimscc:' . $section . '/lomimscc:' . $key . '/lomimscc:string');
        $value = !empty($metadata->item(0)->nodeValue) ? $metadata->item(0)->nodeValue : '';

        return $value;
    }

    public function generate_moodle_xml () {

        global $CFG, $OUTPUT;

        $cdir = static::$path_to_manifest_folder . DIRECTORY_SEPARATOR . 'course_files';

        if (!file_exists($cdir)) {
            mkdir($cdir, $CFG->directorypermissions, true);
        }

        $sheet_base = static::loadsheet(SHEET_BASE);

        // MOODLE_BACKUP / INFO / DETAILS / MOD
        $node_info_details_mod = $this->create_code_info_details_mod();

        // MOODLE_BACKUP / BLOCKS / BLOCK
        $node_course_blocks_block = $this->create_node_course_blocks_block();

        // MOODLE_BACKUP / COURSES / SECTIONS / SECTION
        $node_course_sections_section = $this->create_node_course_sections_section();

        // MOODLE_BACKUP / COURSES / QUESTION_CATEGORIES
        $node_course_question_categories = $this->create_node_question_categories();

        // MOODLE_BACKUP / COURSES / MODULES / MOD
        $node_course_modules_mod = $this->create_node_course_modules_mod();

        // MOODLE_BACKUP / COURSE / HEADER
        $node_course_header = $this->create_node_course_header();

        // GENERAL INFO
        $filename = optional_param('file', 'not_available.zip', PARAM_RAW);
        $filename = basename($filename);

        $www_root = $CFG->wwwroot;

        $find_tags = array('[#zip_filename#]',
                           '[#www_root#]',
                           '[#node_course_header#]',
                           '[#node_info_details_mod#]',
                           '[#node_course_blocks_block#]',
                           '[#node_course_sections_section#]',
                           '[#node_course_question_categories#]',
                           '[#node_course_modules#]');

        $replace_values = array($filename,
                                $www_root,
                                $node_course_header,
                                $node_info_details_mod,
                                $node_course_blocks_block,
                                $node_course_sections_section,
                                $node_course_question_categories,
                                $node_course_modules_mod);

        $result_xml = str_replace($find_tags, $replace_values, $sheet_base);

        // COPY RESOURSE FILES
        $entities = new entities();

        $entities->move_all_files();

        if (array_key_exists("index", self::$instances)) {

            if (!file_put_contents(static::$path_to_manifest_folder . DIRECTORY_SEPARATOR . 'moodle.xml', $result_xml)) {
                static::log_action('Cannot save the moodle manifest file: ' . static::$path_to_tmp_folder . DIRECTORY_SEPARATOR . 'moodle.xml', true);
            } else {
                $status = true;
            }

        } else {
            $status = false;
            echo $OUTPUT->notification('The course is empty');
            static::log_action('The course is empty', false);
        }

        return $status;

    }

    protected function get_sections_numbers ($instances) {

        $count = 0;

        if (array_key_exists("index", $instances)) {
            foreach ($instances["index"] as $instance) {
                if ($instance["deep"] == ROOT_DEEP) {
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function create_node_course_header () {

        $node_course_header = '';
        $sheet_course_header = static::loadsheet(SHEET_COURSE_HEADER);

        $course_title = trim($this->get_metadata('general', 'title'));
        $course_title = empty($course_title) ? 'Untitled Course' : $course_title;
        $course_description = $this->get_metadata('general', 'description');
        $section_count = $this->get_sections_numbers(static::$instances) - 1;

        if ($section_count == -1) {
            $section_count = 0;
        }

        if (empty($course_title)) {
            $this->log_action('The course title not found', true);
        }

        $course_short_name = $this->create_course_code($course_title);

        $find_tags = array('[#course_name#]',
                           '[#course_short_name#]',
                           '[#course_description#]',
                           '[#date_now#]',
                           '[#section_count#]');

        $replace_values = array(entities::safexml($course_title),
                                entities::safexml($course_short_name),
                                entities::safexml($course_description),
                                time(),
                                $section_count);

        $node_course_header = str_replace($find_tags, $replace_values, $sheet_course_header);

        return $node_course_header;
    }

    protected function create_node_question_categories () {

        $quiz = new cc_quiz();

        static::log_action('Creating node: QUESTION_CATEGORIES');

        $node_course_question_categories = $quiz->generate_node_question_categories();

        return $node_course_question_categories;
    }

    protected function create_node_course_modules_mod () {

        $labels = new cc_label();
        $resources = new cc_resource();
        $forums = new cc_forum();
        $quiz = new cc_quiz();

        static::log_action('Creating node: COURSE/MODULES/MOD');

        // LABELS
        $node_course_modules_mod_label = $labels->generate_node();

        // RESOURCES (WEB CONTENT AND WEB LINK)
        $node_course_modules_mod_resource = $resources->generate_node();

        // FORUMS
        $node_course_modules_mod_forum = $forums->generate_node();

        // QUIZ
        $node_course_modules_mod_quiz = $quiz->generate_node_course_modules_mod();
        //TODO: label
        $node_course_modules = $node_course_modules_mod_label . $node_course_modules_mod_resource . $node_course_modules_mod_forum . $node_course_modules_mod_quiz;

        return $node_course_modules;
    }


    protected function create_node_course_sections_section () {

        static::log_action('Creating node: COURSE/SECTIONS/SECTION');

        $node_course_sections_section = '';
        $sheet_course_sections_section = static::loadsheet(SHEET_COURSE_SECTIONS_SECTION);

        $topics = $this->get_nodes_by_criteria('deep', ROOT_DEEP);

        $i = 0;

        if (!empty($topics)) {

            foreach ($topics as $topic) {

                $i++;
                $node_node_course_sections_section_mods_mod = $this->create_node_course_sections_section_mods_mod($topic['index']);

                if ($topic['moodle_type'] == MOODLE_TYPE_LABEL) {

                    $find_tags = array('[#section_id#]',
                                       '[#section_number#]',
                                       '[#section_summary#]',
                                       '[#node_course_sections_section_mods_mod#]');

                    $replace_values = array($i,
                                            $i - 1,
                                            entities::safexml($topic['title']),
                                            $node_node_course_sections_section_mods_mod);

                } else {

                    $find_tags = array('[#section_id#]',
                                       '[#section_number#]',
                                       '[#section_summary#]',
                                       '[#node_course_sections_section_mods_mod#]');

                    $replace_values = array($i,
                                            $i - 1,
                                            '',
                                            $node_node_course_sections_section_mods_mod);

                }

                $node_course_sections_section .= str_replace($find_tags, $replace_values, $sheet_course_sections_section);
            }
        }


        return $node_course_sections_section;
    }

    protected function create_node_course_blocks_block () {

        global $CFG;

        static::log_action('Creating node: COURSE/BLOCKS/BLOCK');

        $sheet_course_blocks_block = static::loadsheet(SHEET_COURSE_BLOCKS_BLOCK);
        $node_course_blocks_block = '';

        $format_config = $CFG->dirroot . '/course/format/weeks/config.php';

        if (@is_file($format_config) && is_readable($format_config)) {
            require ($format_config);
        }

        if (!empty($format['defaultblocks'])) {
            $blocknames = $format['defaultblocks'];
        } else {
            if (isset($CFG->defaultblocks)) {
                $blocknames = $CFG->defaultblocks;
            } else {
                $blocknames = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
            }
        }

        $blocknames = explode(':', $blocknames);
        $blocks_left = explode(',', $blocknames[0]);
        $blocks_right = explode(',', $blocknames[1]);

        $find_tags = array('[#block_id#]',
                           '[#block_name#]',
                           '[#block_position#]',
                           '[#block_weight#]');

        $i = 0;
        $weight = 0;

        foreach ($blocks_left as $block) {
            $i++;
            $weight++;

            $replace_values = array($i,
                                    $block,
                                    'l',
                                    $weight);

            $node_course_blocks_block .= str_replace($find_tags, $replace_values, $sheet_course_blocks_block);
        }

        $weight = 0;

        foreach ($blocks_right as $block) {

            $i++;
            $weight ++;

            $replace_values = array($i,
                                    $block,
                                    'r',
                                    $weight);

            $node_course_blocks_block .= str_replace($find_tags, $replace_values, $sheet_course_blocks_block);
        }

        return $node_course_blocks_block;

    }

    /**
    *
    * Is activity visible or not
    * @param string $identifier
    * @return number
    */
    protected function get_module_visible($identifier) {
        //Should item be hidden or not
        $mod_visible = 1;
        if (!empty($identifier)) {
            $xpath = static::newx_path(static::$manifest, static::$namespaces);
            $query  = '/imscc:manifest/imscc:resources/imscc:resource[@identifier="' . $identifier . '"]';
            $query .= '//lom:intendedEndUserRole/voc:vocabulary/lom:value';
            $intendeduserrole = $xpath->query($query);
            if (!empty($intendeduserrole) && ($intendeduserrole->length > 0)) {
                $role = trim($intendeduserrole->item(0)->nodeValue);
                if (strcasecmp('Instructor', $role) == 0) {
                    $mod_visible = 0;
                }
            }
        }
        return $mod_visible;
    }

    protected function create_node_course_sections_section_mods_mod ($root_parent) {

        $sheet_course_sections_section_mods_mod = static::loadsheet(SHEET_COURSE_SECTIONS_SECTION_MODS_MOD);
        $childs = $this->get_nodes_by_criteria('root_parent', $root_parent);

        if ($childs) {

            $node_course_sections_section_mods_mod = '';

            foreach ($childs as $child) {

                if ($child['moodle_type'] == MOODLE_TYPE_LABEL) {
                    if ($child['index'] == $child['root_parent']) {
                        $is_summary = true;
                    } else {
                        $is_summary = false;
                    }
                } else {
                    $is_summary = false;
                }

                if (!$is_summary) {

                    $indent = $child['deep'] - ROOT_DEEP;

                    if ($indent > 0) {
                        $indent = $indent - 1;
                    }

                    $find_tags = array('[#mod_id#]',
                                       '[#mod_instance_id#]',
                                       '[#mod_type#]',
                                       '[#date_now#]',
                                       '[#mod_indent#]',
                                       '[#mod_visible#]');

                    $replace_values = array($child['index'],
                                            $child['instance'],
                                            $child['moodle_type'],
                                            time(),
                                            $indent,
                                            $this->get_module_visible($child['resource_indentifier']));

                    $node_course_sections_section_mods_mod .= str_replace($find_tags, $replace_values, $sheet_course_sections_section_mods_mod);
                }
            }

            $response = $node_course_sections_section_mods_mod;

        } else {
            $response = '';
        }

        return $response;

    }

    public function get_nodes_by_criteria ($key, $value) {

        $response = array();

        if (array_key_exists('index', static::$instances)) {
            foreach (static::$instances['index'] as $item) {
                if ($item[$key] == $value) {
                    $response[] = $item;
                }
            }
        }

        return $response;
    }

    //Modified here
    protected function create_code_info_details_mod () {

        static::log_action('Creating node: INFO/DETAILS/MOD');

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $items = $xpath->query('/imscc:manifest/imscc:organizations/imscc:organization/imscc:item | /imscc:manifest/imscc:resources/imscc:resource[@type="' . static::CC_TYPE_QUESTION_BANK . '"]');

        $this->create_instances($items);

        $count_quiz = $this->count_instances(MOODLE_TYPE_QUIZ);
        $count_forum = $this->count_instances(MOODLE_TYPE_FORUM);
        $count_resource = $this->count_instances(MOODLE_TYPE_RESOURCE);
        $count_label = $this->count_instances(MOODLE_TYPE_LABEL);

        $sheet_info_details_mod_instances_instance = static::loadsheet(SHEET_INFO_DETAILS_MOD_INSTANCE);

        if ($count_resource > 0) {
            $resource_instance = $this->create_mod_info_details_mod_instances_instance($sheet_info_details_mod_instances_instance, $count_resource, static::$instances['instances'][MOODLE_TYPE_RESOURCE]);
        }
        if ($count_quiz > 0) {
            $quiz_instance = $this->create_mod_info_details_mod_instances_instance($sheet_info_details_mod_instances_instance, $count_quiz, static::$instances['instances'][MOODLE_TYPE_QUIZ]);
        }
        if ($count_forum > 0) {
            $forum_instance = $this->create_mod_info_details_mod_instances_instance($sheet_info_details_mod_instances_instance, $count_forum, static::$instances['instances'][MOODLE_TYPE_FORUM]);
        }
        if ($count_label > 0) {
            $label_instance = $this->create_mod_info_details_mod_instances_instance($sheet_info_details_mod_instances_instance, $count_label, static::$instances['instances'][MOODLE_TYPE_LABEL]);
        }

        $resource_mod = $count_resource ? $this->create_mod_info_details_mod(MOODLE_TYPE_RESOURCE, $resource_instance) : '';
        $quiz_mod = $count_quiz ? $this->create_mod_info_details_mod(MOODLE_TYPE_QUIZ, $quiz_instance) : '';
        $forum_mod = $count_forum ? $this->create_mod_info_details_mod(MOODLE_TYPE_FORUM, $forum_instance) : '';
        $label_mod = $count_label ? $this->create_mod_info_details_mod(MOODLE_TYPE_LABEL, $label_instance) : '';

        //TODO: label
        return $label_mod . $resource_mod . $quiz_mod . $forum_mod;

    }

    protected function create_mod_info_details_mod ($mod_type, $node_info_details_mod_instances_instance) {

        $sheet_info_details_mod = static::loadsheet(SHEET_INFO_DETAILS_MOD);

        $find_tags = array('[#mod_type#]' ,'[#node_info_details_mod_instances_instance#]');
        $replace_values = array($mod_type , $node_info_details_mod_instances_instance);

        return str_replace($find_tags, $replace_values, $sheet_info_details_mod);
    }

    protected function create_mod_info_details_mod_instances_instance ($sheet, $instances_quantity, $instances) {

        $instance = '';

        $find_tags = array('[#mod_instance_id#]',
                           '[#mod_name#]',
                           '[#mod_user_info#]');

        for ($i = 1; $i <= $instances_quantity; $i++) {

            $user_info = ($instances[$i - 1]['common_cartriedge_type'] == static::CC_TYPE_FORUM) ? 'true' : 'false';
            if ($instances[$i - 1]['common_cartriedge_type'] == static::CC_TYPE_EMPTY) {
                if ($instances[$i - 1]['deep'] <= ROOT_DEEP ) {
                    continue;
                }
            }

            $replace_values = array($instances[$i - 1]['instance'],
                                    entities::safexml($instances[$i - 1]['title']),
                                    $user_info);

            $instance .= str_replace($find_tags, $replace_values, $sheet);
        }

        return $instance;

    }

    protected function create_instances ($items, $level = 0, &$array_index = 0, $index_root = 0) {

        $level++;
        $i = 1;

        if ($items) {

            $xpath = self::newx_path(static::$manifest, static::$namespaces);

            foreach ($items as $item) {

                $array_index++;

                if ($item->nodeName == "item")  {
                    $identifierref = '';
                    if ($item->hasAttribute('identifierref')) {
                      $identifierref = $item->getAttribute('identifierref');
                    }

                    $title = '';
                    $titles = $xpath->query('imscc:title', $item);
                    if ($titles->length > 0) {
                        $title = $titles->item(0)->nodeValue;
                    }

                    $cc_type = $this->get_item_cc_type($identifierref);
                    $moodle_type = $this->convert_to_moodle_type($cc_type);
                    //Fix the label issue - MDL-33523
                    if (empty($identifierref) && empty($title)) {
                      $moodle_type = TYPE_UNKNOWN;
                    }
                }
                elseif ($item->nodeName == "resource")  {

                    $identifierref = $xpath->query('@identifier', $item);
                    $identifierref = !empty($identifierref->item(0)->nodeValue) ? $identifierref->item(0)->nodeValue : '';

                    $cc_type = $this->get_item_cc_type($identifierref);
                    $moodle_type = $this->convert_to_moodle_type($cc_type);

                    $title = 'Quiz Bank ' . ($this->count_instances($moodle_type) + 1);

                }

                if ($level == ROOT_DEEP) {
                    $index_root = $array_index;
                }

                static::$instances['index'][$array_index]['common_cartriedge_type'] = $cc_type;
                static::$instances['index'][$array_index]['moodle_type'] = $moodle_type;
                static::$instances['index'][$array_index]['title'] = $title ? $title : '';
                static::$instances['index'][$array_index]['root_parent'] = $index_root;
                static::$instances['index'][$array_index]['index'] = $array_index;
                static::$instances['index'][$array_index]['deep'] = $level;
                static::$instances['index'][$array_index]['instance'] = $this->count_instances($moodle_type);
                static::$instances['index'][$array_index]['resource_indentifier'] = $identifierref;

                static::$instances['instances'][$moodle_type][] = array('title' => $title,
                                                                        'instance' => static::$instances['index'][$array_index]['instance'],
                                                                        'common_cartriedge_type' => $cc_type,
                                                                        'resource_indentifier' => $identifierref,
                                                                        'deep' => $level);

                $more_items = $xpath->query('imscc:item', $item);

                if ($more_items->length > 0) {
                    $this->create_instances($more_items, $level, $array_index, $index_root);
                }

                $i++;

            }
        }
    }

    public function count_instances ($type) {

        $quantity = 0;

        if (array_key_exists('index', static::$instances)) {
            if (static::$instances['index'] && $type) {

                foreach (static::$instances['index'] as $instance) {
                    if (!empty($instance['moodle_type'])) {
                        $types[] = $instance['moodle_type'];
                    }
                }

                $quantity_instances = array_count_values($types);
                $quantity = array_key_exists($type, $quantity_instances) ? $quantity_instances[$type] : 0;
            }
        }

        return $quantity;
    }

    public function convert_to_moodle_type ($cc_type) {
        $type = TYPE_UNKNOWN;

        if ($cc_type == static::CC_TYPE_FORUM) {
            $type = MOODLE_TYPE_FORUM;
        }

        if ($cc_type == static::CC_TYPE_QUIZ) {
            $type = MOODLE_TYPE_QUIZ;
        }

        if ($cc_type == static::CC_TYPE_WEBLINK) {
            $type = MOODLE_TYPE_RESOURCE;
        }

        if ($cc_type == static::CC_TYPE_WEBCONTENT) {
            $type = MOODLE_TYPE_RESOURCE;
        }

        if ($cc_type == static::CC_TYPE_ASSOCIATED_CONTENT) {
            $type = MOODLE_TYPE_RESOURCE;
        }

        if ($cc_type == static::CC_TYPE_QUESTION_BANK) {
            $type = MOODLE_TYPE_QUESTION_BANK;
        }
        //TODO: label
        if ($cc_type == static::CC_TYPE_EMPTY) {
            $type = MOODLE_TYPE_LABEL;
        }

        return $type;
    }

    public function get_item_cc_type ($identifier) {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $nodes = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="' . $identifier . '"]/@type');

        if ($nodes && !empty($nodes->item(0)->nodeValue)) {
            return $nodes->item(0)->nodeValue;
        } else {
            return '';
        }
    }

    public static function newx_path (DOMDocument $manifest, $namespaces = '') {

        $xpath = new DOMXPath($manifest);

        if (!empty($namespaces)) {
            foreach ($namespaces as $prefix => $ns) {
                if (!$xpath->registerNamespace($prefix, $ns)) {
                    static::log_action('Cannot register the namespace: ' . $prefix . ':' . $ns, true);
                }
            }
        }

        return $xpath;
    }

    public static function loadsheet ($file) {

        $content = (is_readable($file) && ($content = file_get_contents($file))) ? $content : false;

        static::log_action('Loading sheet: ' . $file);

        if (!$content) {
            static::log_action('Cannot load the xml sheet: ' . $file, true);
        }

        static::log_action('Load OK!');

        return $content;
    }

    public static function log_file() {
        return static::$path_to_manifest_folder . DIRECTORY_SEPARATOR . 'cc2moodle.log';
    }

    public static function log_action ($text, $critical_error = false) {

        $full_message = strtoupper(date("j/n/Y g:i:s a")) . " - " . $text . "\r";

        file_put_contents(static::log_file(), $full_message, FILE_APPEND);

        if ($critical_error) {
            static::critical_error($text);
        }
    }

    protected function critical_error ($text) {

        $path_to_log = static::log_file();

        echo '

        <p>
        <hr />A critical error has been found!

        <p>' . $text . '</p>


        <p>
        The process has been stopped. Please see the <a href="' . $path_to_log . '">log file</a> for more information.</p>

        <p>Log: ' . $path_to_log . '</p>

        <hr />

        </p>
        ';

        die();
    }

    protected function create_course_code ($title) {
        //Making sure that text of the short name does not go over the DB limit.
        //and leaving the space to add additional characters by the platform
        $code = substr(strtoupper(str_replace(' ', '', trim($title))),0,94);
        return $code;
    }
}
