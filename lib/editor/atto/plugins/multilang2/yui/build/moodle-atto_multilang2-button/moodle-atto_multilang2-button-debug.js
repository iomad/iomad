YUI.add('moodle-atto_multilang2-button', function (Y, NAME) {

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
 * @package    atto_multilang2
 * @copyright  2015 onwards Julen Pardo & Mondragon Unibertsitatea
 * @copyright  2017 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_multilang2-button
 */

/**
 * Atto text editor multilanguage plugin.
 *
 * @namespace M.atto_multilang2
 * @class button
 * @extends M.editor_atto.EditorPlugin.
 */

var CLASSES = {
        TAG: 'filter-multilang-tag'
    },

    LANG_WILDCARD = '%lang',
    CONTENT_WILDCARD = '%content',
    ATTR_LANGUAGES = 'languages',
    ATTR_CAPABILITY = 'capability',
    ATTR_HIGHLIGHT = 'highlight',
    ATTR_CSS = 'css',
    DEFAULT_LANGUAGE = '{"en":"English (en)"}',
    DEFAULT_CAPABILITY = true,
    DEFAULT_HIGHLIGHT = true,
    DEFAULT_CSS = 'outline: 1px dotted;' +
                  'padding: 0.1em;' +
                  'margin: 0em 0.1em;' +
                  'background-color: #ffffaa;',
    OPENING_SPAN = '<span class="' + CLASSES.TAG + '">',
    CLOSING_SPAN = '</span>',
    TEMPLATES = {
        SPANNED: '&nbsp;' + OPENING_SPAN + '{mlang ' + LANG_WILDCARD + '}' + CLOSING_SPAN +
                 CONTENT_WILDCARD + OPENING_SPAN + '{mlang}' + CLOSING_SPAN + '&nbsp;',
        NOT_SPANNED: '{mlang ' + LANG_WILDCARD + '}' + CONTENT_WILDCARD + '{mlang}'
    };


Y.namespace('M.atto_multilang2').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * If the {mlang} tags have to be highlighted or not. Received as parameter from lib.php.
     *
     * @property _highlight
     * @type boolean
     * @private
     */
    _highlight: true,

    initializer: function() {
        var hascapability = this.get(ATTR_CAPABILITY),
            toolbarItems,
            host,
            form;

        if (hascapability) {
            toolbarItems = this._initializeToolbarItems();

            this.addToolbarMenu({
                globalItemConfig: {
                    callback: this._addTags
                },
                icon: 'icon',
                iconComponent: 'atto_multilang2',
                items: toolbarItems
            });

            this._tagTemplate = TEMPLATES.NOT_SPANNED;

            this._highlight = this.get(ATTR_HIGHLIGHT);
            if (this._highlight) {
                this._tagTemplate = TEMPLATES.SPANNED;

                // Attach a submit listener to the form, so we can remove
                // the highlighting html before sending content to Moodle.
                host = this.get('host');
                form = host.textarea.ancestor('form');
                if (form) {
                    form.on('submit', this._cleanMlangTags, this);
                }

                // Listen to every change of the text cursor in the text area, to see if
                // the cursor is placed within a multilang tag.
                this.get('host').on('atto:selectionchanged', this._checkSelectionChange, this);

                // Highlight the multilang tags once everything is loaded.
                this.get('host').on('pluginsloaded', this._addHighlightingCss, this);
                this.get('host').on('pluginsloaded', this._highlightMlangTags, this);

                // Hook into host.updateOriginal() and host.updateFromTextArea()
                // so we can add/remove highlighting when we switch to/from HTML view.
                this._hookUpdateOriginal();
                this._hookUpdateFromTextArea();
            }
        }
    },

    /**
     * Initializes the toolbar items, which will be the installed languages,
     * received as parameter.
     *
     * @method _initializeToolbarItems
     * @private
     * @return {Array} installed language strings
     */
    _initializeToolbarItems: function() {
        var toolbarItems = [],
            languages,
            langCode;

        languages = JSON.parse(this.get(ATTR_LANGUAGES));
        for (langCode in languages) {
            if (languages.hasOwnProperty(langCode)) {
                toolbarItems.push({
                    text: languages[langCode],
                    callbackArgs: langCode
                });
            }
        }

        return toolbarItems;
    },

    /**
     * Adds the CSS rules for the delimiters, received as parameter from lib.php.
     *
     * @method _addHighlightingCss
     * @private
     */
    _addHighlightingCss: function() {
        var css = '.' + CLASSES.TAG + ' {' + this.get(ATTR_CSS) + '}',
            style;

        style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = css;

        document.head.appendChild(style);
    },

    /**
     * Hook the host.updateOriginal() method to allow us to remove the highlighting html when
     * switching to HTML view. As the HTML view plugin doesn't provide a hook or fire an event
     * to notify about the switch to HTML view, we need to hijack host.updateOriginal and look
     * for the caller. Once we've cleaned up the highlighting, we need to execute the original
     * host.updateOriginal() method.
     * Inspired by https://stackoverflow.com/a/16580937
     *
     * @method _hookUpdateOriginal
     * @private
     */
    _hookUpdateOriginal: function() {
        var host = this.get('host'),
            multilangplugin = this; // Capture the plugin in the closure below, so we can invoke _removeTags().

        host.updateOriginal = (function() {
            var _updateOriginal = host.updateOriginal;
            return function() {
                if (multilangplugin._highlight && (this.updateOriginal.caller === host.plugins.html._showHTML)) {
                    multilangplugin.editor.setHTML(multilangplugin._getHTMLwithCleanedTags(multilangplugin.editor.getHTML()));
                }
                return _updateOriginal.apply(this, arguments);
            };
        })();
    },

    /**
     * Hook the host.updateFromTextAreal() method to allow us to re-add the highlighting
     * html when switching from HTML view. As the HTML view plugin doesn't provide a hook
     * or fire an event to notify about the switch from HTML view, we need to hijack
     * host.updateFromTextArea and look for the caller. Once we've executed the original
     * host.updateFromTextArea() method, we re-added the highlighting.
     * Inspired by https://stackoverflow.com/a/16580937
     *
     * @method _hookUpdateFromTextArea
     * @private
     */
    _hookUpdateFromTextArea: function() {
        var host = this.get('host'),
            multilangplugin = this; // Capture the plugin in the closure below, so we can invoke _highlightMlangTags().

        host.updateFromTextArea = (function() {
            var _updateFromTextArea = host.updateFromTextArea;
            return function() {
                var ret = _updateFromTextArea.apply(this, arguments);
                if (multilangplugin._highlight && (this.updateFromTextArea.caller === host.plugins.html._showHTML)) {
                    multilangplugin._highlightMlangTags();
                }
                return ret;
            };
        })();
    },

    /**
     * Retrieves the selected text, wraps it with the multilang tags,
     * and replaces the selected text in the editor with with it.
     *
     * If the 'highlight' setting is checked, the {mlang} will be wrapped between
     * the <span> tags with the class for the CSS highlight; if not, they will not
     * be wrapped.
     *
     * If there is no content selected, a "&nbsp;" will be inserted; otherwhise,
     * it's impossible to place the cursor inside the {mlang} tags.
     *
     * @method _addTags
     * @param {EventFacade} event
     * @param {string} langCode the language code
     * @private
     */
    _addTags: function(event, langCode) {
        var selection,
            host = this.get('host'),
            taggedContent,
            content;

        taggedContent = this._tagTemplate;

        selection = this._getSelectionHTML();
        content = (host.getSelection().toString().length === 0) ? '&nbsp;' : selection;

        taggedContent = taggedContent.replace(LANG_WILDCARD, langCode);
        taggedContent = taggedContent.replace(CONTENT_WILDCARD, content);

        host.insertContentAtFocusPoint(taggedContent);

        this.markUpdated();
    },

    /**
     * Retrieves selected text with its HTML.
     * Taken from: http://stackoverflow.com/questions/4176923/html-of-selected-text/4177234#4177234
     *
     * @method _getSelectionHTML
     * @private
     * @return {string} selected text's html; empty if nothing selected
     */
    _getSelectionHTML: function() {
        var html = '',
            selection,
            container,
            index,
            length;

        if (typeof window.getSelection !== 'undefined') {
            selection = window.getSelection();

            if (selection.rangeCount) {
                container = document.createElement('div');
                for (index = 0, length = selection.rangeCount; index < length; ++index) {
                    container.appendChild(selection.getRangeAt(index).cloneContents());
                }
                html = container.innerHTML;
            }

        } else if (typeof document.selection !== 'undefined') {
            if (document.selection.type === 'Text') {
                html = document.selection.createRange().htmlText;
            }
        }

        return html;
    },

    /**
     * Listens to every change of the text cursor in the text area. If the
     * cursor is placed within a highlighted multilang tag, the whole tag is selected.
     *
     * @method _checkSelectionChange
     * @private
     */
    _checkSelectionChange: function() {
        var host = this.get('host'),
            node = host.getSelectionParentNode(),
            parentNodeName,
            parentClass,
            selection;

        // If the event fires without a parent node for the selection, ignore the whole thing.
        if ((typeof node === 'undefined') || (node === null) || (node === false) ||
                (typeof node.parentNode === 'undefined') || (node.parentNode === null)) {
            return;
        }

        parentNodeName = node.parentNode.nodeName;
        parentClass = node.parentNode.hasAttribute('class') ? node.parentNode.getAttribute('class') : '';
        if ((typeof parentNodeName !== 'undefined') && (parentNodeName !== null) && (parentClass !== '') &&
                (parentNodeName === 'SPAN') && (parentClass.indexOf(CLASSES.TAG) !== -1)) {
            selection = host.getSelectionFromNode(Y.one(node));
            host.setSelection(selection);
        }
    },

     /**
     * When submitting the form, this function is invoked to clean the highlighting html code.
     *
     * @method _cleanMlangTags
     * @private
     */
    _cleanMlangTags: function() {
        if (this._highlight) {
            this.editor.setHTML(this._getHTMLwithCleanedTags(this.editor.getHTML()));
            this.markUpdated();
        }
    },

    /**
     * Adds the <span> tags to the {mlang} tags if highlighting is enable.
     *
     * Instead of taking the HTML directly from the textarea, we have to
     * retrieve it, first, without the <span> tags that can be stored
     * in database, due to a bug in version 2015120501 that stores the
     * {mlang} tags in database, with the <span> tags.
     * More info about this bug: https://github.com/julenpardo/moodle-atto_multilang2/issues/8
     *
     * Every different {mlang} tag has to be replaced only once, otherwise,
     * nested <span>s will be created in every repeated replacement. So, we
     * need to track which replacements have been made.
     *
     * @method _highlightMlangTags
     * @private
     */
    _highlightMlangTags: function() {
        var editorHTML,
            regularExpression,
            mlangtags,
            mlangtag,
            index,
            highlightedmlangtag,
            replacementsmade = [],
            notreplacedyet;
        if (this._highlight) {
            editorHTML = this._getHTMLwithCleanedTags(this.editor.getHTML());

            regularExpression = new RegExp('{mlang.*?}', 'g');
            mlangtags = editorHTML.match(regularExpression);
            if (mlangtags !== null) {
                for (index = 0; index < mlangtags.length; index++) {
                    mlangtag = mlangtags[index];

                    notreplacedyet = replacementsmade.indexOf(mlangtag) === -1;
                    if (notreplacedyet) {
                        replacementsmade.push(mlangtag);
                        highlightedmlangtag = OPENING_SPAN + mlangtag + CLOSING_SPAN;
                        regularExpression = new RegExp(mlangtag, 'g');
                        editorHTML = editorHTML.replace(regularExpression, highlightedmlangtag);
                    }
                }

                this.editor.setHTML(editorHTML);
            }

            this.markUpdated();
        }
    },

    /**
     * This function returns the HTML passed in as parameter, but cleaning every multilang
     * <span> tag around the {mlang} tags. This is necessary for decorating tags on
     * init, because it could happen that in database are stored the {mlang} tags with
     * their <span> tags, due to a bug in version 2015120501.
     * More info about this bug: https://github.com/julenpardo/moodle-atto_multilang2/issues/8
     * Implementation based on code from EditorClean._clearSpans()
     *
     * @method _getHTMLwithCleanedTags
     * @param {string} content The to be cleaned.
     * @return {string} HTML in editor, without any <span> around {mlang} tags.
     */
    _getHTMLwithCleanedTags: function(content) {
        // This is better to run detached from the DOM, so the browser doesn't try to update on each change.
        var holder = document.createElement('div'),
            spans,
            spansarr;

        holder.innerHTML = content;
        spans = holder.getElementsByTagName('span');

        // Since we will be removing elements from the list, we should copy it to an array, making it static.
        spansarr = Array.prototype.slice.call(spans, 0);

        spansarr.forEach(function(span) {
            if (span.className.indexOf(CLASSES.TAG) !== -1) {
                // Move each child (if they exist) to the parent in place of this span.
                while (span.firstChild) {
                    span.parentNode.insertBefore(span.firstChild, span);
                }

                // Remove the now empty span.
                span.parentNode.removeChild(span);
            }
        });

        return holder.innerHTML;
    }

}, {
    ATTRS: {
        /**
         * The list of installed languages.
         *
         * @attribute languages
         * @type array
         * @default {"en":"English (en)"}
         */
        languages: DEFAULT_LANGUAGE,

        /**
         * If the current user has the capability to use the plugin.
         *
         * @attribute capability
         * @type boolean
         * @default true
         */
        capability: DEFAULT_CAPABILITY,

        /**
         * If the {mlang} tags have to be highlighted or not.
         *
         * @property highlight
         * @type boolean
         * @default true
         */
        highlight: DEFAULT_HIGHLIGHT,

        /**
         * The CSS for delimiters.
         *
         * @property css
         * @type string
         * @default DEFAULT_CSS
         */
        css: DEFAULT_CSS
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
