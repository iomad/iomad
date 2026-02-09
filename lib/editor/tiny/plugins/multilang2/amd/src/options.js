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
 * Options helper for multilang2 plugin.
 *
 * @module      tiny_multilang2
 * @author      Iñaki Arenaza <iarenaza@mondragon.edu>
 * @author      Stephan Robotta <stephan.robotta@bfh.ch>
 * @copyright   2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const languages = getPluginOptionName(pluginName, 'languages');
const mlangfilter = getPluginOptionName(pluginName, 'mlangfilter');
const showalllangs = getPluginOptionName(pluginName, 'showalllangs');
const highlight = getPluginOptionName(pluginName, 'highlight');
const highlightcss = getPluginOptionName(pluginName, 'css');
const addlanguage = getPluginOptionName(pluginName, 'addlanguage');
const languageoptions = getPluginOptionName(pluginName, 'optionlanguages');

/**
 * Register the options for the Tiny Equation plugin.
 *
 * @param {tinymce.Editor} editor
 */
export const register = (editor) => {
    editor.options.register(languages, {
        processor: 'Array',
        "default": [],
    });

    editor.options.register(mlangfilter, {
        processor: 'boolean',
        "default": false,
    });

    editor.options.register(showalllangs, {
        processor: 'boolean',
        "default": false,
    });

    editor.options.register(highlight, {
        processor: 'boolean',
        "default": false,
    });

    editor.options.register(highlightcss, {
        processor: 'string',
       "default": '',
    });

    editor.options.register(addlanguage, {
        processor: 'boolean',
        "default": false,
    });

    editor.options.register(languageoptions, {
        processor: 'Array',
        "default": [],
    });
};

/**
 * Get the list of languages that are used for the translation button/menu item.
 *
 * @param {tinymce.Editor} editor
 * @returns {Array}
 */
export const getLanguageList = (editor) => editor.options.get(languages);

/**
 * Get the information whether the multilang2 filter is installed or not.
 * @param {tinymce.Editor} editor
 * @return {boolean}
 */
export const mlangFilterExists = (editor) => editor.options.get(mlangfilter);

/**
 * Get the option whether to show all languages or not.
 *
 * @param {tinymce.Editor} editor
 * @returns {boolean}
 */
export const showAllLanguages = (editor) => editor.options.get(showalllangs);

/**
 * Get the defined option whether to highlight the language dependent content blocks.
 *
 * @param {tinymce.Editor} editor
 * @returns {boolean}
 */
export const isContentToHighlight = (editor) => editor.options.get(highlight);

/**
 * Get the highlight css in case the language dependent block are supposed to be emphasized.
 *
 * @param {tinymce.Editor} editor
 * @returns {string}
 */
export const getHighlightCss = (editor) => editor.options.get(highlightcss);

/**
 * Get the defined option whether to add language manually.
 *
 * @param {tinymce.Editor} editor
 * @returns {boolean}
 */
export const isAddLanguage = (editor) => editor.options.get(addlanguage);

/**
 * Returns an array of all the languages that have the direction right to left (RTL).
 *
 * @returns {string[]} An array of language codes representing RTL languages.
 */
export const getRTLLanguages = () => ['ar', 'az', 'dv', 'he', 'ku', 'fa', 'ur'];
