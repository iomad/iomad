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
 * Commands for the plugin logic of the Moodle tiny_multilang2 plugin.
 *
 * @module      tiny_multilang2
 * @author      Iñaki Arenaza <iarenaza@mondragon.edu>
 * @author      Stephan Robotta <stephan.robotta@bfh.ch>
 * @author      Tai Le Tan <dev.tailetan@gmail.com>
 * @copyright   2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getHighlightCss, isContentToHighlight, mlangFilterExists} from './options';
import {isNull, trim, blockTags, spanMultilangBegin, spanMultilangEnd} from './constants';
import {parseEditorContent} from './htmlparser';
/**
 * Marker to remember that the submit button was hit.
 * @type {boolean}
 * @private
 */
let _isSubmit = false;

/**
 * @type {object}
 * @private
 */
const _options = {};

/**
 * Convert {mlang xx} and {mlang} strings to spans, so we can style them visually.
 * Remove superflous whitespace while at it.
 * @param {tinymce.Editor} ed
 * @return {string}
 */
const addVisualStyling = function(ed) {

    // Parse the editor content and check for all {mlang} tags that are in the html content.
    let content = parseEditorContent(ed.getContent());

    // Check for the traditional <span class="multilang"> tags, in case these were used as well in the text.
    // Any <span class="multilang"> tag must be replaced with a <span class="multilang-begin...>{mlang XX}</span>
    // and the corresponding closing </span> must be replaced by <span class="multilang-end ...>{mlang}</span>.
    // To handle this, we must convert the string into a DOMDocument so that any span.multilang tag can be searched
    // and replaced.
    const dom = new DOMParser();
    const doc = dom.parseFromString(content, 'text/html');
    if (doc.children.length === 0) { // Should not happen, but anyway, keep the check.
        return content;
    }
    const nodes = doc.querySelectorAll('span.multilang');
    if (nodes.length === 0) {
        return content;
    }
    for (const span of nodes) {
        const newSpan = spanMultilangBegin
            .replace(new RegExp('%lang', 'g'), span.getAttribute('lang'))
            .replace('mceNonEditable', 'mceNonEditable fallback')
          + span.innerHTML
          + spanMultilangEnd
            .replace('mceNonEditable', 'mceNonEditable fallback');
        // Insert the replacement string after the span tag itself by converting it into a html fragment.
        span.insertAdjacentHTML('afterend', newSpan);
        // Once the new tags are placed at the correct position, we can remove the original span tag.
        span.remove();
    }
    // Convert the DOMDocument into a string again.
    return doc.getElementsByTagName('body')[0].innerHTML;
};

/**
 * Remove the spans we added in _add_visual_styling() to leave only the {mlang xx} and {mlang} tags.
 * Also make sure we lowercase the multilang 'tags'
 * @param {tinymce.Editor} ed
 */
const removeVisualStyling = function(ed) {
    ['begin', 'end'].forEach(function(t) {
        for (const span of ed.dom.select('span.multilang-' + t)) {
            if (t === 'begin' && span.classList.contains('fallback')) {
                // This placeholder tag was created from an oldstyle <span class="multilang"> tag.
                let innerHTML = '';
                let end = span;
                let toRemove = [];
                // Search the corresponding closing tag.
                while (end) {
                    end = end.nextSibling;
                    if (isNull(end)) { // Got a parent that does not exist. Stop here.
                        break;
                    }
                    if (!isNull(end.classList) && end.classList.contains('multilang-end')) {
                        // We found the multilang-end node, that needs to be removed, and also, we can stop here.
                        toRemove.push(end);
                        break;
                    }
                    // Sibling inside the tags need to be preserved, but moved to the innerHTML of the real
                    // span tag. Therefore, collect the node content as string and remember the real nodes
                    // to remove them later.
                    if (end.nodeType === 3) {
                        innerHTML += end.nodeValue;
                    } else if (end.nodeType === 1) {
                        innerHTML += end.outerHTML;
                    }
                    toRemove.push(end);
                }
                if (!isNull(end)) {
                    // Extract the language from the {mlang XX} tag.
                    const lang = span.innerHTML.match(new RegExp('{\\s*mlang\\s+([^}]+?)\\s*}', 'i'));
                    if (lang) {
                        /* Do not add the dir attribute as it breaks the Moodle language filter.
                        // Right to left default languages.
                        const rtlLanguages = getRTLLanguages();
                        const langCode = lang[1];
                        // Add dir="rtl" to the html tag any time the overall document direction is right-to-left.
                        const dir = rtlLanguages.includes(langCode) ? 'rtl' : 'ltr';
                        // Do not add the dir attribute as it breaks the Moodle language filter.
                        const newHTML = `<span class="multilang" lang="${lang[1]}" dir="${dir}">${innerHTML}</span>`;
                        */
                        const newHTML = `<span class="multilang" lang="${lang[1]}">${innerHTML}</span>`;
                        ed.dom.setOuterHTML(span, newHTML);
                        // And remove the other siblings.
                        for (end of toRemove) {
                            ed.dom.remove(end);
                        }
                    }
                }
            } else {
                // Normal placeholder tag, just restore the innerHTML that is {mlang XX} or {mlang}-
                ed.dom.setOuterHTML(span, span.innerHTML.toLowerCase());
            }
        }
    });
};

/**
 * At the current selection lookup for the current node. If we are inside a special span that
 * encapsulates the {lang} tag, then look for the corresponding opening or closing tag,
 * depending on what's set in the search param.
 * @param {tinymce.Editor} ed
 * @param {string} search
 * @return {Node|null} The encapsulating span tag if found.
 */
const getHighlightNodeFromSelect = function(ed, search) {
    let span;
    ed.dom.getParents(ed.selection.getStart(), elm => {
        // Are we in a span that highlights the lang tag.
        if (!isNull(elm.classList)) {
            // If we are on an opening/closing lang tag, we need to search for the
            // corresponding closing/opening tag.
            const counterpart = search === 'begin' ? 'end' : 'begin';
            if (elm.classList.contains('multilang-' + counterpart)) {
                // If we look for begin, go back the document, otherwise go down the doccument.
                // Search for the next corresponding tag in the complete hierarchy.
                span = search === 'begin' ? findClosestAncestor(ed, elm) : findClosestSuccessor(ed, elm);
            } else if (elm.classList.contains('multilang-' + search)) {
                // We are already on the correct tag we search for
                span = elm;
            }
        }
    });
    return span;
};

/**
 * Find the closest ancestor span that is a multilang-begin span in relation to the given
 * span.multilang-end tag.
 * @param {tinymce.Editor} ed
 * @param {Node} node
 * @returns {Node|null}
 */
const findClosestAncestor = function(ed, node) {
    const nodeList = ed.dom.select('span.multilang-begin, span.multilang-end');
    for (let i = 0; i < nodeList.length; i++) {
        if (nodeList[i] === node && i > 0 &&
            isMultilangTag(nodeList[i - 1], 'begin')
        ) {
            return nodeList[i - 1];
        }
    }
    return null;
};

/**
 * Find the closest successor span that is a multilang-end span in relation to the given
 * span.multilang-begin tag.
 * @param {tinymce.Editor} ed
 * @param {Node} node
 * @returns {Node|null}
 */
const findClosestSuccessor = function(ed, node) {
    const nodeList = ed.dom.select('span.multilang-begin, span.multilang-end');
    for (let i = 0; i < nodeList.length; i++) {
        if (nodeList[i] === node && i < nodeList.length - 1 &&
            isMultilangTag(nodeList[i + 1], 'end')
        ) {
            return nodeList[i + 1];
        }
    }
    return null;
};

/**
 * Returns true if the given node is a multilang (begin|end) tag by checking whether the
 * appropriate class is set.
 * @param {Node} node
 * @param {string} search
 * @returns {boolean}
 */
const isMultilangTag = function(node, search) {
    return !isNull(node.classList) && node.classList.contains('multilang-' + search);
};

/**
 * From the given text (that is derived from a selection) we try to check if we have block elements selected and
 * in case yes, how many.
 * Return an object with:
 *  el: the first block element node from the string
 *  cnt: number of block elements found on the first level
 * In case the text fragment is not valid parsable HTML, then null and 0 is returned.
 * @param {string} text
 * @return {object}
 */
const getBlockElement = function(text) {
    let result = {el: null, cnt: 0};
    const dom = new DOMParser();
    const body = dom.parseFromString(text, 'text/html').body;
    // If the children nodes start with no block element, then just quit here.
    if (body.firstChild.nodeType !== Node.ELEMENT_NODE) {
        return result;
    }
    // Lang tags should be placed inside block elements.
    for (let i = 0; i < body.children.length; i++) {
        if (body.children[i].nodeType !== Node.ELEMENT_NODE) {
            continue;
        }
        if (blockTags.indexOf(body.children[i].tagName.toString().toLowerCase()) !== -1) {
            result.cnt += 1;
            if (isNull(result.el)) {
                result.el = body.children[i];
            }
        }
    }
    return result;
};

/**
 * Check for the parent hierarchy elements, if there's a context toolbar container, then hide it.
 * @param {Node} el
 */
const hideContentToolbar = function(el) {
    while (!isNull(el)) {
        if (el.nodeType === Node.ELEMENT_NODE &&
            !isNull(el.getAttribute('class')) &&
            el.getAttribute('class').indexOf('tox-pop-') != -1
        ) {
            el.style.display = 'none';
            return;
        }
        el = el.parentNode;
    }
};

/**
 * When loading the editor for the first time, add the spans for highlighting the lang tags.
 * These are highlighted with the appropriate css only.
 * In addition pass some options to the plugin instance.
 * @param {tinymce.Editor} ed
 * @param {object} options
 */
const onInit = function(ed, options) {
    Object.keys(options).forEach(function(key) {
        _options[key] = options[key];
    });
    ed.setContent(addVisualStyling(ed));
    if (isContentToHighlight(ed)) {
        ed.dom.addStyle(getHighlightCss(ed));
    }
};

/**
 * When the source code view dialogue is show, we must remove the highlight spans from the editor content
 * and also add them again when the dialogue is closed.
 * @param {tinymce.Editor} ed
 * @param {object} content
 */
const onBeforeGetContent = function(ed, content) {
    if (!isNull(content.source_view) && content.source_view === true) {
        // If the user clicks on 'Cancel' or the close button on the html
        // source code dialog view, make sure we re-add the visual styling.
        const onClose = function(ed) {
            ed.off('close', onClose);
            ed.setContent(addVisualStyling(ed));
        };
        const observer = new MutationObserver((mutations, obs) => {
            const viewSrcModal = document.querySelector('[data-region="modal"]');
            if (viewSrcModal) {
                viewSrcModal.addEventListener('click', (event) => {
                    const {action} = event.target.dataset;
                    if (['cancel', 'save', 'hide'].includes(action)) {
                        onClose(ed);
                    }
                });
                // Stop observing once the modal is found.
                obs.disconnect();
                return;
            }
            const tinyMceModal = document.querySelector('.tox-dialog-wrap');
            if (tinyMceModal) {
                ed.on('CloseWindow', () => {
                    onClose(ed);
                });
                obs.disconnect();
            }
        });
        observer.observe(document.body, {childList: true, subtree: true});
        removeVisualStyling(ed);
    }
};

/**
 * When the submit button is hit, the marker spans are removed. However, if there's an error
 * in saving the content (via ajax) the editor remains with the cleaned content. Therefore,
 * we need to add the marker span elements once again when the user tries to change the content
 * of the editor.
 * @param {tinymce.Editor} ed
 */
const onFocus = function(ed) {
    if (_isSubmit) {
        // eslint-disable-next-line camelcase
        ed.setContent(addVisualStyling(ed), {no_events: true});
        _isSubmit = false;
    }
};

/**
 * Fires when the form containing the editor is submitted. Remove all the marker span elements.
 * @param {tinymce.Editor} ed
 */
const onSubmit = function(ed) {
    removeVisualStyling(ed);
    _isSubmit = true;
};

/**
 * Check for key press <del> when something is deleted. If that happens inside a highlight span
 * tag, then remove this tag and the corresponding that open/closes this lang tag.
 * @param {tinymce.Editor} ed
 * @param {Object} event
 */
const onDelete = function(ed, event) {
    // We are not in composing mode, have not clicked and key <del> or <backspace> was not pressed.
    if (event.isComposing || (isNull(event.clientX) && event.keyCode !== 46 && event.keyCode !== 8)) {
        return;
    }
    // In case we clicked, check that we clicked an icon (this must have been the trash icon in the context menu).
    if (!isNull(event.clientX) &&
        (event.target.nodeType !== Node.ELEMENT_NODE || (event.target.nodeName !== 'path' && event.target.nodeName !== 'svg'))) {
        return;
    }
    // Conditions match either key <del> or <backspace> was pressed, or an click on an svg icon was done.
    // Check if we are inside a span for the language tag.
    const begin = getHighlightNodeFromSelect(ed, 'begin');
    const end = getHighlightNodeFromSelect(ed, 'end');
    // Only if both, start and end tags are found, then delete the nodes here and prevent the default handling
    // because the stuff to be deleted is already gone.
    if (!isNull(begin) && !isNull(end)) {
        event.preventDefault();
        ed.dom.remove(begin);
        ed.dom.remove(end);
        if (!isNull(event.clientX)) {
            hideContentToolbar(event.target);
        }
        cleanupBogus(ed);
    }
};

/**
 * In the tinyMCE of Moodle 4.1 and 4.2 some leftovers from the element selection can be seen when the source code
 * is displayed. Remove these. Apparently 4.3 does not have this problem anymore.
 * @param {tinymce.Editor} ed
 */
const cleanupBogus = function(ed) {
    for (const span of ed.dom.select('span[class*="multilang"')) {
        const p = span.parentElement;
        if (!isNull(p.classList) && p.classList.contains('mce-offscreen-selection')) {
            ed.dom.remove(p);
        }
    }
};

/**
 * The action when a language icon or menu entry is clicked. This adds the {mlang} tags at the current content
 * position or around the selection.
 * @param {tinymce.Editor} ed
 * @param {string} iso
 * @param {Event} event
 */
const applyLanguage = function(ed, iso, event) {
    if (isNull(iso)) {
        return;
    }
    if (iso === "remove") {
        const elements = ed.contentDocument.body;
        // Find all elements with the class "multilang-begin" or "multilang-end".
        const multiLangElements = elements.querySelectorAll('.multilang-begin, .multilang-end');
        multiLangElements.forEach(element => {
            ed.dom.remove(element);
        });
        return;
    }
    const regexLang = /%lang/g;
    let text = ed.selection.getContent();
    // Selection is empty, just insert the lang opening and closing tag
    // together with a space where the user may add the content.
    if (trim(text) === '') {
        // Event is set when the context menu was hit, here the editor lost the previously selected node. Therfore,
        // don't do anything.
        if (!isNull(event)) {
            hideContentToolbar(event.target);
            return;
        }
        let newtext = spanMultilangBegin.replace(regexLang, iso) + ' ' + spanMultilangEnd;
        if (!mlangFilterExists(ed)) {
            // No mlang filter, add the fallback class to the highlight spans so that these are translated
            // to the standard <span class="multilang"> elements.
            newtext = newtext.replaceAll('mceNonEditable', 'mceNonEditable fallback');
        }
        ed.insertContent(newtext);
        return;
    }
    // Hide context toolbar, because at any subsequent call the node is not selected anymore.
    if (!isNull(event)) {
        hideContentToolbar(event.target);
    }
    // No matter if we have syntax highlighting enabled or not, the spans around the language tags exist
    // in the WYSIWYG mode. So check if we are on a special span that encapsulates the language tags. Search
    // for the start span tag.
    const span = getHighlightNodeFromSelect(ed, 'begin');
    // If we have a span, then it's the opening tag, and we just replace this one with the new iso.
    if (!isNull(span)) {
        let replacement = spanMultilangBegin.replace(regexLang, iso);
        if (span.classList.contains('fallback')) {
            replacement = replacement.replace('mceNonEditable', 'mceNonEditable fallback');
        }
        ed.dom.setOuterHTML(span, replacement);
        cleanupBogus(ed);
        return;
    }
    // Check if we have language tags inside the selection:
    if (text.indexOf('multilang-begin') !== -1 || text.indexOf('multilang-end') !== -1) {
        ed.notificationManager.open({
                text: _options.langInSelectionErrMsg,
                type: 'error',
            });
        return;
    }
    const block = getBlockElement(text);
    if (!isNull(block.el)) {
        if (block.cnt === 1) {
            // We have a block element selected, such as a hX or p tag. Then keep this tag and place the
            // language tags inside but around the content of the block element.
            let newtext = spanMultilangBegin.replace(regexLang, iso) + block.el.innerHTML + spanMultilangEnd;
            if (!mlangFilterExists(ed)) { // No mlang filter, add the fallback class to the highlight spans.
                newtext = newtext.replaceAll('mceNonEditable', 'mceNonEditable fallback');
            }
            block.el.innerHTML = newtext;
            ed.selection.setContent(block.el.outerHTML);
            return;
        }
        if (!mlangFilterExists(ed)) {
            ed.notificationManager.open({
                text: _options.multipleBlocksErrMsg,
                type: 'error',
            });
            return;
        }
    }
    // Not inside a lang tag, insert a new opening and closing tag with the selection inside.
    let newtext = spanMultilangBegin.replace(regexLang, iso) + text + spanMultilangEnd;
    if (!mlangFilterExists(ed)) { // No mlang filter, add the fallback class to the highlight spans.
        newtext = newtext.replaceAll('mceNonEditable', 'mceNonEditable fallback');
    }
    ed.selection.setContent(newtext);
};

export {
    onInit,
    onBeforeGetContent,
    onFocus,
    onSubmit,
    onDelete,
    applyLanguage
};
