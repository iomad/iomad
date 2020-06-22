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
 * Contains renderers for the course management pages.
 *
 * @package core_course
 * @copyright 2013 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

class theme_iomadarmm_core_course_management_renderer extends core_course_management_renderer {

    /**
     * Renders a category list item.
     *
     * This function gets called recursively to render sub categories.
     *
     * @param coursecat $category The category to render as listitem.
     * @param coursecat[] $subcategories The subcategories belonging to the category being rented.
     * @param int $totalsubcategories The total number of sub categories.
     * @param int $selectedcategory The currently selected category
     * @param int[] $selectedcategories The path to the selected category and its ID.
     * @return string
     */
    public function category_listitem(coursecat $category, array $subcategories, $totalsubcategories,
                                      $selectedcategory = null, $selectedcategories = array()) {

        $isexpandable = ($totalsubcategories > 0);
        $isexpanded = (!empty($subcategories));
        $activecategory = ($selectedcategory === $category->id);
        $attributes = array(
            'class' => 'listitem listitem-category',
            'data-id' => $category->id,
            'data-expandable' => $isexpandable ? '1' : '0',
            'data-expanded' => $isexpanded ? '1' : '0',
            'data-selected' => $activecategory ? '1' : '0',
            'data-visible' => $category->visible ? '1' : '0',
            'role' => 'treeitem',
            'aria-expanded' => $isexpanded ? 'true' : 'false'
        );
        $text = $category->get_formatted_name();
        if ($category->parent) {
            $a = new stdClass;
            $a->category = $text;
            $a->parentcategory = $category->get_parent_coursecat()->get_formatted_name();
            $textlabel = get_string('categorysubcategoryof', 'moodle', $a);
        }
        $courseicon = $this->output->pix_icon('i/course', get_string('courses'));
        $bcatinput = array(
            'type' => 'checkbox',
            'name' => 'bcat[]',
            'value' => $category->id,
            'class' => 'bulk-action-checkbox',
            'aria-label' => get_string('bulkactionselect', 'moodle', $text),
            'data-action' => 'select'
        );

        if (!$category->can_resort_subcategories() && !$category->has_manage_capability()) {
            // Very very hardcoded here.
            $bcatinput['style'] = 'visibility:hidden';
        }

        $viewcaturl = new moodle_url('/course/management.php', array('categoryid' => $category->id));
        if ($isexpanded) {
            $icon = $this->output->pix_icon('t/switch_minus', get_string('collapse'), 'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                $viewcaturl,
                $icon,
                array(
                    'class' => 'float-left',
                    'data-action' => 'collapse',
                    'title' => get_string('collapsecategory', 'moodle', $text),
                    'aria-controls' => 'subcategoryof'.$category->id
                )
            );
        } else if ($isexpandable) {
            $icon = $this->output->pix_icon('t/switch_plus', get_string('expand'), 'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                $viewcaturl,
                $icon,
                array(
                    'class' => 'float-left',
                    'data-action' => 'expand',
                    'title' => get_string('expandcategory', 'moodle', $text)
                )
            );
        } else {
            $icon = '';
            $icon = html_writer::span($icon, 'float-left');
        }
        $actions = \core_course\management\helper::get_category_listitem_actions($category);
        $hasactions = !empty($actions) || $category->can_create_course();

        $html = html_writer::start_tag('li', $attributes);
        $html .= html_writer::start_div('clearfix');
        $html .= html_writer::start_div('float-left ba-checkbox');
        $html .= html_writer::empty_tag('input', $bcatinput).'&nbsp;';
        $html .= html_writer::end_div();
        $html .= $icon;
        if ($hasactions) {
            $textattributes = array('class' => 'float-left categoryname');
        } else {
            $textattributes = array('class' => 'float-left categoryname without-actions');
        }
        if (isset($textlabel)) {
            $textattributes['aria-label'] = $textlabel;
        }
        $html .= html_writer::link($viewcaturl, $text, $textattributes);
        $html .= html_writer::start_div('float-right');
        if ($category->idnumber) {
            $html .= html_writer::tag('span', s($category->idnumber), array('class' => 'dimmed idnumber'));
        }
        if ($hasactions) {
            $html .= $this->category_listitem_actions($category, $actions);
        }
        $countid = 'course-count-'.$category->id;
        $html .= html_writer::span(
            html_writer::span($category->get_courses_count()) .
            html_writer::span(get_string('courses'), 'accesshide', array('id' => $countid)) .
            $courseicon,
            'course-count dimmed',
            array('aria-labelledby' => $countid)
        );
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        if ($isexpanded) {
            $html .= html_writer::start_tag('ul',
                array('class' => 'ml', 'role' => 'group', 'id' => 'subcategoryof'.$category->id));
            $catatlevel = \core_course\management\helper::get_expanded_categories($category->path);
            $catatlevel[] = array_shift($selectedcategories);
            $catatlevel = array_unique($catatlevel);
            foreach ($subcategories as $listitem) {
                $childcategories = (in_array($listitem->id, $catatlevel)) ? $listitem->get_children() : array();
                $html .= $this->category_listitem(
                    $listitem,
                    $childcategories,
                    $listitem->get_children_count(),
                    $selectedcategory,
                    $selectedcategories
                );
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= html_writer::end_tag('li');
        return $html;
    }
}