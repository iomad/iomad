Atto multilanguage plugin
=========================

![Release](https://img.shields.io/badge/release-v1.9-blue.svg) ![Supported](https://img.shields.io/badge/supported-2.9%2C%203.0%2C%203.1%2C%203.2%2C%203.3%2C%203.4-green.svg)

This plugin will make the creation of multilingual contents on Moodle much more easier with Atto editor.

The plugin is developed to work with [Iñaki Arenaza's multilang2 filter](https://github.com/iarenaza/moodle-filter_multilang2), and the idea is based on [his plugin for TinyMCE editor](https://github.com/iarenaza/moodle-tinymce_moodlelang2).

## Current version

The latest release is v1.9 (build 2017102700) for Moodle 2.9, 3.0, 3.1, 3.2, 3.4 and 3.4 (Checkout [v2.9.1.9](https://github.com/iarenaza/moodle-atto_multilang2/releases/tag/v2.9.1.9), [v3.0.1.9](https://github.com/iarenaza/moodle-atto_multilang2/releases/tag/v3.0.1.9), [v3.1.1.9](https://github.com/iarenaza/moodle-atto_multilang2/releases/tag/v3.1.1.9), [v3.2.1.9](https://github.com/iarenaza/moodle-atto_multilang2/releases/tag/v3.2.1.9) and [v3.3.1.9](https://github.com/iarenaza/moodle-atto_multilang2/releases/tag/v3.3.1.9) releases, respectively.

## Changes from v1.8
 - Added Grunt support files. No we can check Moodle Javascript coding guidelines and minify the plugin code without installing the plugins.
 - Minor Javascript coding guidelines fix (signalled by the minifier, but ignored by the linter!)
 
## Changes from v1.7
 - Multiple bug fixes, especially when there were more than one Atto editor in the same page. Closes issues #2, #18, #21.
 - Multilang tag highlighting <span>'s are now removed/added when switching to/from HTML view, reducing clutter when editing the HTML code.
 - Cleaned the code to conform to PHP, PHPDoc and Javascript Moodle coding guidelines (all checks pass!)

## Requirements
As mentioned before, [filter_multilang2](https://github.com/iarenaza/moodle-filter_multilang2) is required.

## Installation

 - Copy repository content in *moodleroot*/lib/editor/atto/plugins. The following can be omitted:
   - tests/ (if you're not going to test it with Behat)
   - .gitmodules
   - build.xml
 - Install the plugin from Moodle. 
 - Go to "Site administration" >> "Plugins" >> "Text editors" >> "Atto HTML editor" >> "Atto toolbar settings", and add *multilang2* to the Toolbar config where you prefer. E.g. `multilang2 = multilang2` (see [Text editor - Toolbar settings](https://docs.moodle.org/en/Text_editor#Toolbar_settings) and [Text editor - Adding extra buttons](https://docs.moodle.org/en/Text_editor#Adding_extra_buttons) for instructions on how to add a plugin button to Atto toolbar.
