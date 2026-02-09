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
 * File contains definition of designer pro options for activities and sections
 *
 * @package    local_designer
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

use context_course;
use stdClass;

/**
 * Module and section options and view renderers defined.
 */
class options {

    /**
     * Get the module progress and backgroundimage
     * @param object $mod
     */
    public static function get_module_backgroundimage($mod) {
        $options = self::get_options($mod->id);
        $modulebackimageurl = local_designer_get_module_bgimage($mod, $options->backimage, $options);
        if ($mod->modname == 'videotime') {
            $videotime = info::videotime_background_image($mod, $options);
            $modulebackimageurl = isset($videotime['videourl']) ? $videotime['videourl'] : $modulebackimageurl;
        } else if ($mod->modname == 'subcourse') {
            $subcourse = info::subcourse_background_image($mod, $options);
            $modulebackimageurl = isset($subcourse['videourl']) ? $subcourse['videourl'] : $modulebackimageurl;
        }
        return $modulebackimageurl;
    }

    /**
     * Generate the data for render the course module.
     *
     * @param cminfo $mod Module information.
     * @param array $cmlist Module data to render template.
     * @param stdclass $section Section record data.
     * @return array Data content.
     */
    public static function render_course_module($mod, &$cmlist, $section) {
        $promodcontent = $modclasses = [];
        $options = self::get_options($mod->id);
        if ($options) {
            $promodcontent['prostyle'] = isset($options->minheight) ? 'min-height:'.$options->minheight.';' : '';

            $modulebackimageurl = local_designer_get_module_bgimage($mod, $options->backimage, $options);
            if ($mod->modname == 'videotime') {
                $videotime = info::videotime_background_image($mod, $options);
                $modulebackimageurl = isset($videotime['videourl']) ? $videotime['videourl'] : $modulebackimageurl;
                $modclasses[] = isset($videotime['videourl']) ? 'hide-videotime-preview-image' : '';
                $cmlist['moduleprogress'] = isset($videotime['progress']) ? $videotime['progress'] : '';
            } else if ($mod->modname == 'subcourse') {
                $subcourse = info::subcourse_background_image($mod, $options);
                $modulebackimageurl = isset($subcourse['videourl']) ? $subcourse['videourl'] : $modulebackimageurl;
                $cmlist['moduleprogress'] = isset($subcourse['progress']) ? $subcourse['progress'] : '';
            }

            $modulebackgroundstyle = '';
            if ($modulebackimageurl) {
                $modulebackgroundstyle = self::get_background_styles($modulebackimageurl, $options);
            }
            $promodcontent['modulebackgroundstyle'] = $modulebackgroundstyle;

            $backgradient = (isset($options->backgradient) && ($options->backgradient))
                        ? str_replace(';', '', $options->backgradient) : null;
            if ($backgradient) {
                $promodcontent['modbackoverlaycolor'] = sprintf('background: %s;', $backgradient);
                $modclasses[] = ' bg-color-overlay';
            }

            $moduletextcolor = '';
            // Text Color.
            if ($options->textcolor) {
                $moduletextcolor = "color: $options->textcolor" . ";";
            }
            $promodcontent['moduletextcolor'] = $moduletextcolor;
            // Activity mask css.
            $mask = (object) $options->maskstyle ?? [];

            if (($section->sectiontype != 'circles' && $section->sectiontype != 'horizontal_circles' )
                && isset($mask->image) && !empty($mask->image)) {
                $maskimage = self::get_mask_image($mask->image, LOCAL_DESIGNER_ACTIVITY_MASK_AREA);
                $position = format_designer_fill_custom_values($mask, 'position', 'customposition', '') ?: '';
                $size = format_designer_fill_custom_values($mask, 'size', 'customsize', '') ?: '50%';
                $promodcontent['modulebackgroundstyle'] .= self::genenrate_maskimage_styles($maskimage, $size, $position);
                $modclasses[] = 'bg-mask';
            }

            if (isset($modclasses)) {
                $cmlist['modclasses'] .= ' '.implode(' ', $modclasses);
            }
        }
        (new self)->load_layout_options_data($cmlist, $mod,  $section, 'activity');

        return $promodcontent;
    }

    /**
     * Get activity background styles.
     * @param string $imageurl Background image url
     * @param stdclass $options format designer options
     */
    public static function get_background_styles($imageurl, $options) {
        if ($imageurl) {
            $style = "background-image: url('" . $imageurl . "');";
            $bg = (isset($options->bgimagestyle) ? $options->bgimagestyle : []);

            $style .= format_designer_fill_custom_values((object) $bg, 'position', 'customposition', 'background-position');
            $style .= format_designer_fill_custom_values((object) $bg, 'size', 'customsize', 'background-size');

            $style .= (isset($bg['repeat']) && $bg['repeat']) ? 'background-repeat: repeat;' : 'background-repeat: no-repeat;';
            return $style;
        }
        return '';
    }

    /**
     * Get section mask image and mask position, size.
     *
     * @param stdclass $section Section record dataset.
     * @return void
     */
    public static function get_section_mask_image($section) {
        $maskimage = $section->sectiondesignermaskimage ?? '';

        if (!empty($maskimage)) {
            $maskimage = self::get_mask_image($maskimage, LOCAL_DESIGNER_SECTION_MASK_AREA);
            $position = format_designer_fill_custom_values($section, 'sectiondesignermaskposition',
                'sectiondesignercustom_maskposition', '') ?? '';
            $size = format_designer_fill_custom_values($section, 'sectiondesignermasksize',
            'sectiondesignercustom_masksize', '') ?? '50%';
            return self::genenrate_maskimage_styles($maskimage, $size, $position);
        }
        return false;
    }

    /**
     * Prepare editor files.
     *
     * @param [object] $defaultvalues
     * @param [object] $course
     * @return void
     */
    public static function prepare_sectioncardcta_editor_files($defaultvalues, $course) {
        $coursecontext = \context_course::instance($course->id);
        $sectioncardctaeditoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $course->maxbytes,
            'trust' => false,
            'context' => $coursecontext,
            'noclean' => true,
        ];
        $sectioncardctaeditoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'local_designer',
            'sectioncardcta', $defaultvalues->id);
        $defaultvalues = file_prepare_standard_editor($defaultvalues, 'sectioncardcta', $sectioncardctaeditoroptions,
            $coursecontext, 'local_designer', 'sectioncardcta', $defaultvalues->id);
        return $defaultvalues;
    }

    /**
     * Get the notification text color.
     *
     * @param [mixed] $color
     * @return void
     */
    public static function getnotificationtextcolor($color) {
        // Convert color to RGB format if it's in hexadecimal.
        if ($color[0] === '#') {
            $color = substr($color, 1);
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        } else {
            list($r, $g, $b) = sscanf($color, "rgb(%d, %d, %d)");
        }
        // Calculate the lightness of the color.
        $lightness = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        // Return black for lighter colors and white for darker colors.
        return $lightness > 128 ? '#000000' : '#ffffff';
    }

    /**
     * Generate the data set to renderer the section.
     *
     * @param stdclass $section
     * @param stdclass $course
     * @param Modinfo $modinfo
     * @param array $data Section data.
     * @return void
     */
    public static function render_section($section, $course, $modinfo, &$data) : void {
        global $PAGE;
        $format = course_get_format($course);
        $prosectionstyle = $classes = [];
        $classes[] = $data['sectionstyle'];
        // Layout columns and minheight not available for flow type.
        $classes[] = ' '.local_designer_layout_columnclasses($section);
        $context = \context_course::instance($course->id);
        $sectionnum = $format->get_sectionnum();
        // Check the single section sectiontitle.
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            if ($section->section != 0 && $section->section == $sectionnum) {
                // Inside section page.
                if ($section->sectioncardtitle == 'course') {
                    $data['hidesectiontitle'] = true;
                }
                if ($section->sectioncardsummary == 'course') {
                    $data['hidesectionsummary'] = true;
                }

            } else {
                // Inside course page.
                if ($section->sectioncardtitle == 'section') {
                    $data['hidesectiontitle'] = true;
                }

                if ($section->sectioncardsummary == 'section') {
                    $data['hidesectionsummary'] = true;
                }
            }

            if (!$section->uservisible && $section->availableinfo) {
                $data['sectioncardctacontent'] = file_rewrite_pluginfile_urls($section->sectioncardcta, 'pluginfile.php',
                $context->id, 'local_designer', 'sectioncardcta', $section->id);
            }
        }

        if (isset($section->hidesectiontitle) && $section->hidesectiontitle) {
            $data['hidesectiontitle'] = true;
        }

        // Min height for section.
        if (isset($section->sectionminheight) && $minheight = $section->sectionminheight) {
            $data['minheight'] = 'min-height:'.$minheight.';';
        }
        $data['sectionheaderstyle'] = '';
        if ($section->sectionbackgroundtype == 'whole') {
            $data['sectiondesignwhole'] = true;
            $classes[] = ' section-design-whole ';
            $prosectionstyle[] = $data['minheight'] ?? '';
        } else {
            $data['sectiondesignheader'] = true;
            $classes[] = 'section-design-header';
            $data['sectionheaderstyle'] = $data['minheight'] ?? '';
        }
        // Section designer background styles.
        $backgradient = (isset($section->sectiondesignerbackgradient) && ($section->sectiondesignerbackgradient))
                        ? str_replace(';', '', $section->sectiondesignerbackgradient) : null;
        if ($section->sectiondesignerbackgradient) {
            $classes[] = "section-background-overlay";
        }

        // Background color - Add bg color to section li or header content.
        $data['sectionbackgroundstyle'] = '';
        if ($section->sectiondesignerbackgroundcolor) {
            $backcolor = "background-color: $section->sectiondesignerbackgroundcolor" . ";";
            $data['sectionbackcolor'] = $backcolor;
            if ($course->coursetype != DESIGNER_TYPE_FLOW && $section->sectionbackgroundtype != 'header') {
                $prosectionstyle[] = ($course->coursetype == DESIGNER_TYPE_NORMAL && $course->coursedisplay ==
                                        COURSE_DISPLAY_MULTIPAGE) ? '' : $backcolor;
            } else {
                $data['sectionheaderstyle'] .= $backcolor;
            }
            $classes[] = 'section-background-color';
            if (!$course->coursetype == DESIGNER_TYPE_FLOW && empty(self::get_section_mask_image($section))) {
                $data['sectionbackgroundstyle'] .= $backcolor;
            }
        }

        // Section mask image.
        $mask = self::get_section_mask_image($section);
        if (!empty($mask)) {
            $data['sectionbackgroundstyle'] .= $mask;
            $classes[] = 'bg-mask';
        }

        // Get section designer background image.
        $sectiondesignerbackimageurl = format_designer_get_section_background_image($section, $course, $modinfo);
        // Background image, gredient and bakground color are filled.
        if (!empty($mask) && !empty($section->sectiondesignerbackgroundcolor)
            && !empty($sectiondesignerbackimageurl) && !empty($section->sectiondesignerbackgradient)) {
            $data['maskinner'] = sprintf('background: %s;', $section->sectiondesignerbackgradient);
            $classes[] = 'bg-mask-overlay';
            $ismaskinner = true;
        }

        if ($sectiondesignerbackimageurl) {
            $classes[] = 'section-header-image';
            if ($backgradient) {
                $data['sectionbackgroundstyle'] .= sprintf('background-image: url(%s);', $sectiondesignerbackimageurl);
                $overlaycolor = sprintf('background: %s;', $backgradient );
            } else {
                $data['sectionbackgroundstyle'] .= "background-image: url('" . $sectiondesignerbackimageurl . "');";
            }
            // Check background gradient is not added inner of mask.
            if (!isset($ismaskinner)) {
                $data['bgoverlay'] = (isset($overlaycolor)) ? $overlaycolor : false;
                $classes[] = (isset($overlaycolor)) ? ' bg-color-overlay' : '';
            }

            $data['sectionbackgroundstyle'] .= format_designer_fill_custom_values($section, 'sectiondesignerbgposition',
            'sectiondesignercustombgposition', 'background-position');
            $data['sectionbackgroundstyle'] .= format_designer_fill_custom_values($section, 'sectiondesignerbgsize',
            'sectiondesignercustombgsize', 'background-size');
            $data['sectionbackgroundstyle'] .= (isset($section->sectiondesignerbgrepeat) && $section->sectiondesignerbgrepeat)
                    ? 'background-repeat: repeat;' : 'background-repeat: no-repeat;';
        } else if ($section->sectiondesignerbackgradient) {
            $gradient = $section->sectiondesignerbackgradient;
            $data['sectionbackgroundstyle'] .= sprintf('background: %s;', $gradient);
        }

        if ($section->sectiondesignertextcolor) {
            $data['sectiondesigntextcolor'] = "color: $section->sectiondesignertextcolor"
                . ";--sectioncolor:  $section->sectiondesignertextcolor;";
        }

        if ($section->sectiondesignertextcolor) {
            $textcolor = $section->sectiondesignertextcolor;
            $modulecounttextcolor = self::getnotificationtextcolor($textcolor);
            $data['sectiontextcolor'] = "color: $section->sectiondesignertextcolor";

            // Create style rules for section activity icons.
            $moduleimgstyles = "color:$textcolor;fill:$textcolor;border-color:$textcolor;";
            $moduleimgcountstyle = "background-color:$textcolor;color:$modulecounttextcolor";

            $data['sectionmoduleimgstyle'] = $moduleimgstyles;
            $data['sectionactivitycountstyle'] = $moduleimgcountstyle;
        }

        // Section container & content layout.
        $containerlayout = (isset($section->layoutcontainer)) ? $section->layoutcontainer : '';
        $data['sectioncontainerwidth'] = '';
        if ($containerlayout == 'full') {
            $data['sectioncontainerlayout'] = 'container-full';
        } else if ($containerlayout == 'boxed') {
            $data['sectioncontainerlayout'] = 'container-boxed';
            $sectioncontainerboxwidth = ($section->layoutcontainerwidth) ? $section->layoutcontainerwidth : '1200';
            $data['sectioncontainerwidth'] .= 'max-width:'. $sectioncontainerboxwidth. "px;";
        } else {
            $data['sectioncontainerlayout'] = "container";
        }
        $classes[] = $data['sectioncontainerlayout'];
        $prosectionstyle[] = $data['sectioncontainerwidth'];

        $data['sectioncontentwidth'] = '';
        $contentlayout = isset($section->layoutcontent) ? $section->layoutcontent : '';
        if ($contentlayout == 'boxed') {
            $data['sectioncontentlayout'] = 'content-boxed';
            $sectioncontentboxwidth = ($section->layoutcontentwidth) ? $section->layoutcontentwidth : '1200';
            $data['sectioncontentwidth'] .= 'max-width: '. $sectioncontentboxwidth . "px;";
        } else {
            $data['sectioncontentlayout'] = 'content-normal';
        }
        $data['sectionstyle'] = implode(' ', $classes);
        $data['stylerules'] .= implode(' ', $prosectionstyle);
        (new self)->load_layout_options_data($data, [], $section, 'section');
    }

    /**
     * Include the layout sub components options module and section element to form.
     *
     * @param stdclass $data
     * @param cminfo $mod
     * @param stdclass $section
     * @param string $type
     * @return void
     */
    public function load_layout_options_data(&$data, $mod, $section, $type='section') {
        global $CFG;
        $prolayouts = format_designer_get_pro_layouts();
        if (in_array($section->sectiontype, $prolayouts)) {
            $layoutsectionfunc = 'layouts_'.$section->sectiontype.'_'.$type.'_data';
            if (file_exists($CFG->dirroot.'/local/designer/layouts/'.$section->sectiontype.'/lib.php')) {
                require_once($CFG->dirroot.'/local/designer/layouts/'.$section->sectiontype.'/lib.php');
                if (function_exists($layoutsectionfunc)) {
                    $layoutsectionfunc($data, $mod, $section);
                }
            }
        }
    }

    /**
     * Get pro feature values for activities.
     *
     * @param int $cmid course module id.
     * @return stdclass Data for additional fields.
     */
    public static function get_options($cmid) {
        $fields = [
            'backimage',
            'backgradient',
            'textcolor',
            'minheight',
            'bgimagestyle',
            'completionbackimage',
            'usecompletionbg',
            'maskstyle',
            'useactivityimage',
            'subcourseuseactivityimage',
            'subcoursedisplayprogress',
            'displayprogress',
            'purpose',
        ];
        $fields = array_fill_keys(array_values($fields), null);
        $options = \format_designer\options::get_options($cmid);

        return (object) array_merge($fields, (array) $options);
    }

    /**
     * Get available mask images for sections.
     *
     * @return array List of available mask images.
     */
    public function get_section_mask_images() {
        global $CFG;
        $results = [ 0 => get_string('none') ];
        require_once($CFG->libdir.'/filelib.php');
        $fs = get_file_storage();
        $maskimages = $fs->get_area_files(
            \context_system::instance()->id,
            'local_designer', LOCAL_DESIGNER_SECTION_MASK_AREA, 0, '', false);
        foreach ($maskimages as $image) {
            $results[$image->get_id()] = ucwords(explode('.', $image->get_filename())[0]);
        }
        return $results;
    }

    /**
     * Get available list of all activity mask images.
     *
     * @return array $results List of mask images.
     */
    public static function get_activity_mask_images() {
        global $CFG;
        $results = [ 0 => get_string('none') ];
        require_once($CFG->libdir.'/filelib.php');
        $fs = get_file_storage();
        $maskimages = $fs->get_area_files(
            \context_system::instance()->id, 'local_designer', LOCAL_DESIGNER_ACTIVITY_MASK_AREA, 0, '', false
        );
        foreach ($maskimages as $image) {
            $results[$image->get_id()] = ucwords(explode('.', $image->get_filename())[0]);
        }
        return $results;
    }

    /**
     * Get image file url of the given itemid.
     *
     * @param [type] $itemid
     * @param [type] $filearea
     * @return void
     */
    public static function get_mask_image($itemid, $filearea) {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($itemid);
        if (empty($file)) {
            return '';
        }
        if ($file->is_valid_image()) {
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(), false
            );
            return $url->out(false);
        }
    }

    /**
     * Generate the css style rules for the mask image.
     *
     * @param string $maskimage Maks image URL
     * @param string $size  Mask Sizee.
     * @param string $position Mask position "Center Left"
     * @return string Css rules for mask images.
     */
    public static function genenrate_maskimage_styles($maskimage, $size='', $position='right bottom') {
        global $CFG;
        $position = $position ?: 'right bottom';
        $styles[] = sprintf('-webkit-mask-image: url(%s);', $maskimage);
        $styles[] = sprintf('mask-image: url(%s);', $maskimage);
        $styles[] = sprintf('-webkit-mask-position: %s;', $position);
        $styles[] = sprintf('mask-position: %s;', $position);
        $styles[] = sprintf('-webkit-mask-size: %s;', $size);
        $styles[] = sprintf('mask-size: %s;', $size);
        $styles[] = '-webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;';
        return implode('', $styles);
    }

    /**
     * Process the pro sections options.
     * @param bool $foreditform Is the options fetched for build the edit Form.
     * @return array List of section options.
     */
    public function get_section_options($foreditform=false) {
        global $COURSE, $SITE;
        $layoutsectionoptions = $this->load_layout_section_options();
        $sectionbackgrounddesigns = $this->get_section_background_options();
        $sectioncategorise = $this->get_section_categorise_options();
        $sectioncontentlayouts = $this->get_section_content_layouts();
        if (isset($layoutsectionoptions['layout']) && !empty($layoutsectionoptions['layout'])) {
            $sectioncontentlayouts = array_merge($sectioncontentlayouts,  $layoutsectionoptions['layout']);
        }
        $options = array_merge($sectioncontentlayouts, $sectionbackgrounddesigns, $sectioncategorise);
        if ($COURSE->id == $SITE->id) {
            $sectioncardcontents = $this->get_section_card_contents();
            $options = array_merge($options, $sectioncardcontents);
        } else {
            $course = course_get_format($COURSE->id)->get_course();
            if (isset($course->coursedisplay) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $sectioncardcontents = $this->get_section_card_contents();
                $options = array_merge($options, $sectioncardcontents);
            }
        }
        $design = \format_designer\options::get_default_options(true);
        foreach ($options as $option => $value) {
            if (isset($design->$option)) {
                $options[$option]['default'] = $design->$option;
            }
            $adv = $option.'_adv';
            if (isset($design->$adv) && $design->$adv) {
                $options[$option]['adv'] = true;
            }
        }
        return $options;
    }

    /**
     * Get section card contents.
     *
     * @return array
     */
    public function get_section_card_contents() {
        global $CFG;
        $categoriseoptions = [
            'sectioncardheader' => [
                'type' => PARAM_TEXT,
                'element_type' => 'header',
                'element_attributes' => [['class' => "categoriseheader-block"]],
                'default' => get_string('sectioncardheader', 'format_designer'),
                'label' => '',
            ],
            'sectioncardtitle' => [
                'type' => PARAM_TEXT,
                'label' => get_string('sectioncardtitle', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    0 => [
                        DESIGNERCOURSEANDSECTIONPAGE => get_string('displayoncourseandsectionpage' , 'format_designer'),
                        DESIGNERCOURSEPAGE => get_string('displayoncoursepage', 'format_designer'),
                        DESIGNERSECTIONPAGE => get_string('displayonsectionpageonly', 'format_designer'),
                    ],
                ],
            ],
            'sectioncardsummary' => [
                'type' => PARAM_TEXT,
                'label' => get_string('sectioncardsummary', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    0 => [
                        DESIGNERCOURSEANDSECTIONPAGE => get_string('displayoncourseandsectionpage' , 'format_designer'),
                        DESIGNERCOURSEPAGE => get_string('displayoncoursepage', 'format_designer'),
                        DESIGNERSECTIONPAGE => get_string('displayonsectionpageonly', 'format_designer'),
                    ],
                ],
            ],
            'sectioncardcta_editor' => [
                'type' => PARAM_RAW,
                'label' => get_string('sectioncardcta', 'format_designer'),
                'element_type' => 'editor',
                'element_attributes' => [
                    [
                        "rows" => "10",
                        "cols" => "50",
                    ],
                    [
                        "maxfiles" => -1,
                        "maxbytes" => $CFG->maxbytes,
                        'trusttext' => false,
                        'noclean' => true,
                        'enable_filemanagement' => true,
                    ],
                ],
            ],
            'sectioncardcta' => [
                'type' => PARAM_RAW,
                'label' => get_string('sectioncardcta', 'format_designer'),
                'element_type' => 'hidden',
            ],
            'sectioncardctaformat' => [
                'type' => PARAM_INT,
                'label' => get_string('sectioncardcta', 'format_designer'),
                'element_type' => 'hidden',
                'default' => FORMAT_HTML,
            ],
            'sectioncardredirect' => [
                'type' => PARAM_RAW,
                'label' => get_string('sectioncardredirect', 'format_designer'),
                'element_type' => 'text',
            ],
            'sectioncardtab' => [
                'type' => PARAM_INT,
                'label' => get_string('sectioncardtab', 'format_designer'),
                'element_type' => 'checkbox',
                'default' => 1,
            ],
        ];

        return $categoriseoptions;
    }

    /**
     * Get all layout options for sections.
     *
     * @return array List of settings as array.
     */
    public function load_layout_section_options() {
        global $CFG;
        $prolayouts = format_designer_get_pro_layouts();
        $layouts = [];
        if (is_array($prolayouts)) {
            foreach ($prolayouts as $sectiontype) {
                $layoutsectionfunc = 'layouts_'.$sectiontype.'_sectionoptions';
                if (file_exists($CFG->dirroot.'/local/designer/layouts/'.$sectiontype.'/lib.php')) {
                    require_once($CFG->dirroot.'/local/designer/layouts/'.$sectiontype.'/lib.php');
                    if (function_exists($layoutsectionfunc)) {
                        $options = $layoutsectionfunc();
                        if (!empty($options)) {
                            $layouts = array_merge($layouts, $options);
                        }
                    }
                }
            }
        }
        return $layouts;
    }

    /**
     * Get section type settings.
     *
     * @return array
     */
    public function get_section_categorise_options() {

        $categoriseoptions = [
            'sectioncategoriseheader' => [
                'type' => PARAM_TEXT,
                'element_type' => 'header',
                'element_attributes' => [['class' => "categoriseheader-block"]],
                'default' => get_string('categoriseheader', 'format_designer'),
                'label' => '',
            ],
            'categorisetitle' => [
                'type' => PARAM_TEXT,
                'label' => get_string('title', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [['size' => 30]],
            ],
            'categorisebackcolor' => [
                'type' => PARAM_RAW,
                'label' => get_string('backgroundcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
            ],
            'categorisetextcolor' => [
                'type' => PARAM_RAW,
                'label' => get_string('textcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
            ],
        ];
        return $categoriseoptions;
    }

    /**
     * Get section layout settings.
     *
     * @return array $contentlayouts
     */
    public function get_section_content_layouts() {
        global $PAGE;
        $layouts = [
            '1' => get_string('onecolumn', 'format_designer'),
            '2' => get_string('twocolumn', 'format_designer'),
            '3' => get_string('threecolumn', 'format_designer'),
            '4' => get_string('fourcolumn', 'format_designer'),
            '5' => get_string('fivecolumn', 'format_designer'),
        ];

        $settingspage = ($PAGE->course->id == SITEID);
        $course = course_get_format($PAGE->course)->get_course();

        $addsettings = ($settingspage || (isset($course->coursetype) && $course->coursetype != DESIGNER_TYPE_FLOW));
        $contentlayouts = [
            'sectionminheight' => [
                'type' => PARAM_ALPHANUMEXT,
                'label' => get_string('minheight', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [0 => ['class' => 'min-height', 'placeholder' => '200px or 4rem']],
            ],
        ];
        $contentlayouts += $addsettings ? [
            'layoutcontainer' => [
                'type' => PARAM_TEXT,
                'element_type' => 'select',
                'label' => get_string('sectioncontainer', 'format_designer'),
                'element_attributes' => [
                    0 => [
                        'normal' => get_string('normal' , 'format_designer'),
                        'full' => get_string('full', 'format_designer'),
                        'boxed' => get_string('boxed', 'format_designer'),
                    ],
                 ],
            ],
            'layoutcontainerwidth' => [
                'type' => PARAM_TEXT,
                'element_type' => 'text',
                'label' => get_string('sectioncontainerwidth', 'format_designer'),
                'disabledif' => ['layoutcontainer', 'neq', 'boxed'],
            ],
            'layoutcontent' => [
                'type' => PARAM_TEXT,
                'element_type' => 'select',
                'label' => get_string('sectioncontent', 'format_designer'),
                'element_attributes' => [
                    [
                        'normal' => get_string('normal' , 'format_designer'),
                        'boxed' => get_string('boxed', 'format_designer'),
                    ],
                ],
            ],
            'layoutcontentwidth' => [
                'type' => PARAM_TEXT,
                'element_type' => 'text',
                'label' => get_string('sectioncontentwidth', 'format_designer'),
                'disabledif' => ['layoutcontent', 'neq', 'boxed'],
            ],
        ] : [];
        $contentlayouts += [
            'layoutdesktopcolumn' => [
                'type' => PARAM_INT,
                'element_type' => 'select',
                'label' => get_string('desktopcolumn', 'format_designer'),
                'element_attributes' => [$layouts],
                'default' => '3',
            ],
            'layouttabletcolumn' => [
                'type' => PARAM_INT,
                'element_type' => 'select',
                'label' => get_string('tabletcolumn', 'format_designer'),
                'element_attributes' => [
                    array_filter($layouts, function($v, $k) {
                        return $k > 3 ? false : true;
                    }, ARRAY_FILTER_USE_BOTH),
                ],
                'default' => '2',
            ],
            'layoutmobilecolumn' => [
                'type' => PARAM_INT,
                'element_type' => 'select',
                'label' => get_string('mobilecolumn', 'format_designer'),
                'element_attributes' => [
                    array_filter($layouts, function($v, $k) {
                         return $k > 2 ? false : true;
                    }, ARRAY_FILTER_USE_BOTH),
                ],
                'default' => '1',
            ],
        ];
        return $contentlayouts;
    }

    /**
     * Get section background options.
     *
     * @return array
     */
    public function get_section_background_options() {
        global $PAGE;
        $PAGE->requires->js_call_amd("local_designer/designer", "init");
        $course = course_get_format($PAGE->course)->get_course();
        $sectionbackgroundtypeoptions = [
            'header' => get_string('sectionheader', 'format_designer'),
            'whole' => get_string('wholesection', 'format_designer'),
        ];
        if (isset($course->coursetype) && $course->coursetype == DESIGNER_TYPE_FLOW) {
            unset($sectionbackgroundtypeoptions['whole']);
        }
        $maskimages = $this->get_section_mask_images();
        $backgrounddesignoptions = [
            'sectiondesignheader' => [
                'type' => PARAM_TEXT,
                'element_type' => 'header',
                'label' => '',
                'default' => get_string('sectiondesignheader', 'format_designer'),
            ],

            'hidesectiontitle' => [
                'type' => PARAM_INT,
                'label' => get_string('hidesectiontitle', 'format_designer'),
                'element_type' => 'checkbox',
            ],

            'sectionbackgroundtype' => [
                'type' => PARAM_ALPHA,
                'element_type' => 'select',
                'label' => get_string('applyto', 'format_designer'),
                'element_attributes' => [$sectionbackgroundtypeoptions],
                'help' => 'sectionbackgroundtype',
                'help_component' => 'format_designer',
            ],

            'sectiondesignerbackgroundcolor' => [
                'type' => PARAM_RAW,
                'label' => get_string('backgroundcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
            ],
            'sectiondesignerbackgroundimage' => [
                'type' => PARAM_RAW,
                'label' => get_string('backgroundimage', 'format_designer'),
                'element_type' => 'filemanager',
                'element_attributes' => [[],
                    [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'filetype' => 'image',
                    ],
                ],
                'filearea' => 'sectiondesignbackground',
            ],
            'sectiondesignerusecompletionbg' => [
                'type'  => PARAM_INT,
                'label' => get_string('usecompletionbg', 'format_designer'),
                'element_type' => 'checkbox',
            ],
            'sectiondesignercompletionbg' => [
                'type' => PARAM_FILE,
                'label' => get_string('completionbackgroundimage', 'format_designer'),
                'element_type' => 'filemanager',
                'element_attributes' => [[],
                    [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'filetype' => 'image',
                    ],
                ],
                'hideif' => ['sectiondesignerusecompletionbg', 'notchecked'],
            ],
            'sectiondesignerbgposition' => [
                'type' => PARAM_TEXT,
                'label' => get_string('backgroundposition', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [self::get_position_values()],
                'help' => 'backgroundposition',
                'help_component' => 'format_designer',
                'default' => 'center center',
            ],
            'sectiondesignercustombgposition' => [
                'type' => PARAM_TEXT,
                'label' => get_string('designercustombgposition', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [],
                'help' => 'backgroundposition',
                'help_component' => 'format_designer',
                'hideif' => ['sectiondesignerbgposition', 'neq', 'custom'],
            ],
            'sectiondesignerbgsize' => [
                'type' => PARAM_TEXT,
                'label' => get_string('backgroundsize', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [self::get_size_values()],
                'help' => 'backgroundsize',
                'help_component' => 'format_designer',
                'default' => 'cover',
            ],
            'sectiondesignercustombgsize' => [
                'type' => PARAM_TEXT,
                'label' => get_string('designercustombgsize', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [],
                'help' => 'backgroundsize',
                'help_component' => 'format_designer',
                'hideif' => ['sectiondesignerbgsize', 'neq', 'custom'],
            ],
            'sectiondesignerbgrepeat' => [
                'type' => PARAM_TEXT,
                'element_type' => 'select',
                'label' => get_string('backgroundrepeat', 'format_designer'),
                'element_attributes' => [
                    [
                        0 => get_string('yes', 'core'),
                        1 => get_string('no', 'core'),
                    ],
                ],
                'help' => 'backgroundrepeat',
                'help_component' => 'format_designer',
            ],
            'sectiondesignerbackgradient' => [
                'type' => PARAM_RAW,
                'label' => get_string('backgroundgradient', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [['class' => 'gradient', 'placeholder' => '', 'size' => 50]],
                'help' => 'backgroundgradient',
                'help_component' => 'format_designer',
            ],

            'sectiondesignermaskimage' => [
                'type' => PARAM_INT,
                'element_type' => 'select',
                'label' => get_string('maskimage', 'format_designer'),
                'element_attributes' => [
                    $maskimages,
                ],
                'help' => 'maskimage',
                'help_component' => 'format_designer',
            ],

            'sectiondesignermasksize' => [
                'type' => PARAM_TEXT,
                'label' => get_string('masksize', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [self::get_size_values()],
                'help' => 'masksize',
                'help_component' => 'format_designer',
                'default' => 'cover',
            ],

            'sectiondesignercustom_masksize' => [
                'type' => PARAM_TEXT,
                'label' => get_string('designercustom_masksize', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [],
                'help' => 'maskposition',
                'help_component' => 'format_designer',
                'hideif' => ['sectiondesignermasksize', 'neq', 'custom'],
            ],

            'sectiondesignermaskposition' => [
                'type' => PARAM_TEXT,
                'label' => get_string('maskposition', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [self::get_position_values()],
                'help' => 'maskposition',
                'help_component' => 'format_designer',
                'default' => 'center center',
            ],

            'sectiondesignercustom_maskposition' => [
                'type' => PARAM_TEXT,
                'label' => get_string('designercustom_maskposition', 'format_designer'),
                'element_type' => 'text',
                'element_attributes' => [],
                'help' => 'maskposition',
                'help_component' => 'format_designer',
                'hideif' => ['sectiondesignermaskposition', 'neq', 'custom'],
            ],

            'sectiondesignertextcolor' => [
                'type' => PARAM_RAW,
                'label' => get_string('textcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
                'help' => 'sectiondesignertextcolor',
                'help_component' => 'format_designer',
            ],
        ];
        return $backgrounddesignoptions;
    }

    /**
     * Get list of section format options.
     *
     * @param array $data
     * @return void
     */
    public static function update_section_format_options(&$data) {
        global $COURSE;
        $coursecontext = \context_course::instance($COURSE->id);
        if (empty($data['sectioncategoriseheader'])) {
            $data['sectioncategoriseheader'] = get_string('categoriseheader', 'format_designer');
        }
        if (empty($data['sectioncardheader'])) {
            $data['sectioncardheader'] = get_string('sectioncardheader', 'format_designer');
        }
        if (empty($data['sectionbackgroundheader'])) {
            $data['sectionbackgroundheader'] = get_string('sectionbackdesignheader', 'format_designer');
        }

        if (empty($data['sectiondesignheader'])) {
            $data['sectiondesignheader'] = get_string('sectiondesignheader', 'format_designer');
        }
        if (!empty($data['sectiondesignerbackgroundimage'])) {
            $itemid = $data['id'];
            $filearea = 'sectiondesignbackground';
            $record = new \stdClass();
            $record->designerbackground_filemanager = $data['sectiondesignerbackgroundimage'];
            file_postupdate_standard_filemanager($record, 'designerbackground', [
                'accepted_types' => 'images',
                'maxfiles' => 1,
            ], $coursecontext, 'format_designer', $filearea, $itemid);
        }

        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $COURSE->maxbytes,
            'trust' => false,
            'context' => $coursecontext,
            'noclean' => true,
        ];
        if (!empty($data['sectioncardcta_editor'])) {
            $itemid = $data['id'];
            $filearea = 'sectioncardcta';
            $data = file_postupdate_standard_editor( (object) $data, 'sectioncardcta', $editoroptions, $coursecontext,
                'local_designer', $filearea, $itemid);
            $data = (array) $data;
            unset($data['sectioncardcta_editor']);
        }

        if (!empty($data['sectiondesignercompletionbg'])) {
            $itemid = $data['id'];
            $filearea = 'sectiondesigncompletionbackground';
            $record = new \stdClass();
            $record->designercompletionbg_filemanager = $data['sectiondesignercompletionbg'];
            file_postupdate_standard_filemanager($record, 'designercompletionbg', [
                'accepted_types' => 'images',
                'maxfiles' => 1,
            ], $coursecontext, 'format_designer', $filearea, $itemid);
        }

        // Make sectionbackgroundtype to header for flow course format.
        $course = course_get_format($COURSE)->get_course();
        if (isset($course->coursetype) && $course->coursetype == DESIGNER_TYPE_FLOW ) {
            $data['sectionbackgroundtype'] = 'header';
        }
    }

    /**
     * Load course settings prepare file.
     *
     * @param [object] $course
     * @param \MoodleQuickForm $mform The actual form object (required to modify the form).
     * @return void
     */
    public static function load_course_prepare_file($course, $mform) {
        // TODO: Copy all the fields to here.
        self::prepare_course_headerbg_filearea($course, $mform);
        self::prepare_coursebg_filearea($course, $mform);
    }

    /**
     * Update the strucutre of the course format options before get_course.
     *
     * @param stdclass $course
     * @return void
     */
    public static function update_structure_get_course(&$course) {
        global $PAGE;
        // Update the user fields to array.
        if (isset($course->userfields)) {
            $userfields = $course->userfields;
            $course->userfields = is_string($userfields) ? explode(',', $userfields) : $userfields;
        }
        self::prepare_prerequisites_editor($course);
        self::prepare_additionalcontent_editor($course);
    }

    /**
     * Preapare fileare for the coures header additional content option.
     *
     * @param stdclass $course course data.
     */
    protected static function prepare_additionalcontent_editor(&$course) {
        global $CFG;
        if (!isguestuser() && isloggedin()) {
            if (isset($course->additionalcontent) && is_string($course->additionalcontent)) {
                $coursecontext = \context_course::instance($course->id);
                $editoroptions = info::get_editoroptions();
                $editoroptions['context'] = $coursecontext;
                $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'local_designer', 'additionalcontent', 0);
                $course = file_prepare_standard_editor(
                    $course, 'additionalcontent', $editoroptions, $coursecontext, 'local_designer', 'additionalcontent', 0
                );
                $course->additionalcontent = $course->additionalcontent_editor;
                unset($course->additionalcontent_editor);
            }
        } else {
            $editoroptions['context'] = \context_system::instance();
            $course = file_prepare_standard_editor(
                $course, 'additionalcontent', $editoroptions, null, 'local_designer', 'additionalcontent', null,
            );
        }
    }


    /**
     * Preapare fileare for the prerequisites content option.
     *
     * @param stdclass $course course data.
     */
    protected static function prepare_prerequisites_editor(&$course) {
        global $CFG, $DB;
        $coursecontext = \context_course::instance($course->id);
        if (!isguestuser() && isloggedin()) {
            if (isset($course->prerequisiteinfo) && is_string($course->prerequisiteinfo)) {
                $coursecontext = context_course::instance($course->id);
                $editoroptions = ['maxfiles' => -1, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true,
                ];
                $editoroptions['context'] = $coursecontext;
                $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'local_designer', 'prerequisiteinfo', 0);
                $course = file_prepare_standard_editor(
                    $course, 'prerequisiteinfo', $editoroptions,
                    $coursecontext, 'local_designer', 'prerequisiteinfo', 0
                );
                $course->prerequisiteinfo = $course->prerequisiteinfo_editor;
                unset($course->prerequisiteinfo_editor);
            }
        } else {
            $editoroptions['context'] = \context_system::instance();
            $course = file_prepare_standard_editor(
                $course, 'prerequisiteinfo', $editoroptions, null, 'local_designer', 'prerequisiteinfo', null,
            );
        }
    }

    /**
     * Preapare fileare for the coures background.
     *
     * @param stdclass $course course data.
     * @param \MoodleQuickForm $mform The actual form object (required to modify the form).
     */
    protected static function prepare_coursebg_filearea($course, $mform) {
        global $CFG;
        $coursecontext = \context_course::instance($course->id);
        $editoroptions = [
            'subdirs' => 0,
            'accepted_types' => 'images',
            'maxfiles' => 1,
            'maxbytes' => $CFG->maxbytes,
        ];
        $editoroptions['context'] = $coursecontext;
        $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'local_designer', 'coursebgimage', 0);
        $draftid = 0;
        file_prepare_draft_area($draftid, $coursecontext->id, 'local_designer', 'coursebgimage', 0, $editoroptions);
        if ($mform->elementExists('coursebgimage_filemanager')) {
            $mform->setDefault('coursebgimage_filemanager', $draftid);
        }
    }

    /**
     * Preapare fileare for the coures header background.
     *
     * @param stdclass $course course data.
     * @param \MoodleQuickForm $mform The actual form object (required to modify the form).
     */
    protected static function prepare_course_headerbg_filearea($course, $mform) {
        global $CFG;
        $coursecontext = \context_course::instance($course->id);
        $editoroptions = [
            'subdirs' => 0,
            'accepted_types' => 'images',
            'maxfiles' => 1,
            'maxbytes' => $CFG->maxbytes,
        ];
        $editoroptions['context'] = $coursecontext;
        $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'local_designer', 'courseheaderbgimage', 0);
        $draftid = 0;
        file_prepare_draft_area($draftid, $coursecontext->id, 'local_designer', 'courseheaderbgimage', 0, $editoroptions);
        if ($mform->elementExists('courseheaderbgimage_filemanager')) {
            $mform->setDefault('courseheaderbgimage_filemanager', $draftid);
        }
    }

    /**
     * Updates format options for a course.
     *
     * @param stdClass|array $data return value from {@see moodleform::get_data()} or array with data.
     * @param int $courseid course id.
     */
    public static function update_course_format_options(&$data, int $courseid) {

        // Update the course fields.
        if (isset($data->coursefields) && is_array($data->coursefields) ) {
            $data->coursefields = implode(',', $data->coursefields);
        }
        // Update the users profile fields list.
        if (isset($data->userfields) && is_array($data->userfields) ) {
            $data->userfields = implode(',', $data->userfields);
        }
        // Save the course overviewfiles/.
        $context = \context_course::instance($courseid);
        $bgfileoptions = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => array('web_image'),
        ];
        $data = file_postupdate_standard_filemanager((object) $data, 'coursebgimage',
        $bgfileoptions, $context, 'local_designer', 'coursebgimage', 0);

        $data = (array) file_postupdate_standard_filemanager((object) $data, 'courseheaderbgimage',
        $bgfileoptions, $context, 'local_designer', 'courseheaderbgimage', 0);

        if (isset($data['additionalcontent']) &&  is_array($data['additionalcontent'])) {
            // Setup the editor to save areafiles. hack.
            $data['additionalcontent_editor'] = $data['additionalcontent'];
            $data = file_postupdate_standard_editor(
                (object) $data, 'additionalcontent', info::get_editoroptions(), $context, 'local_designer', 'additionalcontent', 0
            );
        }
    }


    /**
     * Get file areas available in desginer format.
     *
     * @param string $method
     */
    public static function get_file_areas($method) {
        if ($method == 'section') {
            return [
                'sectiondesigncompletionbackground' => 'format_designer',
                'sectiondesignbackground' => 'format_designer',
                'sectioncardcta' => 'local_designer',
            ];
        } else if ($method == 'course') {
            return [
                'courseheaderbgimage' => 'local_designer',
                'coursebgimage' => 'local_designer',
                'prerequisiteinfo' => 'local_designer',
                'additionalcontent' => 'local_designer',
            ];
        }
        return [
            'moduledesignbackground' => 'local_designer',
            'moduledesigncompletionbackimage' => 'local_designer',
        ];
    }


    /**
     * Get background position options.
     */
    public static function get_position_values() {
        return [
            'initial' => get_string('initial', 'format_designer'),
            'left top' => get_string('lefttop', 'format_designer'),
            'left center' => get_string('leftcenter', 'format_designer'),
            'left bottom' => get_string('leftbottom', 'format_designer'),
            'right top' => get_string('righttop', 'format_designer'),
            'right center' => get_string('rightcenter', 'format_designer'),
            'right bottom' => get_string('rightbottom', 'format_designer'),
            'center top' => get_string('centertop', 'format_designer'),
            'center center' => get_string('centercenter', 'format_designer'),
            'center bottom' => get_string('centerbottom', 'format_designer'),
            'custom' => get_string('strcustom', 'format_designer'),
        ];
    }


    /**
     * Get background size options.
     */
    public static function get_size_values() {
        return [
            'auto' => get_string('auto', 'format_designer'),
            'cover' => get_string('cover', 'format_designer'),
            'contain' => get_string('contain', 'format_designer'),
            'custom' => get_string('strcustom', 'format_designer'),
        ];
    }

    /**
     * Get the default mod_purposes.
     */
    public static function get_default_purposes() {
        global $CFG;
        $purposes = [
            'administration' => [
                'name' => get_string('purposeadministration', 'format_designer'),
                'icon' => 'fa-cog',
            ],
            'assessment' => [
                'name' => get_string('purposeassessment', 'format_designer'),
                'icon' => 'fa-area-chart',
            ],
            'collaboration' => [
                'name' => get_string('purposecollaboration', 'format_designer'),
                'icon' => 'fa-users',
            ],
            'communication' => [
                'name' => get_string('purposecommunication', 'format_designer'),
                'icon' => 'fa-comments',
            ],
            'content' => [
                'name' => get_string('purposecontent', 'format_designer'),
                'icon' => 'fa-file',
            ],
            'other' => [
                'name' => get_string('purposeother', 'format_designer'),
                'icon' => 'fa-ellipsis-h',
            ],
            'interactivecontent' => [
                'name' => get_string('purposeinteractivecontent', 'format_designer'),
                'icon' => 'fa-magic',
            ],
        ];
        return $purposes;
    }

    /**
     * Get the designer purposes list.
     *
     * @return array
     */
    public static function get_designer_purposes() {
        global $DB;
        return $DB->get_records_menu('local_designer_purposes', null, '', 'name, name');
    }


    /**
     * Install the default purposes.
     * @return void
     */
    public static function install_core_purposes() {
        global $DB;
        $lists = self::get_default_purposes();
        foreach ($lists as $list) {
            if (!$DB->record_exists('local_designer_purposes', ['name' => $list['name']])) {
                $data = new \stdClass;
                $data->name = $list['name'];
                $data->icon = $list['icon'];
                $data->custom = 0;
                $data->status = 1;
                $data->timeadded = time();
                $DB->insert_record('local_designer_purposes', $data);
            }
        }
    }

    /**
     * Process purpose module.
     *
     * @param [object] $data
     * @param [object] $course
     * @param [object] $mod
     * @return object
     */
    public static function process_purpose_modules(&$data, $course, $mod) {
        global $DB;
        $modpurpose = \format_designer\options::get_option($mod->id, 'purpose');
        if (!$modpurpose) {
            $modpurpose = get_config('local_designer', "purpose_" . $mod->modname);
        }

        if (!$DB->record_exists('local_designer_purposes', ['status' => 1, 'name' => $modpurpose])) {
            return;
        }

        if (!isset($data[$modpurpose])) {
            $data[$modpurpose]['count'] = 1;
        } else {
            $data[$modpurpose]['count'] += 1;
        }

        if (!isset($data[$modpurpose]['name'])) {
            $data[$modpurpose]['name'] = $modpurpose;
        }

        if (!isset($data[$modpurpose]['icon'])) {
            $data[$modpurpose]['icon'] = self::get_purpose_field($modpurpose, 'icon');
        }

        if (!isset($data[$modpurpose]['customclass'])) {
            $data[$modpurpose]['customclass'] = self::get_purpose_field($modpurpose, 'customclass');
        }
    }

    /**
     * Get purpose field.
     *
     * @param [string] $purpose
     * @param [string] $field
     * @return void
     */
    public static function get_purpose_field($purpose, $field) {
        global $DB;
        return $DB->get_field('local_designer_purposes', $field, ['name' => $purpose]);
    }

}
