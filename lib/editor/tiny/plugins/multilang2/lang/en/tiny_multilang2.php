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
 * Strings for 'Multilang v2' plugin.
 *
 * @package   tiny_multilang2
 * @copyright 2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addlanguage'] = 'Add language manually';
$string['addlanguage_desc'] = 'If you choose this option, you can manually add languages by entering the iso code of the language in the textbox below. Otherwise, you must install at least 2 languages in the Language Pack.';
$string['highlight'] = 'Highlight delimiters';
$string['highlight_desc'] = 'Visually highlight the multi-language content delimiters (i.e., {mlang XX} and {mlang}) in the WYSIWYG editor';
$string['highlightcss'] = 'CSS for language tag';
$string['highlightcss_desc'] = "CSS used to highlight the multi-language content delimiters.

If you want to display the language for the multilang blocks, you can use something like the following (this example is for the Basque language, colors are probably not the best ones):

<pre>
.multilang-begin:lang(eu):before {
    content: \"eu\";
    position: relative;
    top: -0.5em;
    font-weight: bold;
    background-color: #e05e5e;
    color: #ffffff;
}
</pre>
";
$string['language_options_desc'] = 'Select options for the Language button drop-down menu.
                             <br>The full list is available from  <small><a href=\'https://www.w3schools.com/tags/ref_language_codes.asp\'>
                             <u>https://www.w3schools.com/tags/ref_language_codes.asp</u></a></small>';
$string['language_setting'] = 'Language button settings';
$string['languageoptions'] = 'Language';
$string['multilang2:desc'] = 'Add multilingual tags for content.';
$string['multilang2:langTagsInSelection'] = 'Selected text contains language tags. Please click on a tag to select it.';
$string['multilang2:language'] = 'Language';
$string['multilang2:multiBlockElements'] = 'Selected text spans multiple paragraphs/block elements. Please select one only.';
$string['multilang2:other'] = 'Fallback';
$string['multilang2:removetag'] = 'Remove language tag';
$string['multilang2:use'] = 'Use language dropdown menu in TinyMCE';
$string['pluginname'] = 'Multi-Language Content (v2)';
$string['privacy:metadata'] = 'The Tiny Multi-Language Content (v2) plugin does not store any personal data.';
$string['removealltags'] = 'Remove all lang tags';
$string['requiremultilang2'] = 'Require Multi-Language Content (v2) filter';
$string['requiremultilang2_desc'] = 'If enabled, the language drop down menu is visible only when the Multi-Language Content (v2) filter is enabled.';
$string['showalllangs'] = 'Show all languages';
$string['showalllangs_desc'] = 'If enabled, the language drop down menu will contain all the languages Moodle supports. If not, only the installed and enabled languages will be shown.';

// Deprecated since Moodle 4.5.
$string['helplinktext'] = 'Multi-Language Content (v2)';
// Deprecated since tiny_multilang 1.4.
$string['multilang2:viewlanguagemenu'] = 'View language dropdown menu in TinyMCE editor';
