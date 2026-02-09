// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Some constants that are required throughout the plugin.
 *
 * @module      tiny_multilang2
 * @copyright   2024 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This class inside a <span> identified the {mlang} tag that is encapsulated in a span.
const spanClass = 'multilang-begin mceNonEditable';
// This is the <span> element with the data attribute.
const spanFixedAttrs = '<span contenteditable="false" class="' + spanClass + '" data-mce-contenteditable="false"';
// The begin span needs the language attributes inside the span and the mlang attribute.
export const spanMultilangBegin = spanFixedAttrs + ' lang="%lang" xml:lang="%lang">{mlang %lang}</span>';
// The end span doesn't need information about the used language.
export const spanMultilangEnd = spanFixedAttrs.replace('begin', 'end') + '>{mlang}</span>';
// Helper functions
export const trim = v => v.toString().replace(/^\s+/, '').replace(/\s+$/, '');
export const isNull = a => a === null || a === undefined;
// These are HTML block elements.
export const blockTags = ['address', 'article', 'aside', 'blockquote',
    'dd', 'div', 'dl', 'dt', 'figcaption', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'li', 'ol', 'p', 'pre', 'section', 'tfoot', 'ul'];