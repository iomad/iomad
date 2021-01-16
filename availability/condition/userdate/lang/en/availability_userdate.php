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
 * Language strings.
 *
 * @package availability_days
 * @copyright 2016 Valery Fremaux
 * @copyright 2018 Guido Hornig (userdate made out of "days" from Valery Fremaux)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['conditiontitle'] = 'Userdate overdue';
$string['ajaxerror'] = 'Error contacting server to convert times';
$string['description'] = 'Prevent access if a specified date (including offset) is reached. The date is specified in a configurable user field with date format';
$string['pluginname'] = 'Userdate overdue';
$string['title'] = 'userdate';

$string['error_selectfield'] = 'You must select a profile field.';
//$string['error_setvalue'] = 'You must type a value.';
$string['label_operator'] = 'Method of comparison';
//$string['label_value'] = 'Value to compare against';

$string['requires_before'] = 'Your <strong>{$a->field}</strong>  is before  <strong>{$a->value}</strong>';
$string['requires_after'] = 'Your <strong>{$a->field}</strong> is after <strong>{$a->value}</strong>';
$string['requires_isempty'] = 'Your <strong>{$a->field}</strong> is empty';
$string['requires_isequalto'] = 'Your <strong>{$a->field}</strong> is <strong>{$a->value}</strong>';
$string['requires_isnotempty'] = 'Your <strong>{$a->field}</strong> is not empty';

$string['missing'] = '(Missing custom field: {$a})';

$string['op_before'] = 'before';
$string['op_after'] = 'after';
$string['op_isempty'] = 'is empty';
$string['op_isequalto'] = 'is equal to';
$string['op_isnotempty'] = 'is not empty';

