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
 * Template renderer for Moodle. Load and render Moodle templates with Mustache.
 *
 * @module     core/templates
 * @package    core
 * @class      templates
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['core/mustache',
         'jquery',
         'core/ajax',
         'core/str',
         'core/notification',
         'core/url',
         'core/config',
         'core/localstorage',
         'core/icon_system',
         'core/event',
         'core/yui',
         'core/log',
         'core/truncate',
         'core/user_date'
       ],
       function(mustache, $, ajax, str, notification, coreurl, config, storage, IconSystem, event, Y, Log, Truncate, UserDate) {

    // Module variables.
    /** @var {Number} uniqInstances Count of times this constructor has been called. */
    var uniqInstances = 0;

    /** @var {String[]} templateCache - Cache of already loaded template strings */
    var templateCache = {};

    /** @var {Promise[]} templatePromises - Cache of already loaded template promises */
    var templatePromises = {};

    /** @var {Object} iconSystem - Object extending core/iconsystem */
    var iconSystem = {};

    /**
     * Constructor
     *
     * Each call to templates.render gets it's own instance of this class.
     */
    var Renderer = function() {
        this.requiredStrings = [];
        this.requiredJS = [];
        this.requiredDates = [];
        this.currentThemeName = '';
    };
    // Class variables and functions.

    /** @var {string[]} requiredStrings - Collection of strings found during the rendering of one template */
    Renderer.prototype.requiredStrings = null;

    /** @var {object[]} requiredDates - Collection of dates found during the rendering of one template */
    Renderer.prototype.requiredDates = [];

    /** @var {string[]} requiredJS - Collection of js blocks found during the rendering of one template */
    Renderer.prototype.requiredJS = null;

    /** @var {String} themeName for the current render */
    Renderer.prototype.currentThemeName = '';

    /**
     * Load a template from the cache or local storage or ajax request.
     *
     * @method getTemplate
     * @private
     * @param {string} templateName - should consist of the component and the name of the template like this:
     *                              core/menu (lib/templates/menu.mustache) or
     *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
     * @return {Promise} JQuery promise object resolved when the template has been fetched.
     */
    Renderer.prototype.getTemplate = function(templateName) {
        var parts = templateName.split('/');
        var component = parts.shift();
        var name = parts.shift();

        var searchKey = this.currentThemeName + '/' + templateName;

        // First try request variables.
        if (searchKey in templatePromises) {
            return templatePromises[searchKey];
        }

        // Now try local storage.
        var cached = storage.get('core_template/' + searchKey);

        if (cached) {
            templateCache[searchKey] = cached;
            templatePromises[searchKey] = $.Deferred().resolve(cached).promise();
            return templatePromises[searchKey];
        }

        // Oh well - load via ajax.
        var promises = ajax.call([{
            methodname: 'core_output_load_template',
            args: {
                component: component,
                template: name,
                themename: this.currentThemeName
            }
        }], true, false);

        templatePromises[searchKey] = promises[0].then(
            function(templateSource) {
                templateCache[searchKey] = templateSource;
                storage.set('core_template/' + searchKey, templateSource);
                return templateSource;
            }
        );
        return templatePromises[searchKey];
    };

    /**
     * Load a partial from the cache or ajax.
     *
     * @method partialHelper
     * @private
     * @param {string} name The partial name to load.
     * @return {string}
     */
    Renderer.prototype.partialHelper = function(name) {

        var searchKey = this.currentThemeName + '/' + name;

        if (!(searchKey in templateCache)) {
            notification.exception(new Error('Failed to pre-fetch the template: ' + name));
        }

        return templateCache[searchKey];
    };

    /**
     * Render a single image icon.
     *
     * @method renderIcon
     * @private
     * @param {string} key The icon key.
     * @param {string} component The component name.
     * @param {string} title The icon title
     * @return {Promise}
     */
    Renderer.prototype.renderIcon = function(key, component, title) {
        // Preload the module to do the icon rendering based on the theme iconsystem.
        var modulename = config.iconsystemmodule;

        // RequireJS does not return a promise.
        var ready = $.Deferred();
        require([modulename], function(System) {
            var system = new System();
            if (!(system instanceof IconSystem)) {
                ready.reject('Invalid icon system specified' + config.iconsystemmodule);
            } else {
                iconSystem = system;
                system.init().then(ready.resolve).catch(notification.exception);
            }
        });

        return ready.then(function(iconSystem) {
            return this.getTemplate(iconSystem.getTemplateName());
        }.bind(this)).then(function(template) {
            return iconSystem.renderIcon(key, component, title, template);
        });
    };

    /**
     * Render image icons.
     *
     * @method pixHelper
     * @private
     * @param {object} context The mustache context
     * @param {string} sectionText The text to parse arguments from.
     * @param {function} helper Used to render the alt attribute of the text.
     * @return {string}
     */
    Renderer.prototype.pixHelper = function(context, sectionText, helper) {
        var parts = sectionText.split(',');
        var key = '';
        var component = '';
        var text = '';

        if (parts.length > 0) {
            key = helper(parts.shift().trim(), context);
        }
        if (parts.length > 0) {
            component = helper(parts.shift().trim(), context);
        }
        if (parts.length > 0) {
            text = helper(parts.join(',').trim(), context);
        }

        var templateName = iconSystem.getTemplateName();

        var searchKey = this.currentThemeName + '/' + templateName;
        var template = templateCache[searchKey];

        // The key might have been escaped by the JS Mustache engine which
        // converts forward slashes to HTML entities. Let us undo that here.
        key = key.replace(/&#x2F;/gi, '/');

        return iconSystem.renderIcon(key, component, text, template);
    };

    /**
     * Render blocks of javascript and save them in an array.
     *
     * @method jsHelper
     * @private
     * @param {object} context The current mustache context.
     * @param {string} sectionText The text to save as a js block.
     * @param {function} helper Used to render the block.
     * @return {string}
     */
    Renderer.prototype.jsHelper = function(context, sectionText, helper) {
        this.requiredJS.push(helper(sectionText, context));
        return '';
    };

    /**
     * String helper used to render {{#str}}abd component { a : 'fish'}{{/str}}
     * into a get_string call.
     *
     * @method stringHelper
     * @private
     * @param {object} context The current mustache context.
     * @param {string} sectionText The text to parse the arguments from.
     * @param {function} helper Used to render subsections of the text.
     * @return {string}
     */
    Renderer.prototype.stringHelper = function(context, sectionText, helper) {
        var parts = sectionText.split(',');
        var key = '';
        var component = '';
        var param = '';
        if (parts.length > 0) {
            key = parts.shift().trim();
        }
        if (parts.length > 0) {
            component = parts.shift().trim();
        }
        if (parts.length > 0) {
            param = parts.join(',').trim();
        }

        if (param !== '') {
            // Allow variable expansion in the param part only.
            param = helper(param, context);
        }
        // Allow json formatted $a arguments.
        if ((param.indexOf('{') === 0) && (param.indexOf('{{') !== 0)) {
            param = JSON.parse(param);
        }

        var index = this.requiredStrings.length;
        this.requiredStrings.push({key: key, component: component, param: param});

        // The placeholder must not use {{}} as those can be misinterpreted by the engine.
        return '[[_s' + index + ']]';
    };

    /**
     * Quote helper used to wrap content in quotes, and escape all quotes present in the content.
     *
     * @method quoteHelper
     * @private
     * @param {object} context The current mustache context.
     * @param {string} sectionText The text to parse the arguments from.
     * @param {function} helper Used to render subsections of the text.
     * @return {string}
     */
    Renderer.prototype.quoteHelper = function(context, sectionText, helper) {
        var content = helper(sectionText.trim(), context);

        // Escape the {{ and the ".
        // This involves wrapping {{, and }} in change delimeter tags.
        content = content
            .replace('"', '\\"')
            .replace(/([\{\}]{2,3})/g, '{{=<% %>=}}$1<%={{ }}=%>')
            ;
        return '"' + content + '"';
    };

    /**
     * Shorten text helper to truncate text and append a trailing ellipsis.
     *
     * @method shortenTextHelper
     * @private
     * @param {object} context The current mustache context.
     * @param {string} sectionText The text to parse the arguments from.
     * @param {function} helper Used to render subsections of the text.
     * @return {string}
     */
    Renderer.prototype.shortenTextHelper = function(context, sectionText, helper) {
        // Non-greedy split on comma to grab section text into the length and
        // text parts.
        var regex = /(.*?),(.*)/;
        var parts = sectionText.match(regex);
        // The length is the part matched in the first set of parethesis.
        var length = parts[1].trim();
        // The length is the part matched in the second set of parethesis.
        var text = parts[2].trim();
        var content = helper(text, context);
        return Truncate.truncate(content, {
            length: length,
            words: true,
            ellipsis: '...'
        });
    };

    /**
     * User date helper to render user dates from timestamps.
     *
     * @method userDateHelper
     * @private
     * @param {object} context The current mustache context.
     * @param {string} sectionText The text to parse the arguments from.
     * @param {function} helper Used to render subsections of the text.
     * @return {string}
     */
    Renderer.prototype.userDateHelper = function(context, sectionText, helper) {
        // Non-greedy split on comma to grab the timestamp and format.
        var regex = /(.*?),(.*)/;
        var parts = sectionText.match(regex);
        var timestamp = helper(parts[1].trim(), context);
        var format = helper(parts[2].trim(), context);
        var index = this.requiredDates.length;

        this.requiredDates.push({
            timestamp: timestamp,
            format: format
        });

        return '[[_t_' + index + ']]';
    };

    /**
     * Add some common helper functions to all context objects passed to templates.
     * These helpers match exactly the helpers available in php.
     *
     * @method addHelpers
     * @private
     * @param {Object} context Simple types used as the context for the template.
     * @param {String} themeName We set this multiple times, because there are async calls.
     */
    Renderer.prototype.addHelpers = function(context, themeName) {
        this.currentThemeName = themeName;
        this.requiredStrings = [];
        this.requiredJS = [];
        context.uniqid = (uniqInstances++);
        context.str = function() {
          return this.stringHelper.bind(this, context);
        }.bind(this);
        context.pix = function() {
          return this.pixHelper.bind(this, context);
        }.bind(this);
        context.js = function() {
          return this.jsHelper.bind(this, context);
        }.bind(this);
        context.quote = function() {
          return this.quoteHelper.bind(this, context);
        }.bind(this);
        context.shortentext = function() {
          return this.shortenTextHelper.bind(this, context);
        }.bind(this);
        context.userdate = function() {
          return this.userDateHelper.bind(this, context);
        }.bind(this);
        context.globals = {config: config};
        context.currentTheme = themeName;
    };

    /**
     * Get all the JS blocks from the last rendered template.
     *
     * @method getJS
     * @private
     * @return {string}
     */
    Renderer.prototype.getJS = function() {
        var js = '';
        if (this.requiredJS.length > 0) {
            js = this.requiredJS.join(";\n");
        }

        return js;
    };

    /**
     * Treat strings in content.
     *
     * The purpose of this method is to replace the placeholders found in a string
     * with the their respective translated strings.
     *
     * Previously we were relying on String.replace() but the complexity increased with
     * the numbers of strings to replace. Now we manually walk the string and stop at each
     * placeholder we find, only then we replace it. Most of the time we will
     * replace all the placeholders in a single run, at times we will need a few
     * more runs when placeholders are replaced with strings that contain placeholders
     * themselves.
     *
     * @param {String} content The content in which string placeholders are to be found.
     * @param {Array} strings The strings to replace with.
     * @return {String} The treated content.
     */
    Renderer.prototype.treatStringsInContent = function(content, strings) {
        var pattern = /\[\[_s\d+\]\]/,
            treated,
            index,
            strIndex,
            walker,
            char,
            strFinal;

        do {
            treated = '';
            index = content.search(pattern);
            while (index > -1) {

                // Copy the part prior to the placeholder to the treated string.
                treated += content.substring(0, index);
                content = content.substr(index);
                strIndex = '';
                walker = 4; // 4 is the length of '[[_s'.

                // Walk the characters to manually extract the index of the string from the placeholder.
                char = content.substr(walker, 1);
                do {
                    strIndex += char;
                    walker++;
                    char = content.substr(walker, 1);
                } while (char != ']');

                // Get the string, add it to the treated result, and remove the placeholder from the content to treat.
                strFinal = strings[parseInt(strIndex, 10)];
                if (typeof strFinal === 'undefined') {
                    Log.debug('Could not find string for pattern [[_s' + strIndex + ']].');
                    strFinal = '';
                }
                treated += strFinal;
                content = content.substr(6 + strIndex.length); // 6 is the length of the placeholder without the index: '[[_s]]'.

                // Find the next placeholder.
                index = content.search(pattern);
            }

            // The content becomes the treated part with the rest of the content.
            content = treated + content;

            // Check if we need to walk the content again, in case strings contained placeholders.
            index = content.search(pattern);

        } while (index > -1);

        return content;
    };

    /**
     * Treat strings in content.
     *
     * The purpose of this method is to replace the date placeholders found in the
     * content with the their respective translated dates.
     *
     * @param {String} content The content in which string placeholders are to be found.
     * @param {Array} strings The strings to replace with.
     * @return {String} The treated content.
     */
    Renderer.prototype.treatDatesInContent = function(content, dates) {
        dates.forEach(function(date, index) {
            var key = '\\[\\[_t_' + index + '\\]\\]';
            var re = new RegExp(key, 'g');
            content = content.replace(re, date);
        });

        return content;
    };

    /**
     * Render a template and then call the callback with the result.
     *
     * @method doRender
     * @private
     * @param {string} templateSource The mustache template to render.
     * @param {Object} context Simple types used as the context for the template.
     * @param {String} themeName Name of the current theme.
     * @return {Promise} object
     */
    Renderer.prototype.doRender = function(templateSource, context, themeName) {
        this.currentThemeName = themeName;
        var iconTemplate = iconSystem.getTemplateName();

        return this.getTemplate(iconTemplate).then(function() {
            this.addHelpers(context, themeName);
            var result = mustache.render(templateSource, context, this.partialHelper.bind(this));
            return $.Deferred().resolve(result.trim(), this.getJS()).promise();
        }.bind(this))
        .then(function(html, js) {
            if (this.requiredStrings.length > 0) {
                return str.get_strings(this.requiredStrings).then(function(strings) {

                    // Make sure string substitutions are done for the userdate
                    // values as well.
                    this.requiredDates = this.requiredDates.map(function(date) {
                        return {
                            timestamp: this.treatStringsInContent(date.timestamp, strings),
                            format: this.treatStringsInContent(date.format, strings)
                        };
                    }.bind(this));

                    // Why do we not do another call the render here?
                    //
                    // Because that would expose DOS holes. E.g.
                    // I create an assignment called "{{fish" which
                    // would get inserted in the template in the first pass
                    // and cause the template to die on the second pass (unbalanced).
                    html = this.treatStringsInContent(html, strings);
                    js = this.treatStringsInContent(js, strings);
                    return $.Deferred().resolve(html, js).promise();
                }.bind(this));
            }

            return $.Deferred().resolve(html, js).promise();
        }.bind(this))
        .then(function(html, js) {
            // This has to happen after the strings replacement because you can
            // use the string helper in content for the user date helper.
            if (this.requiredDates.length > 0) {
                return UserDate.get(this.requiredDates).then(function(dates) {
                    html = this.treatDatesInContent(html, dates);
                    js = this.treatDatesInContent(js, dates);
                    return $.Deferred().resolve(html, js).promise();
                }.bind(this));
            }

            return $.Deferred().resolve(html, js).promise();
        }.bind(this));
    };

    /**
     * Execute a block of JS returned from a template.
     * Call this AFTER adding the template HTML into the DOM so the nodes can be found.
     *
     * @method runTemplateJS
     * @param {string} source - A block of javascript.
     */
    var runTemplateJS = function(source) {
        if (source.trim() !== '') {
            var newscript = $('<script>').attr('type', 'text/javascript').html(source);
            $('head').append(newscript);
        }
    };

    /**
     * Do some DOM replacement and trigger correct events and fire javascript.
     *
     * @method domReplace
     * @private
     * @param {JQuery} element - Element or selector to replace.
     * @param {String} newHTML - HTML to insert / replace.
     * @param {String} newJS - Javascript to run after the insertion.
     * @param {Boolean} replaceChildNodes - Replace only the childnodes, alternative is to replace the entire node.
     */
    var domReplace = function(element, newHTML, newJS, replaceChildNodes) {
        var replaceNode = $(element);
        if (replaceNode.length) {
            // First create the dom nodes so we have a reference to them.
            var newNodes = $(newHTML);
            var yuiNodes = null;
            // Do the replacement in the page.
            if (replaceChildNodes) {
                // Cleanup any YUI event listeners attached to any of these nodes.
                yuiNodes = new Y.NodeList(replaceNode.children().get());
                yuiNodes.destroy(true);

                // JQuery will cleanup after itself.
                replaceNode.empty();
                replaceNode.append(newNodes);
            } else {
                // Cleanup any YUI event listeners attached to any of these nodes.
                yuiNodes = new Y.NodeList(replaceNode.get());
                yuiNodes.destroy(true);

                // JQuery will cleanup after itself.
                replaceNode.replaceWith(newNodes);
            }
            // Run any javascript associated with the new HTML.
            runTemplateJS(newJS);
            // Notify all filters about the new content.
            event.notifyFilterContentUpdated(newNodes);
        }
    };

    /**
     * Scan a template source for partial tags and return a list of the found partials.
     *
     * @method scanForPartials
     * @private
     * @param {string} templateSource - source template to scan.
     * @return {Array} List of partials.
     */
    Renderer.prototype.scanForPartials = function(templateSource) {
        var tokens = mustache.parse(templateSource),
            partials = [];

        var findPartial = function(tokens, partials) {
            var i, token;
            for (i = 0; i < tokens.length; i++) {
                token = tokens[i];
                if (token[0] == '>' || token[0] == '<') {
                    partials.push(token[1]);
                }
                if (token.length > 4) {
                    findPartial(token[4], partials);
                }
            }
        };

        findPartial(tokens, partials);

        return partials;
    };

    /**
     * Load a template and scan it for partials. Recursively fetch the partials.
     *
     * @method cachePartials
     * @private
     * @param {string} templateName - should consist of the component and the name of the template like this:
     *                              core/menu (lib/templates/menu.mustache) or
     *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
     * @return {Promise} JQuery promise object resolved when all partials are in the cache.
     */
    Renderer.prototype.cachePartials = function(templateName) {
        return this.getTemplate(templateName).then(function(templateSource) {
            var i;
            var partials = this.scanForPartials(templateSource);
            var fetchThemAll = [];

            for (i = 0; i < partials.length; i++) {
                var searchKey = this.currentThemeName + '/' + partials[i];
                if (searchKey in templatePromises) {
                    fetchThemAll.push(templatePromises[searchKey]);
                } else {
                    fetchThemAll.push(this.cachePartials(partials[i]));
                }
            }

            return $.when.apply($, fetchThemAll).then(function() {
                return templateSource;
            });
        }.bind(this));
    };

    /**
     * Load a template and call doRender on it.
     *
     * @method render
     * @private
     * @param {string} templateName - should consist of the component and the name of the template like this:
     *                              core/menu (lib/templates/menu.mustache) or
     *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
     * @param {Object} context - Could be array, string or simple value for the context of the template.
     * @param {string} themeName - Name of the current theme.
     * @return {Promise} JQuery promise object resolved when the template has been rendered.
     */
    Renderer.prototype.render = function(templateName, context, themeName) {
        if (typeof (themeName) === "undefined") {
            // System context by default.
            themeName = config.theme;
        }

        this.currentThemeName = themeName;

        // Preload the module to do the icon rendering based on the theme iconsystem.
        var modulename = config.iconsystemmodule;

        var ready = $.Deferred();
        require([modulename], function(System) {
            var system = new System();
            if (!(system instanceof IconSystem)) {
                ready.reject('Invalid icon system specified' + config.iconsystem);
            } else {
                iconSystem = system;
                system.init().then(ready.resolve).catch(notification.exception);
            }
        });

        return ready.then(function() {
                return this.cachePartials(templateName);
            }.bind(this)).then(function(templateSource) {
                return this.doRender(templateSource, context, themeName);
            }.bind(this));
    };

    /**
     * Prepend some HTML to a node and trigger events and fire javascript.
     *
     * @method domPrepend
     * @private
     * @param {jQuery|String} element - Element or selector to prepend HTML to
     * @param {String} html - HTML to prepend
     * @param {String} js - Javascript to run after we prepend the html
     */
    var domPrepend = function(element, html, js) {
        var node = $(element);
        if (node.length) {
            // Prepend the html.
            node.prepend(html);
            // Run any javascript associated with the new HTML.
            runTemplateJS(js);
            // Notify all filters about the new content.
            event.notifyFilterContentUpdated(node);
        }
    };

    /**
     * Append some HTML to a node and trigger events and fire javascript.
     *
     * @method domAppend
     * @private
     * @param {jQuery|String} element - Element or selector to append HTML to
     * @param {String} html - HTML to append
     * @param {String} js - Javascript to run after we append the html
     */
    var domAppend = function(element, html, js) {
        var node = $(element);
        if (node.length) {
            // Append the html.
            node.append(html);
            // Run any javascript associated with the new HTML.
            runTemplateJS(js);
            // Notify all filters about the new content.
            event.notifyFilterContentUpdated(node);
        }
    };

    return /** @alias module:core/templates */ {
        // Public variables and functions.
        /**
         * Every call to render creates a new instance of the class and calls render on it. This
         * means each render call has it's own class variables.
         *
         * @method render
         * @private
         * @param {string} templateName - should consist of the component and the name of the template like this:
         *                              core/menu (lib/templates/menu.mustache) or
         *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
         * @param {Object} context - Could be array, string or simple value for the context of the template.
         * @param {string} themeName - Name of the current theme.
         * @return {Promise} JQuery promise object resolved when the template has been rendered.
         */
        render: function(templateName, context, themeName) {
            var renderer = new Renderer();
            return renderer.render(templateName, context, themeName);
        },

        /**
         * Every call to renderIcon creates a new instance of the class and calls renderIcon on it. This
         * means each render call has it's own class variables.
         *
         * @method renderIcon
         * @public
         * @param {string} key - Icon key.
         * @param {string} component - Icon component
         * @param {string} title - Icon title
         * @return {Promise} JQuery promise object resolved when the pix has been rendered.
         */
        renderPix: function(key, component, title) {
            var renderer = new Renderer();
            return renderer.renderIcon(key, component, title);
        },

        /**
         * Execute a block of JS returned from a template.
         * Call this AFTER adding the template HTML into the DOM so the nodes can be found.
         *
         * @method runTemplateJS
         * @param {string} source - A block of javascript.
         */
        runTemplateJS: runTemplateJS,

        /**
         * Replace a node in the page with some new HTML and run the JS.
         *
         * @method replaceNodeContents
         * @param {JQuery} element - Element or selector to replace.
         * @param {String} newHTML - HTML to insert / replace.
         * @param {String} newJS - Javascript to run after the insertion.
         */
        replaceNodeContents: function(element, newHTML, newJS) {
            domReplace(element, newHTML, newJS, true);
        },

        /**
         * Insert a node in the page with some new HTML and run the JS.
         *
         * @method replaceNode
         * @param {JQuery} element - Element or selector to replace.
         * @param {String} newHTML - HTML to insert / replace.
         * @param {String} newJS - Javascript to run after the insertion.
         */
        replaceNode: function(element, newHTML, newJS) {
            domReplace(element, newHTML, newJS, false);
        },

        /**
         * Prepend some HTML to a node and trigger events and fire javascript.
         *
         * @method prependNodeContents
         * @param {jQuery|String} element - Element or selector to prepend HTML to
         * @param {String} html - HTML to prepend
         * @param {String} js - Javascript to run after we prepend the html
         */
        prependNodeContents: function(element, html, js) {
            domPrepend(element, html, js);
        },

        /**
         * Append some HTML to a node and trigger events and fire javascript.
         *
         * @method appendNodeContents
         * @param {jQuery|String} element - Element or selector to append HTML to
         * @param {String} html - HTML to append
         * @param {String} js - Javascript to run after we append the html
         */
        appendNodeContents: function(element, html, js) {
            domAppend(element, html, js);
        }
    };
});
