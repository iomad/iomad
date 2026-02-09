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
 * Handling of the editor content to add and remove the visual styling and
 * helper nodes to modify language settings.
 *
 * @module      tiny_multilang2
 * @copyright   2024 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {spanMultilangBegin, spanMultilangEnd} from './constants';

/**
 * This class is used to parse HTML content and call a callback function
 * when a tag is opened, closed or text is found.
 */
class HTMLParser {
    constructor() {
        this.onTagOpen = null;
        this.onTagClose = null;
        this.onText = null;
        this.onComment = null;
        this.chunk = '';

        /**
         * Parser function that receives the HTML string matches tags, text and comments
         * and calls the corresponding callback functions.
         * @param {string} input The HTML content to parse.
         */
        this.parse = function(input) {
            let content = input;
            while (content.length > 0) {
                // Find the next < with chars and some time later a >.
                let match = content.match(/<[^>]*>/);
                if (match) {
                    let index = match.index;
                    if (index > 0) {
                        // The < was not at the beginning of the string.
                        // All before the < is text.
                        this.chunk = content.substring(0, index);
                        content = content.substring(index);
                        if (typeof this.onText === 'function') {
                            this.onText(this.chunk);
                        }
                    }
                    this.chunk = match[0];
                    // First char is a / so the matched tag is a closing tag.
                    if (match[0].charAt(1) === '/') {
                        if (typeof this.onTagClose === 'function') {
                            const tag = match[0].substring(2, match[0].length - 1).trim().toLowerCase();
                            this.onTagClose(tag);
                        }
                    } else if (match[0].indexOf('<!--') === 0) {
                        // We found the start of a comment.
                        let end = content.indexOf('-->');
                        if (end === -1) {
                            end = content.length;
                        } else {
                            end += 3;
                        }
                        this.chunk = content.substring(0, end);
                        if (typeof this.onComment === 'function') {
                            this.onComment(this.chunk);
                        }
                    } else if (typeof this.onTagOpen === 'function') {
                        // None of the above, so we have an opening tag.
                        const attr1 = this.mapAttrs(match[0].match(/([\w\-_]+)="([^"]*)"/g));
                        const attr2 = this.mapAttrs(match[0].match(/([\w\-_]+)='([^']*)'/g));
                        const tag = match[0].match(/^<(\w+)/);
                        this.onTagOpen(tag[1].toLowerCase(), {...attr1, ...attr2});
                    }
                    // Remove the chunk from the content, that we just parsed and start from the
                    // beginning again, to match the next tag.
                    content = content.substring(this.chunk.length);
                } else {
                    // No more tags, so the rest is text.
                    if (typeof this.onText === 'function') {
                        this.onText(content);
                    }
                    this.chunk = content;
                    content = '';
                }
            }
        };

        /**
         * During the parsing process, this function returns the current chunk of the
         * parsed HTML content. This is either text, an opening tag including attributes,
         * a closing tag or a comment (including comment tags).
         * @returns {string} The current chunk of the parsed HTML content.
         */
        this.getChunk = function() {
            return this.chunk;
        };

        /**
         * The function receives an array of attributes in the form of key="value" pairs
         * and returns an object with the key-value pairs.
         * @param {Array} attrs An array of key="value" pairs.
         * @returns {Object} An object with the key-value pairs of the attributes.
         */
        this.mapAttrs = function(attrs) {
            let res = {};
            if (attrs) {
                for (let i = 0; i < attrs.length; i++) {
                    let [k, v] = attrs[i].split('=');
                    res[k] = v ? v.substring(1, v.length) : null;
                }
            }
            return res;
        };
    }
}

/**
 * The parser function that receives the HTML content and parses it to add the
 * visual styling and helper nodes to modify language settings.
 * @param {string} html
 * @returns {string} The modified HTML content.
 */
export const parseEditorContent = function(html) {
    let newHtml = '';
    let mlang = 0;
    let inClose = false;
    const parser = new HTMLParser();

    /**
     * Callback function when an opening tag is found.
     * @param {string} tag
     * @param {object} attr
     */
    parser.onTagOpen = function(tag, attr) {
        if (tag === 'span' && attr.class && attr.class.indexOf('multilang-begin') > -1) {
            mlang++;
        } else if (tag === 'span' && attr.class && attr.class.indexOf('multilang-end') > -1 &&
            mlang > 0
        ) {
            mlang--;
            inClose = true;
        }
        newHtml += parser.getChunk();
    };

    /**
     * Callback function when a closing tag is found.
     * @param {string} tag
     */
    parser.onTagClose = function(tag) {
        if (tag === 'span' && inClose) {
            inClose = false;
        }
        newHtml += parser.getChunk();
    };

    /**
     * Callback function when text is found.
     * @param {string} text
     */
    parser.onText = function(text) {
        if (inClose) {
            newHtml += text;
            return;
        }
        const intermediateReplacements = [];
        // eslint-disable-next-line no-constant-condition
        while (1) {
            const m = text.match(new RegExp('{\\s*mlang(\\s+([^}]+?))?\\s*}', 'i'));
            if (!m) {
                break;
            }
            const textBefore = text.substring(0, m.index);
            const textAfter = text.substring(m.index + m[0].length);
            let r = m[0];
            if (!m[2]) {
                if (mlang === 1) {
                    r = spanMultilangEnd;
                }
                mlang--;
            } else {
                if (mlang === 0) {
                    r = spanMultilangBegin.replace(new RegExp('%lang', 'g'), m[2]);
                }
                mlang++;
            }
            intermediateReplacements.push(r);
            text = `${textBefore}___~~${intermediateReplacements.length}~~___${textAfter}`;
        }
        // Revert all placeholders back to the original {mlang} tags.
        for (let i = 0; i < intermediateReplacements.length; i++) {
            text = text.replace(`___~~${i + 1}~~___`, intermediateReplacements[i]);
        }
        newHtml += text;
    };

    /**
     * Callback function when a comment is found.
     * @param {string} comment
     */
    parser.onComment = function(comment) {
        newHtml += comment;
    };

    // Parse the HTML content.
    parser.parse(html);
    // And return the modified content.
    return newHtml;
};
