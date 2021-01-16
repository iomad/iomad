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
 * Javascript helper function for IMS Content Package module.
 *
 * @package    mod
 * @subpackage imscp
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_imscp = {};

M.mod_imscp.init = function(Y) {

    var imscp_layout_widget;
    var imscp_current_node;
    var imscp_buttons = [];
    var imscp_bloody_labelclick = false;

    Y.use('yui2-resize', 'yui2-dragdrop', 'yui2-container', 'yui2-button', 'yui2-layout', 'yui2-treeview', 'yui2-json', 'yui2-event', function(Y) {

        var imscp_activate_item_by_index = function(index) {
            imscp_activate_item(Y.YUI2.widget.TreeView.getNode('imscp_tree', index));
        };

        var imscp_activate_item = function(node) {
            if (!node) {
                return;
            }
            imscp_current_node = node;
            imscp_current_node.highlight();

            var content = new Y.YUI2.util.Element('imscp_content');
            var obj;
            if (node.href) {
                try {
                    // First try IE way - it can not set name attribute later
                    // and also it has some restrictions on DOM access from object tag.
                    obj = document.createElement('<iframe id="imscp_object" src="' + node.href + '">');
                } catch (e) {
                    obj = document.createElement('object');
                    obj.setAttribute('id', 'imscp_object');
                    obj.setAttribute('type', 'text/html');
                    obj.setAttribute('data', node.href);
                }
            } else {
                // No href, so create links to children.
                obj = document.createElement('div');
                obj.setAttribute('id', 'imscp_child_list');

                var title = document.createElement('h2');
                title.appendChild(document.createTextNode(node.label));
                title.setAttribute('class', 'sectionname');
                obj.appendChild(title);

                var ul = document.createElement('ul');
                obj.appendChild(ul);
                for (var i = 0; i < node.children.length; i++) {
                    var childnode = node.children[i];
                    var li = document.createElement('li');
                    var a = document.createElement('a');
                    a.appendChild(document.createTextNode(childnode.label));
                    a.setAttribute('id', 'ref_' + childnode.index);
                    Y.YUI2.util.Event.addListener(a, "click", function () {
                        imscp_activate_item_by_index(this.id.substr(4));
                    });
                    ul.appendChild(li);
                    li.appendChild(a);
                }
            }
            var old = Y.YUI2.util.Dom.get('imscp_object');
            if (old) {
                content.replaceChild(obj, old);
            } else {
                old = Y.YUI2.util.Dom.get('imscp_child_list');
                if (old) {
                    content.replaceChild(obj, old);
                } else {
                    content.appendChild(obj);
                }
            }
            imscp_resize_frame();

            imscp_current_node.focus();
            imscp_fixnav();
        };

        /**
         * Enables/disables navigation buttons as needed.
         * @return void
         */
        var imscp_fixnav = function() {
            imscp_buttons[0].set('disabled', (imscp_skipprev(imscp_current_node) == null));
            imscp_buttons[1].set('disabled', (imscp_prev(imscp_current_node) == null));
            imscp_buttons[2].set('disabled', (imscp_up(imscp_current_node) == null));
            imscp_buttons[3].set('disabled', (imscp_next(imscp_current_node) == null));
            imscp_buttons[4].set('disabled', (imscp_skipnext(imscp_current_node) == null));
        };

        var imscp_resize_layout = function(alsowidth) {
            if (alsowidth) {
                var layout = Y.YUI2.util.Dom.get('imscp_layout');
                var newwidth = imscp_get_htmlelement_size('maincontent', 'width');
                layout.style.width = '600px';
                if (newwidth > 600) {
                    layout.style.width = newwidth + 'px';
                }
            }
            // Make sure that the max width of the TOC doesn't go to far.

            var left = imscp_layout_widget.getUnitByPosition('left');
            var maxwidth = parseInt(Y.YUI2.util.Dom.getStyle('imscp_layout', 'width'));
            left.set('maxWidth', (maxwidth - 10));
            var cwidth = left.get('width');
            if (cwidth > (maxwidth - 10)) {
                left.set('width', (maxwidth - 10));
            }

            var headerheight = imscp_get_htmlelement_size('page-header', 'height');
            var footerheight = imscp_get_htmlelement_size('page-footer', 'height');
            var newheight = parseInt(Y.YUI2.util.Dom.getViewportHeight()) - footerheight - headerheight - 20;
            if (newheight < 400) {
                newheight = 400;
            }
            imscp_layout_widget.setStyle('height', newheight + 'px');

            imscp_layout_widget.render();
            imscp_resize_frame();
        };

        var imscp_get_htmlelement_size = function(el, prop) {
            var val = Y.YUI2.util.Dom.getStyle(el, prop);
            if (val == 'auto') {
                if (el.get) {
                    el = el.get('element'); // Get real HTMLElement from YUI element.
                }
                val = Y.YUI2.util.Dom.getComputedStyle(Y.YUI2.util.Dom.get(el), prop);
            }
            return parseInt(val);
        };

        var imscp_resize_frame = function() {
            obj = Y.YUI2.util.Dom.get('imscp_object');
            if (obj) {
                var content = imscp_layout_widget.getUnitByPosition('center').get('wrap');
                // Basically trap IE6 and 7.
                if (Y.YUI2.env.ua.ie > 5 && Y.YUI2.env.ua.ie < 8) {
                    if( obj.style.setAttribute ) {
                        obj.style.setAttribute("cssText", 'width: ' + (content.offsetWidth - 6) + 'px; height: ' + (content.offsetHeight - 10) + 'px;');
                    }
                    else {
                        obj.style.setAttribute('width', (content.offsetWidth - 6) + 'px', 0);
                        obj.style.setAttribute('height', (content.offsetHeight - 10) + 'px', 0);
                    }
                }
                else {
                    obj.style.width = (content.offsetWidth - 6) + 'px';
                    obj.style.height = (content.offsetHeight - 10) + 'px';
                }
            }
        };

        var imscp_firstlinked = function(node) {
            // Return first item with an href.
            if (node.href) {
                return node;
            } else if (node.children) {
                return imscp_firstlinked(node.children[0]);
            } else {
                return null;
            }
        };

        var imscp_up = function(node) {
            if (node.depth > 0) {
                return node.parent;
            }
            return null;
        };

        var imscp_lastchild = function(node) {
            if (node.children.length) {
                return imscp_lastchild(node.children[node.children.length - 1]);
            } else {
                return node;
            }
        };

        var imscp_prev = function(node) {
            if (node.previousSibling && node.previousSibling.children.length) {
                return imscp_lastchild(node.previousSibling);
            }
            return imscp_skipprev(node);
        };

        var imscp_skipprev = function(node) {
            if (node.previousSibling) {
                return node.previousSibling;
            } else if (node.depth > 0) {
                return node.parent;
            }
            return null;
        };

        var imscp_next = function(node) {
            if (node.children.length) {
                return node.children[0];
            }
            return imscp_skipnext(node);
        };

        var imscp_skipnext = function(node) {
            if (node.nextSibling) {
                return node.nextSibling;
            } else if (node.depth > 0) {
                return imscp_skipnext(node.parent);
            }
            return null;
        };

        // Layout.
        Y.YUI2.widget.LayoutUnit.prototype.STR_COLLAPSE = M.util.get_string('hide', 'moodle');
        Y.YUI2.widget.LayoutUnit.prototype.STR_EXPAND = M.util.get_string('show', 'moodle');

        imscp_layout_widget = new Y.YUI2.widget.Layout('imscp_layout', {
            minWidth: 600,
            minHeight: 400,
            units: [
                { position: 'left', body: 'imscp_toc', header: M.util.get_string('toc', 'imscp'), width: 250, resize: true, gutter: '2px 5px 5px 2px', collapse: true, minWidth:150},
                { position: 'center', body: '<div id="imscp_content"></div>', gutter: '2px 5px 5px 2px', scroll: true}
            ]
        });
        imscp_layout_widget.render();
        var left = imscp_layout_widget.getUnitByPosition('left');
        left.on('collapse', function() {
            imscp_resize_frame();
        });
        left.on('expand', function() {
            imscp_resize_frame();
        });

        // Ugly resizing hack that works around problems with resizing of iframes and objects.
        left._resize.on('startResize', function() {
            obj = Y.YUI2.util.Dom.get('imscp_object');
            obj.style.display = 'none';
        });
        left._resize.on('endResize', function() {
            obj = Y.YUI2.util.Dom.get('imscp_object');
            obj.style.display = 'block';
            imscp_resize_frame();
        });

        // TOC tree.
        var tree = new Y.YUI2.widget.TreeView('imscp_tree');
        tree.singleNodeHighlight = true;
        tree.subscribe('clickEvent', function(oArgs) {
            imscp_activate_item(oArgs.node);
            if (oArgs.node.children.length) {
                imscp_bloody_labelclick = true;
            }
            Y.YUI2.util.Event.preventDefault(oArgs.event);
            return false;
        });
        tree.subscribe('collapse', function(node) {
            if (imscp_bloody_labelclick) {
                imscp_bloody_labelclick = false;
                return false;
            }
        });
        tree.subscribe('expand', function(node) {
            if (imscp_bloody_labelclick) {
                imscp_bloody_labelclick = false;
                return false;
            }
        });
        tree.expandAll();
        tree.render();

        var navbar = Y.YUI2.util.Dom.get('imscp_nav');
        navbar.style.display = 'block';

        // Navigation.
        imscp_buttons[0] = new Y.YUI2.widget.Button('nav_skipprev');
        imscp_buttons[1] = new Y.YUI2.widget.Button('nav_prev');
        imscp_buttons[2] = new Y.YUI2.widget.Button('nav_up');
        imscp_buttons[3] = new Y.YUI2.widget.Button('nav_next');
        imscp_buttons[4] = new Y.YUI2.widget.Button('nav_skipnext');
        imscp_buttons[0].on('click', function(ev) {
            imscp_activate_item(imscp_skipprev(imscp_current_node));
        });
        imscp_buttons[1].on('click', function(ev) {
            imscp_activate_item(imscp_prev(imscp_current_node));
        });
        imscp_buttons[2].on('click', function(ev) {
            imscp_activate_item(imscp_up(imscp_current_node));
        });
        imscp_buttons[3].on('click', function(ev) {
            imscp_activate_item(imscp_next(imscp_current_node));
        });
        imscp_buttons[4].on('click', function(ev) {
            imscp_activate_item(imscp_skipnext(imscp_current_node));
        });

        // Finally activate the first item.
        imscp_activate_item(imscp_firstlinked(tree.getRoot().children[0]));

        // Resizing.
        imscp_resize_layout(false);

        // Fix layout if window resized.
        window.onresize = function() {
            imscp_resize_layout(true);
        };
    });
};
