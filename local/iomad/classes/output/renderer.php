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
 * Local IOMAD OUTPUT renderer class
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\output;

use core_table\output\html_table;
use core_table\output\html_table_row;
use core_table\output\html_table_cell;
use html_writer;
use local_iomad\custom_context\context_company;
use local_iomad\{email, iomad};
use local_iomad\forms\email_template_edit_form;
use moodle_url;
use plugin_renderer_base;

/**
 * Local IOMAD OUTPUT renderer class
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Back to list of roles button
     */
    public function roles_button($link) {
        return html_writer::tag(
            'p',
            html_writer::tag(
                'a',
                get_string('listroles', 'block_iomad_company_admin'),
                [
                    'class' => 'btn btn-primary',
                    'href' => $link,
                ]
            ));
    }

    /**
     * Back to list of roles button
     */
    public function templateset_buttons($savelink, $managelink, $backlink) {
        $out = html_writer::start_tag('p');
        if (!empty($backlink)) {
            $out .= html_writer::tag(
                'a',
                get_string('backtocompanytemplates', 'local_iomad'),
                [
                    'class' => 'btn btn-primary',
                    'href' => $backlink,
                ]
            );
        } else {
            $out .= html_writer::tag(
                'a',
                get_string('savetemplateset', 'local_iomad'),
                [
                    'class' => 'btn btn-primary',
                    'href' => $savelink,
                ]
            );
            $out .= html_writer::tag(
                'a',
                get_string('managetemplatesets', 'local_iomad'),
                [
                    'class' => 'btn btn-primary',
                    'href' => $managelink,
                ]
            );
        }
        $out .= html_writer::end_tag('p');

        return $out;
    }

    /**
     * Display role templates.
     */
    public function email_templates($templates,
                                    $configtemplates,
                                    $lang,
                                    $prefix,
                                    $templatesetid,
                                    $page = 0,
                                    $perpage = 30) {
        global $companyid;

        $ntemplates = count($configtemplates);
        $companycontext = context_company::instance($companyid);
        $out = "";

        if (iomad::has_capability('local/iomad:email_edit', $companycontext)) {
            $enable = true;
        } else {
            $enable = false;
        }

        // Deal with header sliders.
        $sliced = array_slice($configtemplates, $page * $perpage, $perpage, true);
        $echecked = "checked";
        $eschecked = "checked";
        $emchecked = "checked";
        $ecount = 0;
        $emcount = 0;
        $escount = 0;

        foreach ($sliced as $test) {
            foreach ($templates as $templateid => $template) {
                if ($template->name == $test) {
                    if ($template->disabled) {
                        $ecount++;
                    }
                    if ($template->disabledmanager) {
                        $emcount++;
                    }
                    if ($template->disabledsupervisor) {
                        $escount++;
                    }
                }
            }
        }
        if ($ecount == count($sliced)) {
            $echecked = "";
        }
        if ($emcount == count($sliced)) {
            $emchecked = "";
        }
        if ($escount == count($sliced)) {
            $eschecked = "";
        }
        $table = new html_table();
        $table->id = 'ReportTable';
        $head = [];
        $head[] = get_string('emailtemplatename', 'local_iomad');
        $head[] = get_string('enable') .
                  html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enableallall',
                            'type' => 'checkbox',
                            $echecked => true,
                            'value' => "{$prefix}.e.{$page}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
        $head[] = get_string('enable_manager', 'local_iomad') .
                  html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enableallmanager',
                            'type' => 'checkbox',
                            $emchecked => true,
                            'value' => "{$prefix}.em.{$page}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
        $head[] = get_string('enable_supervisor', 'local_iomad') .
                  html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enableallsupervisor',
                            'type' => 'checkbox',
                            $eschecked => true,
                            'value' => "{$prefix}.es.{$page}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
        $head[] = get_string('controls', 'local_iomad');
        $table->head = $head;
        $table->align = ["left",
                         "center",
                         "center",
                         "center",
                         "center",
                         "center",
                         "center",
                         "center"];

        $i = $page * $perpage;
        $max = ($page + 1) * $perpage;

        while ($i < $max && $i < $ntemplates) {
            $found = false;
            foreach ($templates as $templateid => $template) {
                if ($template->name == $configtemplates[$i]) {
                    $found = true;
                    $templatename = $configtemplates[$i];
                    unset($templates[$templateid]);
                    break;
                }
            }
            if (!$found) {
                $table->data[] = email::create_default_template_row($configtemplates[$i],
                                                                    $enable,
                                                                    $lang,
                                                                    $prefix,
                                                                    $templatesetid);
            } else {
                $row = new html_table_row();
                $row->cells[] = get_string($templatename.'_name', 'local_iomad') .
                                $this->help_icon($templatename.'_name', 'local_iomad');
                if ($enable) {
                    if ($template->disabled) {
                        $checked = "";
                    } else {
                        $checked = "checked";
                    }
                    $enablebutton = html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enableallall',
                            'type' => 'checkbox',
                            $checked => true,
                            'value' => "{$prefix}.e.{$templatename}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
                    $cell = new html_table_cell($enablebutton);
                    $row->cells[] = $cell;
                    if ($template->disabledmanager) {
                        $checked = '';
                    } else {
                        $checked = 'checked';
                    }
                    $enablemanagerbutton = html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enablemanager',
                            'type' => 'checkbox',
                            $checked => true,
                            'value' => "{$prefix}.em.{$templatename}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
                    $cell = new html_table_cell($enablemanagerbutton);
                    $row->cells[] = $cell;
                    if ($template->disabledsupervisor) {
                        $checked = '';
                    } else {
                        $checked = 'checked';
                    }
                    $enablesupervisorbutton = html_writer::tag(
                    'label',
                    html_writer::tag(
                        'input',
                        html_writer::empty_tag(
                            'span',
                            [
                                'class' => 'slider round',
                            ]
                        ),
                        [
                            'class' => 'checkbox enablesupervisor',
                            'type' => 'checkbox',
                            $checked => true,
                            'value' => "{$prefix}.es.{$templatename}",
                        ]
                    ),
                    [
                        'class' => 'switch',
                    ]);
                    $cell = new html_table_cell($enablesupervisorbutton);
                    $row->cells[] = $cell;
                }

                $rowform = new email_template_edit_form(new moodle_url('template_edit_form.php'),
                                                        $templatesetid);
                $rowform->set_data(['templatename' => $templatename, 'lang' => $lang]);
                $cell = new html_table_cell($rowform->render());
                $row->cells[] = $cell;
                $table->data[] = $row;
            }

            // Need to increase the counter to skip the default template.
            $i++;
        }

        if (!empty($table)) {
            $out .= html_writer::table($table);
        }

        return $out;
    }

    /**
     * Display role templates.
     */
    public function email_templatesets($templates, $backlink) {

        $out = html_writer::tag('a', get_string('back'), ['class' => 'btn btn-primary', 'href' => $backlink]);
        $table = new html_table();
        foreach ($templates as $template) {
            $deletelink = new moodle_url('/local/iomad/template_list.php',
                                          ['templatesetid' => $template->id,
                                                'action' => 'delete',
                                                'sesskey' => sesskey()]);
            $editlink = new moodle_url('/local/iomad/template_list.php',
                                        ['templatesetid' => $template->id, 'action' => 'edit']);
            $applylink = new moodle_url('/local/iomad/template_apply_form.php',
                                        ['templatesetid' => $template->id, 'action' => 'apply']);
            $row = [
                $template->templatesetname,
                html_writer::tag(
                    'a',
                    get_string('deletetemplateset', 'local_iomad'),
                    [
                        'class' => 'btn btn-primary',
                        'href' => $deletelink,
                    ]) .
                html_writer::tag(
                    'a',
                    get_string('edittemplateset', 'local_iomad'),
                    [
                        'class' => 'btn btn-primary',
                        'href' => $editlink,
                    ]) .
                html_writer::tag(
                    'a',
                    get_string('applytemplateset', 'local_iomad', $template->templatesetname),
                    [
                        'class' => 'btn btn-primary',
                        'href' => $applylink,
                    ]),
            ];

            $table->data[] = $row;
        }

        $out .= html_writer::table($table);
        return $out;
    }
}
