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
 * Local IOMAD implementation of web service local_iomad_restrict_email_template
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;

/**
 * Local IOMAD implementation of web service local_iomad_restrict_email_template
 *
 * @package    local_iomad
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restrict_email_template extends external_api {

    /**
     * Describes the parameters for local_iomad_restrict_email_template
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'template' => new external_value(PARAM_CLEAN, 'Encoded template info'),
            'value' => new external_value(PARAM_BOOL, 'On or Off'),
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'page' => new external_value(PARAM_INT, 'Current page of listing'),
            'perpage' => new external_value(PARAM_INT, 'How many are on the page'),
            'lang' => new external_value(PARAM_ALPHA, 'Language code'),
        ]);
    }

    /**
     * Implementation of web service local_iomad_restrict_email_template
     *
     * @param string $template
     * @param bool $value
     * @param int $companyid
     * @param int $page
     * @param int $perpage
     * @param string $lang
     */
    public static function execute($template, $value, $companyid, $page, $perpage, $lang) {
        global $DB;

        // Parameter validation.
        ['template' => $template,
         'value' => $value,
         'companyid' => $companyid,
         'page' => $page,
         'perpage' => $perpage,
         'lang' => $lang] = self::validate_parameters(
            self::execute_parameters(),
            ['template' => $template,
             'value' => $value,
             'companyid' => $companyid,
             'page' => $page,
             'perpage' => $perpage,
             'lang' => $lang]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $context = context_company::instance($companyid);
        self::validate_context($context);

        // Split out the payload.
        [$type, $id, $managertype, $senttemplatename] = explode('.', $template);

        // What are we dealing with?
        if ($type == 'c') {
            iomad::require_capability('local/iomad:email_list', $context);
            $tablename = "email_template";
            $tablenamestrings = "email_template_strings";
            $tablekey = "companyid";
            $stringkey = "templateid";
        } else if ($type == 't') {
            iomad::require_capability('local/iomad:email_templateset_list', $context);
            $tablename = "email_templateset_templates";
            $tablenamestrings = "email_templateset_template_strings";
            $tablekey = "templateset";
            $stringkey = "templatesetid";
        }

        // Get the new value.
        $newvalue = 0;
        if (empty($value)) {
            $newvalue = 1;
        }

        // What are we disabling?
        if ($managertype == 'e') {
            $tablefield = "disabled";
        }
        if ($managertype == 'em') {
            $tablefield = "disabledmanager";
        }
        if ($managertype == 'es') {
            $tablefield = "disabledsupervisor";
        }

        // Did we get passed a single template name?
        if (!is_numeric($senttemplatename)) {
            // Do the work.
            $DB->set_field($tablename, $tablefield, $newvalue, ['name' => $senttemplatename, $tablekey => $id]);
        } else {
            // Get all the records.
            $findsql = "SELECT et.id, et.name
                        FROM {" . $tablename . "} et
                        JOIN {" . $tablenamestrings ."} ets
                        ON et.id = ets.$stringkey
                        JOIN {tool_customlang} cl ON (
                            ets.lang=cl.lang
                            AND cl.stringid = CONCAT(et.name, '_name')
                        )
                        JOIN {tool_customlang_components} tcc ON (cl.componentid = tcc.id)
                        WHERE et.$tablekey = :id
                        AND ets.lang = :lang
                        AND tcc.name = :component
                        ORDER BY cl.master";
            $sqlparams = ['id' => $id,
                          'lang' => $lang,
                          'component' => 'local_email'];

            // Get all of the records.
            $templatenames = $DB->get_records_sql_menu($findsql,
                                                       $sqlparams,
                                                       $page * $perpage,
                                                       $perpage);

            foreach ($templatenames as $templatename) {
                $DB->set_field($tablename, $tablefield, $newvalue, ['name' => $templatename, $tablekey => $id]);
            }
        }

        return [];
    }

    /**
     * Describe the return structure for local_iomad_restrict_email_template
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
