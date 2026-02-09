/**
 * lc_color_picker.js - The colorpicker for modern web
 * Version: 2.0.0
 * Author: Luca Montanari (LCweb)
 * Website: https://lcweb.it
 * Licensed under the MIT license
 */


(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
    typeof define === 'function' && define.amd ? define(factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.Chart = factory());
    })(this, (function () { 'use strict';

    if(typeof(window.lc_color_picker) != 'undefined') {return false;} // prevent multiple script inits


    /*** vars ***/
    let debounced_vars  = [],
        window_width   = null,

        style_generated = null,
        active_trigger  = null,
        active_trig_id  = null,

        active_solid    = null,
        active_opacity  = null,
        active_gradient = null,
        active_mode     = 'linear-gradient',

        sel_grad_step   = 0, // selected gradient step
        gradient_data   = {
            deg: 0,
            radial_circle: false,
            steps: [
                //{color : null, opacity: null, position : null}
            ],
        };


    /*** default options ***/
    const def_opts = {
        modes           : ['linear-gradient'], // (array) containing supported modes (solid | linear-gradient | radial-gradient)
        open_on_focus   : true, // (bool) whether to open the picker when field is focused
        transparency    : true, // (bool) whether to allow colors transparency tune
        dark_theme      : false, // (bool) whether to enable dark picker theme
        no_input_mode   : false, // (bool) whether to stretch the trigger in order to cover the whole input field
        wrap_width      : 'auto', // (string) defines the wrapper width. "auto" to leave it up to CSS, "inherit" to statically copy input field width, or any other CSS sizing
        preview_style   : { // (object) defining shape and position of the in-field preview
            input_padding   : 35, // extra px padding eventually added to the target input to not cover text
            side            : 'right', // right or left
            width           : 30,
            separator_color : '#ccc', // (string) CSS color applird to preview element as separator
        },
        fallback_colors : ['#008080', 'linear-gradient(90deg, #fff 0%, #000 100%)'], // (array) defining default colors used when trigger field has no value. First parameter for solid color, second for gradient

        on_change       : null, // function(new_value, target_field) {}, - triggered every time field value changes. Passes value and target field object as parameters

        labels          : [ // (array) option used to translate script texts
            'click to change color',
            'Solid',
            'Linear Gradient',
            'Radial Gradient',
            'add gradient step',
            'gradient angle',
            'gradient shape',
            'color',
            'opacity',
        ],
    };


    // shortcut var to target the text input only
    const right_input_selector = 'input:not([type="color"])';



    // input value check custom event
    const lccp_ivc_event = function(picker_id, hide_picker = false) {
        return new CustomEvent('lccp_input_val_check', {
            bubbles : true,
            detail: {
                picker_id   : picker_id,
                hide_picker : hide_picker
            }
        });
    };



    /*** hide picker cicking outside ***/
    document.addEventListener('click', function(e) {
        const picker = document.querySelector("#lc-color-picker.lccp-shown");
        if(!picker || e.target.classList.contains('lccp-preview')) {
            return true;
        }

        // is an element within a trigger?
        for (const trigger of document.getElementsByClassName('lccp-preview')) {
            if(trigger.contains(e.target)) {
                return true;
            }
        }

        // clicked on the same colorpicker field? keep visible
        if(e.target.parentNode && e.target.parentNode.classList && e.target.parentNode.classList.contains('lccp-el-wrap') && document.getElementById(active_trig_id)) {
            return true;
        }

        // close if clicked element is not in the picker
        if(!picker.contains(e.target) && !e.target.classList.contains('lccp-shown')) {
            const picker_id = picker.getAttribute('data-trigger-id'),
            $input = document.getElementById(picker_id).parentNode.querySelector(right_input_selector);

            $input.dispatchEvent(lccp_ivc_event(picker_id, true));
        }
        return true;
    });


    /* hide picker on screen resizing */
    window.addEventListener('resize', function(e) {
        const picker = document.querySelector("#lc-color-picker.lccp-shown");
        if(!picker || window_width == window.innerWidth) {
            return true;
        }

        // check field value
        const picker_id = picker.getAttribute('data-trigger-id'),
              $input = document.getElementById(picker_id).parentNode.querySelector(right_input_selector);

        $input.dispatchEvent(lccp_ivc_event(picker_id, true));
    });


    /* extend string object to ReplaceArray */
    String.prototype.lccpReplaceArray = function(find, replace) {
        let replaceString = this;
        let regex;

        for (var i = 0; i < find.length; i++) {
            const regex = new RegExp(find[i], "g");
            replaceString = (typeof(replace) == 'object') ? replaceString.replace(regex, replace[i]) : replaceString.replace(regex, replace);
        }
        return replaceString;
    };




    /*** plugin class ***/
    window.lc_color_picker = function(attachTo, options = {}) {
        let cp_uniqid, // unique ID assigned to this colorpicker instance
            last_tracked_col;

        this.attachTo = attachTo;
        if(!this.attachTo) {
            return console.error('You must provide a valid selector string first argument');
        }

        // override options
        if(typeof(options) !=  'object') {
            return console.error('Options must be an object');
        }

        const bkp_opts = options;
        options = Object.assign({}, def_opts, options);

        if(typeof(bkp_opts.preview_style) != 'undefined') {
            options.preview_style = Object.assign({}, def_opts.preview_style, bkp_opts.preview_style);
        }



        /* initialize */
        this.init = function() {
            const $this = this;

            // Generate style
            if(!style_generated) {
                this.generate_style();
                style_generated = true;
            }


            // assign to each target element
            maybe_querySelectorAll(attachTo).forEach(function(el) {
                if(el.tagName == 'INPUT' && el.getAttribute('type') != 'text') {
                    return;
                }

                // do not initialize twice
                if(el.parentNode.classList.length && el.parentNode.classList.contains('lcslt_wrap')) {
                    return;
                }

                $this.wrap_element(el);
            });
        };



        /* wrap target element to allow trigger display */
        this.wrap_element = function(el) {
            cp_uniqid = Math.random().toString(36).substr(2, 9);

            const $this     = this,
                  side_prop = (options.preview_style.side == 'right') ? 'borderRightWidth' : 'borderLeftWidth';

            let trigger_css =
                `width:${ (options.no_input_mode) ? 'calc(100% - '+ parseInt(getComputedStyle(el)['borderRightWidth'], 10) +'px - '+ parseInt(getComputedStyle(el)['borderLeftWidth'], 10) +'px);' : options.preview_style.width +'px;'}` +

                options.preview_style.side +':'+ parseInt(getComputedStyle(el)[side_prop], 10) +'px;'+

                'top:'+ parseInt(getComputedStyle(el)['borderTopWidth'], 10) +'px;' +

                'height: calc(100% - '+ parseInt(getComputedStyle(el)['borderTopWidth'], 10) +'px - '+ parseInt(getComputedStyle(el)['borderBottomWidth'], 10) +'px);';

            let trigger_upper_css =
                trigger_css +
                'background:'+ el.value +';' +
                'border-color:'+ options.preview_style.separator_color +';'

            let div = document.createElement('div');
            div.className = 'lccp-preview-'+ options.preview_style.side;
            div.setAttribute('data-for', el.getAttribute('name'));

            // static width from input?
            if(options.wrap_width != 'auto') {
                div.style.width = (options.wrap_width == 'inherit') ? Math.round(el.getBoundingClientRect().width) + 'px' : options.wrap_width;
            }

            const direct_colorpicker_code = (!options.transparency && options.modes.length == 1 && options.modes[0] == 'linear-gradient') ?
                '<input type="color" name="'+ cp_uniqid +'_direct_cp" value="'+ el.value +'" class="lccp-direct-cp-f" />' : '';

            div.classList.add("lccp-el-wrap");
            div.innerHTML =
                '<span class="lccp-preview-bg" style="'+ trigger_css +'"></span>' +
                '<span id="'+ cp_uniqid +'" class="lccp-preview" style="'+ trigger_upper_css +'" title="'+ options.labels[0] +'"></span>' +
                direct_colorpicker_code;

            el.parentNode.insertBefore(div, el);
            div.appendChild(el);

            // input padding
            if(!options.no_input_mode) {
                if(options.preview_style.side == 'right') {
                    div.querySelector('input:not([type="color"])').style.paddingRight = options.preview_style.input_padding +'px';
                } else {
                    div.querySelector('input:not([type="color"])').style.paddingLeft = options.preview_style.input_padding +'px';
                }
            }


            // direct browser colorpicker? track changes
            if(div.querySelector('.lccp-direct-cp-f')) {
                div.querySelector('.lccp-direct-cp-f').addEventListener("input", (e) => {

                    div.querySelector('input:not([type="color"])').value = e.target.value;
                    div.querySelector('.lccp-preview').style.background = e.target.value;
                });
            }


            // event to show picker
            const trigger = document.getElementById(cp_uniqid);
            trigger.addEventListener("click", (e) => {
                this.show_picker(trigger);
            });



            // show on field focus?
            if(options.open_on_focus) {
                div.querySelector(right_input_selector).addEventListener("focus", (e) => {
                    if(trigger != active_trigger) {
                        if(active_trigger) {
                            document.getElementById('lc-color-picker').classList.remove('lccp-shown');
                            active_trigger = null;
                        }

                        $this.debounce('open_on_focus', 10, 'show_picker', trigger);
                    }
                });
            }


            // sync manually-inputed data in the field
            div.querySelector(right_input_selector).addEventListener("keyup", (e) => {
                if(e.keyCode == 9 || e.key === 'Enter' || e.keyCode === 13) {
                    return;
                }

                const is_active_trigger_and_opened = (active_trig_id = cp_uniqid && document.querySelector("#lc-color-picker.lccp-shown")) ? true : false;

                active_trigger = trigger;
                active_trig_id = cp_uniqid;

                $this.debounce('manual_input_sync', 10, 'val_to_picker', true);

                if(is_active_trigger_and_opened) {
                    $this.debounce('manual_input_sync_cp', 10, 'append_color_picker', false);
                    $this.debounce('reopen_picker_after_manual_edit', 10, 'show_picker', trigger);
                }
            });


            // be sure input value is managed on focusout
            div.querySelector(right_input_selector).addEventListener("focusout", (e) => {
                // not if this field's picker is shown and focus is on "body"
                if(document.activeElement.tagName == 'BODY' && document.querySelector('#lc-color-picker.lccp-shown[data-trigger-id="'+ active_trig_id +'"]')) {
                    return true;
                }

                e.target.dispatchEvent(lccp_ivc_event(active_trig_id, true));
            });


            // custom event - check field validity and eventually use fallback values
            div.querySelector(right_input_selector).addEventListener("lccp_input_val_check", (e) => {
                const curr_val = e.target.value,
                      test = document.createElement('div');

                test.style.background = curr_val;
                let browser_val = test.style.background,
                    val_to_set;

                if(!curr_val.trim().length || !browser_val) {
                    if(e.target.value.toLowerCase().indexOf('gradient') === -1) {
                        val_to_set = (options.fallback_colors[0].toLowerCase().indexOf('rgba') === -1) ? $this.RGB_to_hex(options.fallback_colors[0]) : options.fallback_colors[0];
                    }
                    else {
                        val_to_set = options.fallback_colors[1];
                    }
                }
                else {
                    // browser already fixes minor things
                    browser_val = browser_val.replaceAll('0.', '.').replace(/rgb\([^\)]+\)/g, (rgb) => {
                        return $this.RGB_to_hex(rgb);
                    });

                    val_to_set = (browser_val.trim().toLowerCase().substr(0, 4) == 'rgb(') ? $this.RGB_to_hex(browser_val) : browser_val;
                }

                if(val_to_set != curr_val) {
                    e.target.value = val_to_set;
                }

                if(typeof(options.on_change) == 'function' && last_tracked_col != val_to_set) {
                    options.on_change.call($this, val_to_set, e.target);
                }

                if(e.detail.picker_id == active_trig_id) {
                    active_trigger = null;
                    active_trig_id = null;
                }


                // also hide picker?
                const $target = document.querySelector('#lc-color-picker.lccp-shown[data-trigger-id="'+ e.detail.picker_id +'"]');
                if($target) {

                    $target.classList.remove('lccp-shown');
                    document.getElementById("lc-color-picker").remove();
                }
            });
        };



        /* show picker */
        this.show_picker = function(trigger) {
            if(document.querySelector('#lc-color-picker.lccp-shown[data-trigger-id="'+ active_trig_id +'"]')) {
                document.getElementById("lc-color-picker").remove();
                active_trigger = null;
                active_trig_id = null

                return false;
            }

            // direct colorpicker usage? Not for Firefox is "show on focus" is enabled
            const direct_colorpicker = trigger.parentNode.querySelector('.lccp-direct-cp-f');
            if(
                direct_colorpicker &&
                (
                    !options.open_on_focus ||
                    (options.open_on_focus && !navigator.userAgent.toLowerCase().includes('firefox'))
                )
            ) {
                direct_colorpicker.value = active_solid;
                direct_colorpicker.click();
                return true;
            }


            window_width = window.innerWidth;
            active_trigger = trigger;
            active_trig_id = cp_uniqid;

            this.val_to_picker();
            this.append_color_picker();

            const picker      = document.getElementById('lc-color-picker'),
                  picker_w    = picker.offsetWidth,
                  picker_h    = picker.offsetHeight,
                  at_offsety  = active_trigger.getBoundingClientRect(),
                  at_h        = parseInt(active_trigger.clientHeight, 10) + parseInt(getComputedStyle(active_trigger)['borderTopWidth'], 10) + parseInt(getComputedStyle(active_trigger)['borderBottomWidth'], 10),
                  y_pos       = (parseInt(at_offsety.y, 10) + parseInt(window.pageYOffset, 10) + at_h + 5);

            // left pos control - also checking side overflows
            let left = (parseInt(at_offsety.right, 10) - picker_w);
            if(left < 0) {
                left = 0;
            }

            // mobile? show it centered
            if(window.innerWidth < 700) {
                left = Math.floor( (window.innerWidth - picker_w) / 2);
            }

            // top or bottom ?
            const y_pos_css = (y_pos + picker_h - document.documentElement.scrollTop < window.innerHeight) ?
                    'top:'+ y_pos :
                    'transform: translate3d(0, calc((100% + '+ (active_trigger.offsetHeight + 10) +'px) * -1), 0); top:'+ y_pos;

            picker.setAttribute('style', y_pos_css +'px; left: '+ left +'px;');
            picker.classList.add('lccp-shown');
        };



        /* handles input value and prepres data for the picker */
        this.val_to_picker = function(from_manual_input) {
            if(!active_trigger) {
                return false;
            }
            const val = active_trigger.parentNode.querySelector(right_input_selector).value.trim().toLowerCase();
            last_tracked_col = val;

            // check validity
            let test = document.createElement('div');
            test.style.background = val;

            //// set active colors
            // if no value found
            if(!val.length || !test.style.background.length) {
                active_solid = options.fallback_colors[0];
                active_gradient = options.fallback_colors[1];
                active_mode = 'linear-gradient';

                /* if(val.indexOf('linear-gradient') !== -1) {
                }
                else if(val.indexOf('radial-gradient') !== -1) {
                    active_mode = 'radial-gradient';
                }
                else {
                    active_mode = 'solid';
                } */
            }
            else {

                active_mode = 'linear-gradient';
                active_gradient = val;
                // find which value type has been passed
                /* if(val.indexOf('linear-gradient') !== -1) {
                }
                else if(val.indexOf('radial-gradient') !== -1) {
                    active_mode = 'radial-gradient';
                }
                else {
                    active_mode = 'solid';
                }

                if(active_mode == 'solid') {
                    active_solid = val;
                    active_gradient = options.fallback_colors[1];
                }
                else{
                    active_solid = options.fallback_colors[0];
                } */
            }
            active_trigger.style.background = val;

            if(!from_manual_input || (from_manual_input && options.open_on_focus)) {
                // elaborate solid color data (color and alpha)
                //this.load_solid_data(active_solid);
                // elaborate gradient data
                if(active_gradient) {
                    this.load_gradient_data(active_gradient);
                }
            }
        };



        /* elaborate solid color data (color and alpha) loading into active_solid and active_opacity */
        this.load_solid_data = function(raw_data) {
            active_opacity = 1;

            // rgba
            if(raw_data.indexOf('rgba') !== -1) {
                const data = this.RGBA_to_hexA(raw_data);
                active_solid = data[0];
                active_opacity = data[1];
            }

            // rgb
            else if(raw_data.indexOf('rgba') !== -1) {
                active_solid = this.RGB_to_hex(raw_data);
            }

            // hex
            else {
                active_solid = this.short_hex_fix(raw_data);
            }
        };



        /* elaborate gradient data loading into gradient_data */
        this.load_gradient_data = function(raw_data) {
            const $this = this;
            const is_radial = (raw_data.indexOf('radial-gradient') === -1) ? false : true;

            // solve issues with inner RGB|RGBA and turn everything into RGBA
            raw_data = raw_data
                .replace(/,\./g, ',0.').replace(/ \./g, ' 0.')
                .replace(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)/g, 'rgbaZ($1|$2|$3|$4)')
                .replace(/\|\)/g, '|1)');

            // names to deg
            raw_data = raw_data
                .replace('top right', '45deg').replace('right top', '45deg')
                .replace('bottom right', '135deg').replace('bottom right', '135deg')
                .replace('top left', '315deg').replace('left top', '315deg')
                .replace('bottom left', '225deg').replace('bottom left', '225deg')
                .replace('right', '90deg').replace('left', '270deg').replace('top', '0deg').replace('bottom', '180deg');

            // be sure deg or shape is defined
            if(is_radial && raw_data.indexOf('ellipse') === -1 && raw_data.indexOf('circle') === -1) {
                raw_data.replace('\\(', '(ellipse ');
            }
            if(!is_radial && raw_data.indexOf('deg') === -1) {
                raw_data.replace('\\(', '(180deg');
            }

            // process
            raw_data = raw_data.lccpReplaceArray(
                ['linear-gradient', 'radial-gradient', '', '\\(', 'to', '\\)'],
                ''
            );

            // split steps
            const raw_steps = raw_data.split(',');
            const fallback_multiplier = 100 / raw_steps.length;

            gradient_data.steps = [];
            raw_steps.some(function(raw_step, index) {

                // direction on first index
                if(!index) {
                    if(is_radial) {
                        gradient_data.radial_circle = (raw_step.indexOf('circle') === -1) ? false : true;
                    } else {
                        gradient_data.deg = parseInt(raw_step.replace('deg', ''), 10);
                    }
                }

                // {color : null, opacity: null, position : null}
                else {
                    raw_step = raw_step.trim().split(' ');
                    let position = '';

                    // position
                    if(raw_step.length < 2) {
                        if(index === 1) {
                            position = '0%';
                        }
                        else if(index == (raw_steps.length - 1)) {
                            position = '100%';
                        }
                        else {
                            position = (fallback_multiplier * index) +'%';
                        }
                    }
                    else {
                        position = raw_step[1];
                    }

                    // color
                    let raw_color   = raw_step[0],
                        opacity     = 1;

                    // normalize to hex
                    if(raw_color.indexOf('rgbaZ') !== -1) {
                        const col_arr = $this.RGBA_to_hexA(
                            raw_color.replace('rgbaZ', 'rgba').replace(/\|/g, ',')
                        );

                        raw_color = col_arr[0];
                        opacity = col_arr[1];
                    }

                    gradient_data.steps.push({
                        color : $this.short_hex_fix(raw_color),
                        opacity: opacity,
                        position : parseInt(position, 10)
                    });
                }
            });
        };



        /* handles RGBA string returning a two elements array: hex and alpha  */
        this.RGBA_to_hexA = function(raw_data) {
            raw_data = raw_data.lccpReplaceArray(['rgba', '\\(', '\\)'], '');
            const rgba_arr = raw_data.split(',')

            let alpha = (typeof(rgba_arr[3]) != 'undefined') ? rgba_arr[3] : '1';
            if(alpha.substring(0, 1) == '.') {
                alpha = 0 + alpha;
            }
            rgba_arr.splice(3, 1);

            return [
                this.RGB_to_hex('rgb('+ rgba_arr.join(',') +')'),
                parseFloat(alpha)
            ];
        };



        /* convert RGB to hex */
        this.RGB_to_hex = function(rgb) {
            rgb = rgb.lccpReplaceArray(['rgb', '\\(', '\\)'], '');
            const rgb_arr = rgb.split(',');

            if(rgb_arr.length < 3) {
                return '';
            }

            let r = parseInt(rgb_arr[0].trim(), 10).toString(16),
                g = parseInt(rgb_arr[1].trim(), 10).toString(16),
                b = parseInt(rgb_arr[2].trim(), 10).toString(16);

            if (r.length == 1) {r = "0" + r;}
            if (g.length == 1) {g = "0" + g;}
            if (b.length == 1) {b = "0" + b;}

            return this.shorten_hex(r + g + b);
        };



        /* if possible, shortenize hex string */
        this.shorten_hex = function(hex) {
            hex = hex.replace('#', '').split('');

            if(hex.length >= 6) {
                if(
                    hex[0] === hex[1] &&
                    hex[2] === hex[3] &&
                    hex[4] === hex[5]
                ) {
                    return '#'+ hex[0] + hex[2] + hex[4];
                }
            }

            return '#'+ hex.join('');
        };



        /* convert short hex to full format */
        this.short_hex_fix = function(hex) {
            if(hex.length == 4) {
                const a = hex.split('');
                hex = a[0] + a[1] + a[1] + a[2] + a[2] + a[3] + a[3];
            }

            return hex.toLowerCase();
        };



        /* convert hex to RGB */
        this.hex_to_RGB = function(h) {
            let r = 0, g = 0, b = 0;

            // 3 digits
            if (h.length == 4) {
                r = "0x" + h[1] + h[1];
                g = "0x" + h[2] + h[2];
                b = "0x" + h[3] + h[3];

                // 6 digits
            } else if (h.length == 7) {
                r = "0x" + h[1] + h[2];
                g = "0x" + h[3] + h[4];
                b = "0x" + h[5] + h[6];
            }

            return "rgb("+ +r + ", " + +g + ", " + +b + ")";
        };



        /* convert hex to RGB */
        this.hex_to_RGBA = function(h, opacity) {
            if(parseFloat(opacity) === 1) {
                return this.shorten_hex(h);
            }

            let rgb = this.hex_to_RGB(h);
            return rgb.replace('(', 'a(').replace(')', ', '+ opacity.toString().replace('0.', '.') +')');
        };




        /* append color container picker to the body */
        this.append_color_picker = function(on_manual_input_change = false) {
            const $this = this;

           /*  if(document.getElementById("lc-color-picker") && !on_manual_input_change) {
                document.getElementById("lc-color-picker").remove();
            } */

            const theme_class     = (options.dark_theme) ? 'lccp_dark_theme' : 'lccp_light_theme',
                  bg              = (active_mode == 'solid') ? active_solid : active_gradient,
                  shown_solid     = (active_mode == 'solid') ? active_solid : gradient_data.steps[0].color,
                  shown_opacity   = (active_mode == 'solid') ? active_opacity : (options.transparency) ? gradient_data.steps[0].opacity : null,
                  print_grad_code = (options.modes.indexOf('linear-gradient') !== -1 || options.modes.indexOf('radial-gradient') !== -1) ? true : false;


            // start code
            let picker = '',
                picker_el;

            if(on_manual_input_change && document.getElementById("lc-color-picker")) {
                picker_el = document.getElementById("lc-color-picker");
                picker_el.setAttribute('data-mode', active_mode);
                picker_el.setAttribute('data-trigger-id', cp_uniqid);
            }
            else {
                picker = '<div id="lc-color-picker" class="'+ theme_class +'" data-mode="'+ active_mode +'" data-trigger-id="'+ cp_uniqid +'">';
            }


            // modes select
            /* if(options.modes.length >= 1) {
                picker += `
                <div id="lccp_modes_wrap">
                    <span class="${(active_mode == 'solid') ? 'lccp_sel_mode' : ''}" ${(options.modes.indexOf('solid') === -1) ? 'style="display: none;"' : ''} data-mode="solid">${ options.labels[1] }</span>
                    <span class="${(active_mode == 'linear-gradient') ? 'lccp_sel_mode' : ''}" ${(options.modes.indexOf('linear-gradient') === -1) ? 'style="display: none;"' : ''} data-mode="linear-gradient">${ options.labels[2] }</span>
                    <span class="${(active_mode == 'radial-gradient') ? 'lccp_sel_mode' : ''}" ${(options.modes.indexOf('radial-gradient') === -1) ? 'style="display: none;"' : ''} data-mode="radial-gradient">${ options.labels[3] }</span>
                </div>`;
            } */


            // gradient wizard
            if(print_grad_code) {
                picker += `
                <div class="lccp_gradient_wizard" ${ (active_mode == 'solid') ? 'style="display: none;"' : '' }>
                    <div class="lccp_gradient lccp_gradient-bg"></div>
                    <div class="lccp_gradient" style="background: ${ active_gradient }" title="${ options.labels[4] }"></div>

                    <div class="lccp_gradient_ranges"></div>

                    <div class="pccp_deg_f_wrap" ${ (active_mode == 'radial-gradient') ? 'style="display: none;"' : '' }>
                        <img src="   data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABkAAAAZCAYAAADE6YVjAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAZJJREFUeNqsVetthDAMzp06QEagEv/LBk03oBswAhuQDVAn6AgZgTIBbAAb5DZIE+ScUs42OVRLVvBd7M+v2BeRRxVwEQSttei6LlNV/HB/ymDP8+LZYVxVlev73llrXUrLsqSipgBaz5YyvmcppfPRbRYDYJCNMSzId67xPSulXNu223c4KZDTAFgqMRDNKAXwGuokoAEazwMHBLW6gxTExQm6iqOGAoG66Ctc7BHl2fMHnBT1ECVK4zgKLgqbpIYjm1EX/eIFhSh/eb5lgLyC/jucf1I7z7NY1/Ue8t4LybwfDU1APeAabIZ6urIsg85DhyxM/tN7de5cuSK/rcz84uSnQKhU3Q7kNKUqDtNIJrMmEu4ORMvH6NDUtwhII85TBfXSYBtF54p/tBoGMI6mfCDm1bM0JU4+vD/FDMYc6pMUx11ksIuGAFrAAJYCtctEk5PT6WBPTOCMTKKPDljg4j+AHABEw0PSQBMxC8n3cASSbtLibL8rZvOpxHtztBYuGWAFPK63xOPPzFWw0a8AAwA+dEfwP/CgZgAAAABJRU5ErkJggg==" alt="angle" title="${ options.labels[5] }" />

                        <input type="range" name="deg" value="${ gradient_data.deg }" min="0" max="360" step="1" />
                        <input type="number" name="deg-num" value="${ gradient_data.deg }" min="0" max="360" step="1" />
                    </div>
                    <div class="pccp_circle_f_wrap" ${ (active_mode == 'radial-gradient') ? '' : 'style="display: none;"' }>
                        <img src="    data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAhQAAAIUB4uz/wQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAEZSURBVEiJzdQxSgRBFATQ52hionsKxcjQTERYUzMv4F28hImsiwcQExFEBfUCggtGXkBccRNR1qBnQHRme9p2wYJigu5f9Wd+zaceXexjgFeM8IADbGGmoS6KJVxiHOENVlLF1/HUQrziEBspnaeIV3zGchuD81+IV7wWmUk3Q7ziZpP4HHYib/eBU9yVYnWYnyQwmNDZI1YjDUTx0iD+Xop30BdSk5Kww7LWqOHSSdlAP0H4O3uEP7TucK80SOn8R4wLIWZ1eCufC03ftgUWCxxlCERR4Ay30zQYY1dI01QM4B7bwkCnYgAXWMPVXxo0LalNzArzaVoPWQZfkWVQxK/k4V8Y5MR32MbgOMOgVW1H2Iqp67qHzif097OAt54XXQAAAABJRU5ErkJggg==" alt="shape" title="${ options.labels[6] }" />

                        <span class="pcpp_ellipse_shape ${ (gradient_data.radial_circle) ? '' :  'pcpp_circle_btn_active' }" data-val="ellipse">Ellipse</span>
                        <span class="pcpp_circle_shape ${ (gradient_data.radial_circle) ? 'pcpp_circle_btn_active' : '' }" data-val="circle">Circle</span>
                    </div>
                    <hr/>
                </div>`;
            }


            // HTML5 colorpicker
            picker += `
            <div class="pccp_color_f_wrap" ${ (!print_grad_code) ? 'style="margin-top: 0;"' : '' }>
                <img src="  data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAA2wAAANsB8FDmnAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAFzSURBVEiJrdU/SxxRFIbx3wgWCjbRSkEICCHBzhCwUCurQDr3I6QLaJEqTcDWJoVNPoAWqW2sXAJW22hhkX+F1VYKBlwQ15ti7+Cy2Z29M+OFt5i5nOdhztw5I4QgNXiFH/iYXFMCPoFThJhPTy340AcPeEDjSQRYxN8BQcAt3tQSYBInQ+B52pitI/haAM/zpZIAOwnwgDsslRLgLbqJgoBvyQIs46YEPM/qWAGm8asCPOB7imCvIjzPy5ECrOC+pmC3SJByJMflvEhwUQO8j0t0kP0nwDO9+VIFfh8Px0m8nh8mmKrR/zO9aXsTGVM5d0JcIYRObFGV1cQLzOAisojW/tWqAG/jM94PZQy85IZyrenqjZR1j2OlMfIURclRIvwP1jCH3/HeUcqXvIDrEdCfOMC2Xr/fxRaFWLOQOuw2+wrzHMe959iKonyvjc2y43oWhwOSqyFPdajgj5ZF2MiVZdkGNvA6Jj8pLTRDCM2i+n/jlZhJOn4yuAAAAABJRU5ErkJggg==" alt="color" title="${ options.labels[7] }" />

                <div>
                    <input type="color" name="color" value="${ shown_solid }" style="opacity: ${ active_opacity };" />
                </div>
                <input type="text" name="hex" value="${ shown_solid.toLowerCase() }" />
            </div>`;

            // opacity cursor
            if(options.transparency) {
                picker += `
                <div class="pccp_opacity_f_wrap">
                    <img src=" data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAApgAAAKYB3X3/OAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAF9SURBVEiJtda/ihRBEMfxT42D4Jmdq5GHIBqJIHgYGCuIIhcYaGRgYGDsU4i+gqFgJogXivgGwkUmKpqcnhgJJ/4pg5mV3XVmd3q5K+iku+v37e7qrurITH0WEUdwC5dwCiexi21sZub9XufW6h7ho3iEmzg4M/wOm3i/SBxk5lTDDXxB9rRnHT7H8QYX/hubmXgHf+aI9wEO4CW+Yb0T0B7HIvFOQOu/glct5PwUAMfwdYB4L6DVOYzX+DTuGwf5IVYHBW2OZeb3iLiC9cn+EX4OXP3cHXS1qj37zuu6F1bjaqFP/8ucsIi4jtM1ThQCYuC8azhXYa0QUGTVfoqPAR/3E1DjA84U+AwKMl7gba3JjCU3aVCQM/M5zRE9xa8CQJFVmbmDJ3slGBGHIuLiv442SY3MrwFLJ7uqhezgriZdL7vyFU1gz2JjagcTK7iN36U7MKTgzJTMz4WANWxZVDInHEZ4jB8dgC08wL0h6ToWfFtWNd+Wy5b8tvwFZS60TLZpD/8AAAAASUVORK5CYII=" alt="opacity" title="${ options.labels[8] }" />

                    <input type="range" name="opacity" value="${ shown_opacity }" min="0" max="1" step="0.01" />
                    <input type="number" name="opacity-num" value="${ shown_opacity }" min="0" max="1" step="0.05" />
                </div>`;
            }


            // append or re-fill
            (on_manual_input_change && document.getElementById("lc-color-picker")) ? picker_el.innerHTML = picker : document.body.insertAdjacentHTML('beforeend', picker +'</div>');


            // modes change
            if(options.modes.length >= 1) {
                for (const mode of document.querySelectorAll('#lccp_modes_wrap span')) {
                    mode.addEventListener("click", (e) => { $this.mode_change( e.target, e.target.getAttribute('data-mode')) });
                }
            }

            // print steps and add gradient step action
            if(print_grad_code) {
                gradient_data.steps.some(function(step, index) {
                    $this.add_draggable_element(index, step.position, step.color);
                });

                document.querySelector('.lccp_gradient:not(.lccp_gradient-bg)').addEventListener("click", (e) => {this.add_gradient_step(e) });
            }

            // angle actions
            if(options.modes.indexOf('linear-gradient') !== -1) {
                document.querySelector('.pccp_deg_f_wrap input[type=range]').addEventListener("input", (e) => {this.track_deg_range_change(e)});
                document.querySelector('.pccp_deg_f_wrap input[name=deg-num]').addEventListener("change", (e) => {this.track_deg_num_change(e)});
                document.querySelector('.pccp_deg_f_wrap input[name=deg-num]').addEventListener("keyup", (e) => {
                    this.debounce('deg_f_change', 500, 'track_deg_num_change', e);
                });
            }

            // circle actions
            if(options.modes.indexOf('radial-gradient') !== -1) {
                for (const mode of document.querySelectorAll('.pccp_circle_f_wrap span')) {
                    mode.addEventListener("click", (e) => { $this.set_ellipse_circle( e.target, e.target.getAttribute('data-val')) });
                }
            }

            // color actions
            document.querySelector('.pccp_color_f_wrap input[type="color"]').addEventListener("input", (e) => {this.track_color_change(e)});
            document.querySelector('.pccp_color_f_wrap input[type="color"]').addEventListener("change", (e) => {this.track_color_change(e)});
            document.querySelector('.pccp_color_f_wrap input[name=hex]').addEventListener("keyup", (e) => {
                this.debounce('hex_f_change', 600, 'track_color_hex_change', e);
            });

            // transparency actions
            if(options.transparency) {
                document.querySelector('.pccp_opacity_f_wrap input[type=range]').addEventListener("input", (e) => {this.track_opacity_range_change(e)});
                document.querySelector('.pccp_opacity_f_wrap input[name=opacity-num]').addEventListener("change", (e) => {this.track_opacity_num_change(e)});
                document.querySelector('.pccp_opacity_f_wrap input[name=opacity-num]').addEventListener("keyup", (e) => {
                    this.debounce('opacity_f_change', 500, 'track_opacity_num_change', e);
                });
            }
        };



        /*** add draggable element ***/
        this.add_draggable_element = function(rel_step_num, position, color) {
            const   $this       = this,
                    container   = document.querySelector('.lccp_gradient_ranges'),
                    sel_class   = (!rel_step_num) ? 'lccp_sel_step' : '',
                    del_btn_vis = (gradient_data.steps.length > 2) ? '' : 'style="display: none;"'

            container.innerHTML +=
            '<span class="lccp_gradient_range '+ sel_class +'" data-step-num="'+ rel_step_num +'" style="background: '+ color +'; left: '+ position +'%;">'+
                '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJFJREFUeNpiYMAOFIC4H4jPA/F/NHweKqfAQAQQgCr+TyTuJ2TYeRIMQ3axADYD8RlWAMVEG9pAwBUwQJT3FYjwFjEGgrACE5BIYKAeyGcgMiKIdeF9RjQNuAAjkoEEAXpskgpQYp+JgcqA6l4GufACFR34AGTgRioauIHqCZsmWY8mhQNNii+qF7BUqQIAAgwANgu7Y7cw5VAAAAAASUVORK5CYII=" '+ del_btn_vis +' />'+
            '</span>';

            let active = false;

            //////
            const dragStart = function(range_id, el, e) {
                active = range_id;
            };

            const dragEnd = function() {
                active = false;
                $this.apply_changes();
            };

            const drag = function(range_id, range, e) {
                if (active !== false && range_id == active) {
                    e.preventDefault();
                    const rect = container.getBoundingClientRect();

                    let new_pos = (e.type === "touchmove") ? (e.touches[0].clientX - rect.left) : (e.clientX - rect.left);
                    new_pos = Math.round((100 * new_pos) / container.offsetWidth);

                    if(new_pos < 0) {new_pos = 0;}
                    else if(new_pos > 100) {new_pos = 100;}

                    // limit positions basing on previous and next step
                    const min_pos = (!range_id) ? 0 : gradient_data.steps[ range_id-1 ].position;
                    const max_pos = (range_id == (gradient_data.steps.length - 1)) ? 100 : gradient_data.steps[ range_id+1 ].position;

                    if(new_pos < min_pos) {new_pos = min_pos + 1;}
                    else if(new_pos > max_pos) {new_pos = max_pos - 1;}

                    gradient_data.steps[ range_id ].position = new_pos;
                    range.style.left = new_pos +'%';

                    $this.apply_gradient_changes();
                }
            };+
            /////

            document.querySelectorAll('.lccp_gradient_range').forEach(range => {
                const step_num = parseInt(range.getAttribute('data-step-num'), 10);

                range.removeEventListener("touchstart", null);
                range.removeEventListener("touchend", null);
                range.removeEventListener("touchmove", null);
                range.removeEventListener("click", null);

                range.removeEventListener("mousedown", null);
                range.removeEventListener("mouseup", null);

                range.addEventListener("touchstart", (e) => {dragStart(step_num, e.target, e)});
                range.addEventListener("mousedown", (e) => {dragStart(step_num, e.target, e)});

                range.addEventListener("click", (e) => {$this.select_gradient_color(step_num)});

                container.addEventListener("touchmove", (e) => {drag(step_num, range, e)});
                container.addEventListener("mousemove", (e) => {drag(step_num, range, e)});

                range.addEventListener("mouseup", (e) => {dragEnd()});
                range.addEventListener("touchend", (e) => {dragEnd()});
                document.addEventListener("mouseup", (e) => {dragEnd()});
            });


            // remove step handler
            document.querySelectorAll('.lccp_gradient_range img').forEach((btn) => {

                btn.addEventListener("click", (e) => {
                    if(document.querySelectorAll('.lccp_gradient_range').length < 3) {
                        return false;
                    }

                    // wait a bit to not interfere with global handler for picker closing
                    setTimeout(() => {
                        const parent = e.target.parentNode,
                              step_num = parseInt(parent.getAttribute('data-step-num'), 10),
                              to_select = (!step_num) ? 0 : step_num - 1;

                        gradient_data.steps.splice(step_num, 1);

                        // clean and restart
                        document.querySelectorAll('.lccp_gradient_range').forEach(r => r.remove());

                        gradient_data.steps.some(function(step, index) {
                            $this.add_draggable_element(index, step.position, step.color);
                        });

                        // select newly added element
                        document.querySelector('.lccp_gradient_range[data-step-num="'+ to_select +'"]').click();

                        this.apply_gradient_changes(true);
                    }, 20);
                });
            });
        };



        /* select gradient color  */
        this.select_gradient_color = function(step_num) {
            sel_grad_step = step_num;

            document.querySelectorAll('.lccp_gradient_range').forEach(m => m.classList.remove('lccp_sel_step'));
            document.querySelector('.lccp_gradient_range[data-step-num="'+ step_num +'"]').classList.add('lccp_sel_step');

            active_solid = gradient_data.steps[ step_num ].color;
            active_opacity = gradient_data.steps[ step_num ].opacity;

            document.querySelector('#lc-color-picker input[type="color"]').value = active_solid;
            document.querySelector('.pccp_color_f_wrap input[name=hex]').value = active_solid;

            if(options.transparency) {
                document.querySelector('.pccp_opacity_f_wrap input[type=range]').value = active_opacity;
                document.querySelector('.pccp_opacity_f_wrap input[name=opacity-num]').value = active_opacity;
            }
        };



        /* apply changes to gradient, after a color/opacity/degree update */
        this.apply_gradient_changes = function(also_apply_changes) {
            const $this = this;

            let new_gradient = active_mode+'(';
            new_gradient += (active_mode == 'linear-gradient') ? gradient_data.deg+'deg' : (gradient_data.radial_circle) ? 'circle' : 'ellipse';
            new_gradient += ', ';

            let colors_part = []
            gradient_data.steps.some(function(step, index) {

                let to_add = (options.transparency) ? $this.hex_to_RGBA(step.color, step.opacity) : $this.shorten_hex(step.color);

                if(
                    gradient_data.steps.length > 2 ||
                    (
                        gradient_data.steps.length <= 2 &&
                        (
                            (!index && parseInt(step.position, 10)) ||
                            (index && index < (gradient_data.steps.length - 1)) ||
                            (index == (gradient_data.steps.length - 1) && parseInt(step.position, 10) != 100)
                        )
                    )
                ) {
                        to_add += ' '+ step.position +'%';
                    }

                colors_part.push( to_add );
            });

            active_gradient = new_gradient + colors_part.join(', ') + ')';

            if(document.querySelector('.lccp_gradient:not(.lccp_gradient-bg)')) {
                document.querySelector('.lccp_gradient:not(.lccp_gradient-bg)').style.background = active_gradient;
            }

            if(also_apply_changes) {
                this.apply_changes();
            }
        };



        /* apply changes to target field */
        this.apply_changes = function() {
            if(!active_trigger) {
                return false;
            }
            let val = '';

            // apply everything to picker global vars
            if(active_mode == 'solid') {
                val = this.shorten_hex(active_solid);

                if(options.transparency && document.querySelector('.pccp_opacity_f_wrap input[type=range]')) {
                    active_opacity = document.querySelector('.pccp_opacity_f_wrap input[type=range]').value;
                    val = this.hex_to_RGBA(val, active_opacity);
                }
            }
            else {
                val = active_gradient;
            }

            // apply
            active_trigger.style.background = val;

            const field = active_trigger.parentNode.querySelector(right_input_selector),
                  old_val = field.value;

            if(old_val != val) {
                field.value = val;
                last_tracked_col = val;

                if(typeof(options.on_change) == 'function') {

                    if(typeof(debounced_vars['on_change_cb']) != undefined && debounced_vars['on_change_cb']) {
                        clearTimeout(debounced_vars['on_change_cb']);
                    }
                    debounced_vars['on_change_cb'] = setTimeout(() => {
                        options.on_change.call(this, val, field);
                    }, 300);
                }
            }
        };






        // HANDLERS

        // fields toggle basing on modes change
        this.mode_change = function(el, new_mode) {
            if(active_mode == new_mode) {
                return false;
            }
            let color, opacity;

            // from gradient to solid
            if(new_mode == 'solid') {
                color = active_solid;
                if(options.transparency) {
                    opacity = active_opacity;
                }
            }
            else {
                color = gradient_data.steps[0].color;
                if(options.transparency) {
                    opacity = gradient_data.steps[0].opacity;
                }
            }

            document.querySelector('#lc-color-picker input[type="color"]').value = color;
            document.querySelector('.pccp_color_f_wrap input[name=hex]').value = color;

            if(options.transparency) {
                document.querySelector('.pccp_opacity_f_wrap input[type=range]').value = opacity;
                document.querySelector('.pccp_opacity_f_wrap input[name=opacity-num]').value = opacity;
            }

            // toggle grad fields
            if(options.modes.length >= 1) {
                document.querySelector('.pccp_deg_f_wrap').style.display = (new_mode == 'linear-gradient') ? 'flex' : 'none';
                document.querySelector('.pccp_circle_f_wrap').style.display = (new_mode == 'radial-gradient') ? 'block' : 'none';
            }

            // toogle gradient wizard
            if(options.modes.indexOf('linear-gradient') !== -1 || options.modes.indexOf('radial-gradient') !== -1) {
                document.querySelector('.lccp_gradient_wizard').style.display = (new_mode != 'solid') ? 'block' : 'none';
            }

            document.querySelectorAll('#lccp_modes_wrap span').forEach(m => m.classList.remove('lccp_sel_mode'));
            el.classList.add('lccp_sel_mode');

            active_mode = new_mode;
            (new_mode == 'solid') ? this.apply_changes() : this.apply_gradient_changes(true);
        };


        // add gradient step
        this.add_gradient_step = function(e) {
            const   $this = this,
                    pos = Math.round((100 * e.layerX) / e.target.offsetWidth);

            // inject in actual steps
            let index = 0;
            for(let step of gradient_data.steps) {

                if(step.position > pos) {
                    const step_data = {
                        color       : (index - 1 < 0) ? step.color : gradient_data.steps[(index - 1)].color,
                        opacity     : 1,
                        position    : pos
                    }

                    gradient_data.steps.splice(index, 0, step_data);
                    break;
                }

                index++;
            }
            document.querySelectorAll('.lccp_gradient_range').forEach(r => r.remove());

            gradient_data.steps.some(function(step, index) {
                $this.add_draggable_element(index, step.position, step.color);
            });

            // select newly added element
            document.querySelector('.lccp_gradient_range[data-step-num="'+ index +'"]').click();

            this.apply_gradient_changes(true);
        };


        // apply ellipse or circle
        this.set_ellipse_circle = function(el, new_opt) {
            if(gradient_data.radial_circle && new_opt == 'circle' || !gradient_data.radial_circle && new_opt != 'circle') {
                return false;
            }
            gradient_data.radial_circle = !gradient_data.radial_circle;

            document.querySelectorAll('.pccp_circle_f_wrap span').forEach(m => m.classList.remove('pcpp_circle_btn_active'));
            el.classList.add('pcpp_circle_btn_active');

            this.apply_gradient_changes(true);
        };


        // track opacity range fields change
        this.track_deg_range_change = function(e) {
            document.querySelector('.pccp_deg_f_wrap input[name=deg-num]').value = e.target.value;

            gradient_data.deg = e.target.value;
            this.apply_gradient_changes(true);
        };
        this.track_deg_num_change = function(e) {
            let val = parseFloat(e.target.value);
            if(isNaN(val) || val < 0 || val > 360) {
                val = 90;
            }

            e.target.value = val;
            if(document.querySelector('.pccp_deg_f_wrap input[type=range]')) {
                document.querySelector('.pccp_deg_f_wrap input[type=range]').value = val;
            }

            gradient_data.deg = val;
            this.apply_gradient_changes(true);
        };


        // track opacity range fields change
        this.track_color_change = function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelector('.pccp_color_f_wrap input[name=hex]').value = val;

            this.apply_color_change(val);
        };
        this.track_color_hex_change = function(e) {
            let val = this.short_hex_fix(e.target.value);

            if(val.match(/^#[a-f0-9]{6}$/i) === null) {
                val = active_solid.toLowerCase();
            }

            e.target.value = val;
            document.querySelector('#lc-color-picker input[type="color"]').value = val;

            this.apply_color_change(val);
        };
        this.apply_color_change = function(val) {
            if(active_mode == 'solid') {
                active_solid = val;
                this.apply_changes();
            }
            else {
                gradient_data.steps[ sel_grad_step ].color = val;

                document.querySelector('.lccp_sel_step').style.background = val;
                this.apply_gradient_changes(true);
            }
        };


        // track opacity range fields change
        this.track_opacity_range_change = function(e) {
            document.querySelector('.pccp_opacity_f_wrap input[name=opacity-num]').value = e.target.value;
            this.alter_hex_opacity(e.target.value);
        };
        this.track_opacity_num_change = function(e) {
            let val = parseFloat(e.target.value);
            if(isNaN(val) || val < 0 || val > 1) {
                val = 0.5;
            }

            e.target.value = val;

            if(document.querySelector('.pccp_opacity_f_wrap input[type=range]')) {
                document.querySelector('.pccp_opacity_f_wrap input[type=range]').value = val;
                this.alter_hex_opacity(val);
            }
        };
        this.alter_hex_opacity = function(opacity) {
            document.querySelector('#lc-color-picker input[type="color"]').style.opacity = opacity;

            if(active_mode == 'solid') {
                active_opacity = opacity;
                this.apply_changes();
            }
            else {
                gradient_data.steps[ sel_grad_step ].opacity = opacity;
                this.apply_gradient_changes(true);
            }
        };





        /*
         * UTILITY FUNCTION - debounce action to run once after X time
         *
         * @param (string) action_name
         * @param (int) timing - milliseconds to debounce
         * @param (string) - class method name to call after debouncing
         * @param (mixed) - extra parameters to pass to callback function
         */
        this.debounce = function(action_name, timing, cb_function, cb_params) {
            if( typeof(debounced_vars[ action_name ]) != 'undefined' && debounced_vars[ action_name ]) {
                clearTimeout(debounced_vars[ action_name ]);
            }
            const $this = this;

            debounced_vars[ action_name ] = setTimeout(() => {
                $this[cb_function].call($this, cb_params);
            }, timing);
        };





        /* CSS - creates inline CSS into the page */
        this.generate_style = function() {
            const transp_bg_img = "url('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAQDAwMDAwQDAwQGBAMEBgcFBAQFBwgGBgcGBggKCAkJCQkICgoMDAwMDAoMDA0NDAwRERERERQUFBQUFBQUFBT/2wBDAQQFBQgHCA8KCg8UDg4OFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAAoACgDAREAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAAAAUGCP/EACIQAQAABQMFAQAAAAAAAAAAAAAFFUNjgqLB4RESEzVRkf/EABQBAQAAAAAAAAAAAAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwDv4AE6K0stgTgAaIAE6K0stgTgAUZra1cATW1q4A9na8WXXu/PgEqu6eQJVd08gnAAowqrjuCiADOgAowqrjuCiAD/2Q==')";

            document.head.insertAdjacentHTML('beforeend',
`<style>
.lccp-el-wrap {
    position: relative;
    display: inline-block;
}
.lccp-el-wrap > input {
    margin: 0;
    min-width: 100%;
    max-width: 100%;
    width: auto;
}
.lccp-preview,
.lccp-preview-bg {
    display: inline-block;
    position: absolute;
    cursor: pointer;
    z-index: 15;
}
.lccp-preview-bg {
    z-index: 10;
}
.lccp-preview-right .lccp-preview {
    border-left: 1px solid #ccc;
}
.lccp-preview-left .lccp-preview {
    border-right: 1px solid #ccc;
}
.lccp-direct-cp-f {
    padding: 0 !important;
    margin: 0 !important;
    width: 0 !important;
    height: 0 !important;
    position: absolute;
    bottom: 0;
    visibility: hidden;
}
.lccp-preview-right .lccp-direct-cp-f {
    right: 0;
}
.lccp-preview-left .lccp-direct-cp-f{
    left: 0;
}
#lc-color-picker,
#lc-color-picker * {
    box-sizing: border-box;
    font-family: sans-serif;
}
#lc-color-picker {
    visibility: hidden;
    z-index: -100;
    opacity: 0;
    position: absolute;
    top: -9999px;
    z-index: 9999999999;
    width: 280px;
    background: #fff;
    box-shadow: 0px 2px 13px -2px rgba(0, 0, 0, .18);
    border-radius: 4px;
    overflow: hidden;
    padding: 10px;
    border: 1px solid #ccc;
    transition: opacity .15s ease;
}
#lc-color-picker.lccp-shown {
    visibility: visible;
    opacity: 1;

}

#lccp_modes_wrap {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
    margin-bottom: 10px;
}
#lccp_modes_wrap span,
.pccp_circle_f_wrap span {
	display: inline-block;
	border: 1px solid #e8e8e8;
	background: #e8e8e8;
    opacity: .78;
	padding: 4px 7px;
	font-size: 11.5px;
	border-radius: 3px;
	line-height: normal;
    cursor: pointer;
    user-select: none;
    transition: all .2s ease;
}
#lccp_modes_wrap span.lccp_sel_mode,
.pccp_circle_f_wrap span.pcpp_circle_btn_active {
	border: 1px solid #bbb;
	background: #fff;
    opacity: 1;
	cursor: default;
}
.lccp_gradient_wizard,
.lccp_gradient_ranges {
    position: relative;
}
.lccp_gradient {
    height: 35px;
    border: 1px solid #aaa;
    cursor: crosshair;
    position: relative;
    z-index: 10;
    user-select: none;
}
.lccp_gradient-bg {
	position: absolute;
	top: 0;
	z-index: 0;
	width: 100%;
	margin: 0;
}
.lccp_gradient_ranges {
    margin: 2px 7px 25px 8px;
    height: 20px;
}
.lccp_gradient_range {
	display: inline-block;
	width: 13px;
	height: 13px;
	border: 1px solid #ccc;
	border-radius: 0 50% 50% 50%;
	transform: rotate(45deg) translate3d(-5px, 5px, 0);
	cursor: col-resize;
	position: absolute;
    top: 3px;
}
.lccp_gradient_range img {
	width: 13px;
	position: relative;
	top: 11px;
	left: 11px;
	opacity: .3;
	cursor: pointer;
    transition: all .2s ease;
}
.lccp_gradient_range img:hover {
    opacity: .5;
}
.lccp_sel_step {
    border: 1px solid #333;
    box-shadow: 0 0 2px 1px teal;
}
.pccp_deg_f_wrap,
.pccp_circle_f_wrap {
    margin-bottom: 10px;
}
.pccp_circle_f_wrap * {
    float: left;
}
.pccp_circle_f_wrap:after {
    content: "";
    clear: both;
    display: block;
}
.pccp_circle_f_wrap img {
    position: relative;
    top: 4px;
}
.pccp_circle_f_wrap span {
    margin-left: 13px;
}
.pccp_circle_f_wrap span:not(.pcpp_circle_btn_active) {
    cursr: pointer;
}
.pccp_deg_f_wrap,
.pccp_color_f_wrap,
.pccp_opacity_f_wrap {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    clear: both;
}
.pccp_deg_f_wrap input,
.pccp_color_f_wrap input,
.pccp_opacity_f_wrap input {
    border: 1px solid #aaa;
    border-radius: 3px;
    padding: 0;
}
.lccp-preview-bg,
.pccp_color_f_wrap div,
.lccp_gradient-bg {
    background: ${ transp_bg_img } repeat;
    background-size: 15px;
}
#lc-color-picker hr {
	margin: 14px 0 0;
	height: 0;
	border-width: 1px 0;
	border-style: dashed;
	border-color: #e3e3e3;
}
.pccp_color_f_wrap div {
    width: calc(100% - 25px - 85px);
    height: 25px;
    border: 1px solid #aaa;
    border-radius: 2px;
    overflow: hidden;
}
.pccp_deg_f_wrap img,
.pccp_circle_f_wrap img,
.pccp_color_f_wrap img,
.pccp_opacity_f_wrap img {
	max-width: 15px;
	opacity: .6;
    cursor: help;
    user-select: none;
}
.pccp_color_f_wrap input[type=color] {
    -webkit-appearance: none;
    padding: 0;
    width: 110%;
    height: 110%;
    transform: translate(-5%, -5%);
    cursor: pointer;
    border: none;
}
.pccp_color_f_wrap input:focus {
    outline: none;
}
.pccp_color_f_wrap input::-webkit-color-swatch-wrapper {
    padding: 0;
}
.pccp_color_f_wrap input::-webkit-color-swatch {
    border: none;
}
.pccp_color_f_wrap input[name=hex] {
    width: 70px;
    height: 25px;
    text-align: center;
}
.pccp_color_f_wrap {
    margin-top: 17px;
}
.pccp_opacity_f_wrap {
    margin-top: 10px;
}
.pccp_deg_f_wrap input[type=range],
.pccp_opacity_f_wrap input[type=range] {
    width: calc(100% - 25px - 70px);
    height: 25px;
}
.pccp_deg_f_wrap input[type="number"],
.pccp_opacity_f_wrap input[type="number"] {
	width: 53px;
	height: 25px;
	text-align: center;
}
.pccp_deg_f_wrap input[type=range],
.pccp_opacity_f_wrap input[type=range] {
    -webkit-appearance: none;
    height: 5px;
    background: #d5d5d5;
    outline: none;
    border: none;
}
.pccp_deg_f_wrap input::-webkit-slider-thumb,
.pccp_opacity_f_wrap input::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 17px;
    height: 17px;
    background: #888;
    cursor: pointer;
    border-radius: 50%;
    border: 1px solid #aaa;
    box-shadow: 0 0 0 5px #fff inset, 0 0 2px rgba(0,0,0,.15);
}
.pccp_deg_f_wrap input::-moz-range-thumb,
.pccp_opacity_f_wrap input::-moz-range-thumb {
    width: 15px;
    height: 15px;
    background: #888;
    cursor: pointer;
    border-radius: 50%;
    border: 1px solid #aaa;
    box-shadow: 0 0 0 5px #fff inset, 0 0 2px rgba(0,0,0,.15);
}

#lc-color-picker.lccp_dark_theme {
    background: #333;
    border-color: #505050;
}
.lccp_dark_theme img {
	filter: invert(100%);
    opacity: .85;
}
.lccp_dark_theme .lccp_gradient_range img {
	opacity: .6;
}
.lccp_dark_theme .lccp_gradient_range img:hover {
    opacity: .8;
}
.lccp_dark_theme .lccp_gradient {
    border-color: #626262;
}
.lccp_dark_theme .lccp_sel_step {
	box-shadow: 0 0 2px 1px orange;
}
#lc-color-picker.lccp_dark_theme hr {
	border-color: #727272;
}
.lccp_dark_theme .pccp_deg_f_wrap input,
.lccp_dark_theme .pccp_color_f_wrap input,
.lccp_dark_theme .pccp_opacity_f_wrap input {
	border-color: #777;
	background: #505050;
	color: #f3f3f3;
}
.lccp_dark_theme input[type=range] {
    background: #808080;
}
</style>`);
        };


        // init when called
        this.init();
    };






    // UTILITIES

    // sanitize "selector" parameter allowing both strings and DOM objects
    const maybe_querySelectorAll = (selector) => {

        if(typeof(selector) != 'string') {
            if(selector instanceof Element) { // JS or jQuery
                return [selector];
            }
            else {
                let to_return = [];

                for(const obj of selector) {
                    if(obj instanceof Element) {
                        to_return.push(obj);
                    }
                }
                return to_return;
            }
        }

        // clean problematic selectors
        (selector.match(/(#[0-9][^\s:,]*)/g) || []).forEach(function(n) {
            selector = selector.replace(n, '[id="' + n.replace("#", "") + '"]');
        });

        return document.querySelectorAll(selector);
    };


})());
