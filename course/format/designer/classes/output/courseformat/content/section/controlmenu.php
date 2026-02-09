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
 * Contains the default section controls output class.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\output\courseformat\content\section;

use core_courseformat\output\local\content\section\controlmenu as controlmenu_base;
use section_info;
use core_courseformat\base as course_format;
use stdClass;
use action_menu_link_secondary;
use action_menu;
use moodle_url;
use pix_icon;
use context_course;
use core\output\local\action_menu\subpanel as action_menu_subpanel;
use core\output\choicelist;

/**
 * Base class to render section controls.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {

    /**
     * Generate the default section action menu.
     *
     * This method is public in case some block needs to modify the menu before output it.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return action_menu|null the activity action menu
     */
    public function get_default_action_menu(\renderer_base $output): ?action_menu {
        $controls = $this->section_control_items();
        if (empty($controls)) {
            return null;
        }

        // Convert control array into an action_menu.
        $menu = new action_menu();
        $menu->set_kebab_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-actions';

        foreach ($controls as $value) {
            $value = (array) $value;
            $url = empty($value['url']) ? '' : $value['url'];
            $icon = empty($value['icon']) ? '' : $value['icon'];
            if ($icon instanceof pix_icon) {
                $icon = $icon->pix;
            }
            $name = empty($value['name']) ? '' : $value['name'];
            $attr = empty($value['attr']) ? [] : $value['attr'];
            $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
            $al = new action_menu_link_secondary(
                new moodle_url($url),
                new pix_icon($icon, '', null, ['class' => "smallicon " . $class]),
                $name,
                $attr
            );
            $menu->add($al);
        }
        return $menu;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        $section = $this->section;

        $hassectiontypes = true;

        $sectionnum = $this->format->get_sectionnum();

        $course = $this->format->get_course();

        if (($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE && !$sectionnum)
            || $course->coursetype == DESIGNER_TYPE_FLOW) {
            $hassectiontypes = false;
        }

        $controls = $this->section_control_items();

        if (empty($controls)) {
            return new stdClass();
        }

        // Convert control array into an action_menu.
        $menu = new action_menu();
        $menu->set_menu_trigger(get_string('edit'));
        $menu->attributes['class'] .= ' section-actions';
        foreach ($controls as $key => $value) {
            if ($key == 'sectionlayout') {
                $menu->add($value);
            } else {
                $url = empty($value['url']) ? '' : $value['url'];
                $icon = empty($value['icon']) ? '' : $value['icon'];
                $name = empty($value['name']) ? '' : $value['name'];
                $attr = empty($value['attr']) ? [] : $value['attr'];
                $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
                $al = new action_menu_link_secondary(
                    new moodle_url($url),
                    new pix_icon($icon, '', null, ['class' => "smallicon " . $class]),
                    $name,
                    $attr,
                );
                $menu->add($al);
            }
        }

        $sectiontypes = [];
        if (!format_designer_is_support_subpanel()) {

            $sectiontypes = [
                [
                    'type' => 'default',
                    'name' => get_string('link', 'format_designer'),
                    'active' => empty($this->format->get_section_option($section->id, 'sectiontype'))
                        || $this->format->get_section_option($section->id, 'sectiontype') == 'default',
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $section->section),
                ],
                [
                    'type' => 'list',
                    'name' => get_string('list', 'format_designer'),
                    'active' => $this->format->get_section_option($section->id, 'sectiontype') == 'list',
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $section->section),
                ],
                [
                    'type' => 'cards',
                    'name' => get_string('cards', 'format_designer'),
                    'active' => $this->format->get_section_option($section->id, 'sectiontype') == 'cards',
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $section->section),
                ],
            ];

            if (format_designer_has_pro()) {
                $prosectiontypes = \local_designer\info::get_layout_menu($this->format, $section, $course);
                $sectiontypes = array_merge($sectiontypes, $prosectiontypes);
            }

        }

        $data = (object) [
            'menu' => $output->render($menu),
            'hasmenu' => true,
            'id' => $section->id,
            'seciontypes' => $sectiontypes,
            'is_subpanel' => format_designer_is_support_subpanel(),
            'hassectiontypes' => $hassectiontypes,
        ];
        return $data;
    }

    /**
     * Generate the edit control items of a section.
     *
     * It is not clear this kind of controls are still available in 4.0 so, for now, this
     * method is almost a clone of the previous section_control_items from the course/renderer.php.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     * @return array of edit control items
     */
    public function section_control_items() {
        global $USER, $PAGE, $CFG;

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $sectionreturn = !is_null($format->get_sectionid()) ? $format->get_sectionnum() : null;

        $user = $USER;

        $usecomponents = $format->supports_components();
        $coursecontext = context_course::instance($course->id);
        $numsections = $format->get_last_section_number();
        $isstealth = $section->section > $numsections;

        $baseurl = course_get_url($course, $sectionreturn);
        $baseurl->param('sesskey', sesskey());

        $course = $format->get_course();

        $controls = [];

        // Only show the view link if we are not already in the section view page.
        if ($PAGE->pagetype !== 'section-view-' . $course->format) {
            $controls['view'] = [
                'url'   => new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]),
                'icon' => 'i/viewsection',
                'name' => get_string('view'),
                'pixattr' => ['class' => ''],
                'attr' => ['class' => 'icon view'],
            ];
        }

        if (!$isstealth && has_capability('moodle/course:update', $coursecontext, $user)) {

            $streditsection = get_string('editsection', 'format_'.$format->get_format());
            $controls['edit'] = [
                'url'   => new moodle_url('/course/editsection.php', ['id' => $section->id, 'sr' => $sectionreturn]),
                'icon' => 'i/settings',
                'name' => $streditsection,
                'pixattr' => ['class' => ''],
                'attr' => ['class' => 'icon edit'],
            ];

            $hassectiontypes = true;
            if (($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE && !$sectionreturn)
                || $course->coursetype == DESIGNER_TYPE_FLOW) {
                $hassectiontypes = false;
            }

            if (format_designer_is_support_subpanel() && $hassectiontypes) {
                $controls['sectionlayout'] = new action_menu_subpanel(
                    get_string('strsectionlayout', 'format_designer'),
                    $this->get_choice_list($section),
                    ['data-value' => 'section-designer-action'],
                    new pix_icon('t/hide', '', 'moodle', ['class' => 'iconsmall'])
                );
            }

            $duplicatesectionurl = clone($baseurl);
            $duplicatesectionurl->param('section', $section->section);
            $duplicatesectionurl->param('duplicatesection', $section->section);

            if (!is_null($sectionreturn)) {
                $duplicatesectionurl->param('sr', $sectionreturn);
            }

            $controls['duplicate'] = [
                'url' => $duplicatesectionurl,
                'icon' => 't/copy',
                'name' => get_string('duplicate'),
                'pixattr' => ['class' => ''],
                'attr' => ['class' => 'icon duplicate'],
            ];
        }

        if ($section->section) {
            $url = clone($baseurl);

            if (!is_null($sectionreturn)) {
                $url->param('sectionid', $format->get_sectionid());
            }

            if (!$isstealth) {
                if (has_capability('moodle/course:sectionvisibility', $coursecontext, $user)) {
                    $strhidefromothers = get_string('hidefromothers', 'format_' . $course->format);
                    $strshowfromothers = get_string('showfromothers', 'format_' . $course->format);
                    if ($section->visible) { // Show the hide/show eye.
                        $url->param('hide', $section->section);
                        $controls['visiblity'] = [
                            'url' => $url,
                            'icon' => 'i/hide',
                            'name' => $strhidefromothers,
                            'pixattr' => ['class' => ''],
                            'attr' => [
                                'class' => 'icon editing_showhide',
                                'data-sectionreturn' => $sectionreturn,
                                'data-action' => ($usecomponents) ? 'sectionHide' : 'hide',
                                'data-id' => $section->id,
                                'data-swapname' => $strshowfromothers,
                                'data-swapicon' => 'i/show',
                            ],
                        ];
                    } else {
                        $url->param('show',  $section->section);
                        $controls['visiblity'] = [
                            'url' => $url,
                            'icon' => 'i/show',
                            'name' => $strshowfromothers,
                            'pixattr' => ['class' => ''],
                            'attr' => [
                                'class' => 'icon editing_showhide',
                                'data-sectionreturn' => $sectionreturn,
                                'data-action' => ($usecomponents) ? 'sectionShow' : 'show',
                                'data-id' => $section->id,
                                'data-swapname' => $strhidefromothers,
                                'data-swapicon' => 'i/hide',
                            ],
                        ];
                    }
                }

                if (!$sectionreturn && has_capability('moodle/course:movesections', $coursecontext, $user)) {
                    if ($usecomponents) {
                        // This tool will appear only when the state is ready.
                        $url = clone ($baseurl);
                        $url->param('movesection', $section->section);
                        $url->param('section', $section->section);
                        $controls['movesection'] = [
                            'url' => $url,
                            'icon' => 'i/dragdrop',
                            'name' => get_string('move', 'moodle'),
                            'pixattr' => ['class' => ''],
                            'attr' => [
                                'class' => 'icon move waitstate',
                                'data-action' => 'moveSection',
                                'data-id' => $section->id,
                            ],
                        ];
                    }
                    // Legacy move up and down links for non component-based formats.
                    $url = clone($baseurl);
                    if ($section->section > 1) { // Add a arrow to move section up.
                        $url->param('section', $section->section);
                        $url->param('move', -1);
                        $strmoveup = get_string('moveup');
                        $controls['moveup'] = [
                            'url' => $url,
                            'icon' => 'i/up',
                            'name' => $strmoveup,
                            'pixattr' => ['class' => ''],
                            'attr' => ['class' => 'icon moveup whilenostate'],
                        ];
                    }

                    $url = clone($baseurl);
                    if ($section->section < $numsections) { // Add a arrow to move section down.
                        $url->param('section', $section->section);
                        $url->param('move', 1);
                        $strmovedown = get_string('movedown');
                        $controls['movedown'] = [
                            'url' => $url,
                            'icon' => 'i/down',
                            'name' => $strmovedown,
                            'pixattr' => ['class' => ''],
                            'attr' => ['class' => 'icon movedown whilenostate'],
                        ];
                    }
                }
            }

            if (course_can_delete_section($course, $section)) {
                if (get_string_manager()->string_exists('deletesection', 'format_'.$course->format)) {
                    $strdelete = get_string('deletesection', 'format_'.$course->format);
                } else {
                    $strdelete = get_string('deletesection');
                }

                $params = [
                    'id' => $section->id,
                    'delete' => 1,
                    'sesskey' => sesskey(),
                ];

                if (!is_null($sectionreturn)) {

                    $params['sr'] = $sectionreturn;

                }
                $url = new moodle_url(
                    '/course/editsection.php',
                    $params,
                );
                $controls['delete'] = [
                    'url' => $url,
                    'icon' => 'i/delete',
                    'name' => $strdelete,
                    'pixattr' => ['class' => ''],
                    'attr' => [
                        'class' => 'icon editing_delete text-danger',
                        'data-action' => 'deleteSection',
                        'data-id' => $section->id,
                    ],
                ];
            }
        }
        if (
            has_any_capability([
                'moodle/course:movesections',
                'moodle/course:update',
                'moodle/course:sectionvisibility',
            ], $coursecontext)
        ) {
            $sectionlink = new moodle_url(
                '/course/view.php',
                ['id' => $course->id],
                "sectionid-{$section->id}-title"
            );
            $controls['permalink'] = [
                'url' => $sectionlink,
                'icon' => 'i/link',
                'name' => get_string('sectionlink', 'format_designer'),
                'pixattr' => ['class' => ''],
                'attr' => [
                    'class' => 'icon',
                    'data-action' => 'permalink',
                ],
            ];
        }

        return $controls;
    }

    /**
     * Get the availability choice list.
     * @param object $section
     * @return choicelist
     */
    public function get_choice_list($section): choicelist {
        $sectiontype = $this->format->get_section_option($section->id, 'sectiontype');
        $sectiontype = $sectiontype ? $sectiontype : get_config('format_designer', 'sectiontype');
        $choice = $this->create_choice_list($section);
        $choice->set_selected_value($sectiontype);
        return $choice;
    }


    /**
     * Create a choice list for the dropdown.
     * @param object $section
     * @return choicelist the choice list
     */
    protected function create_choice_list($section): choicelist {
        global $CFG;

        $choice = new choicelist();

        $lists = [
            'default' => get_string('link', 'format_designer'),
            'list' => get_string('list', 'format_designer'),
            'cards' => get_string('cards', 'format_designer'),
        ];
        if (format_designer_has_pro()) {
            $prosectiontypes = \local_designer\info::get_layout_menu($this->format, $section, $this->format->get_course());
            $lists = array_merge($lists, array_column($prosectiontypes, 'name', 'type'));
        }

        foreach ($lists as $key => $value) {
            $choice->add_option(
                $key,
                $value,
                $this->get_option_data( $key)
            );
        }

        return $choice;
    }


    /**
     * Get the data for the option.
     * @param string $value the value of the option
     * @return array
     */
    private function get_option_data(string $value): array {
        global $PAGE;
        return [
            'icon' => '',
            // Non-ajax behat is not smart enough to discrimante hidden links
            // so we need to keep providing the non-ajax links.
            'url' => $PAGE->url,
            'extras' => [
                'data-option' => 'sectiontype',
                'data-value'  => $value,
            ],
        ];
    }

}
