TinyMCE multilanguage plugin
============================

![Release](https://img.shields.io/badge/Release-1.6-blue.svg)
[![Moodle Plugin CI](https://github.com/bfh/moodle-tiny_multilang2/actions/workflows/moodle-plugin-ci.yml/badge.svg)](https://github.com/bfh/moodle-tiny_multilang2/actions/workflows/moodle-plugin-ci.yml)
[![PHP Support](https://img.shields.io/badge/php-7.4--8.4-blue)](https://github.com/bfh/moodle-tiny_multilang2/actions)
[![Moodle Support](https://img.shields.io/badge/Moodle-4.1--5.0-orange)](https://github.com/bfh/moodle-tiny_multilang2/actions)
[![License GPL-3.0](https://img.shields.io/github/license/bfh/moodle-tiny_multilang2?color=lightgrey)](https://github.com/bfh/moodle-tiny_multilang2/blob/main/LICENSE)
[![GitHub contributors](https://img.shields.io/github/contributors/bfh/moodle-tiny_multilang2)](https://github.com/bfh/moodle-tiny_multilang2/graphs/contributors)

This plugin will make the creation of multilingual contents on Moodle much easier with the TinyMCE editor.

The plugin is developed to work with the optionally installed
[Iñaki Arenaza's multilang2 filter](https://github.com/iarenaza/moodle-filter_multilang2).

This plugin was started as an adaption of [Iñaki Arenaza's plugin for legacy TinyMCE editor](https://github.com/iarenaza/moodle-tinymce_moodlelang2)
and has been made to work with TinyMCE 6 that is included in Moodle ≥ 4.1. Further development
of this plugin added support for the builtin multilanguage tags that are suppored in
Moodle core, so that the multilang2 filter is no requirement anymore.

After the installation of the plugin, the TinyMCE shows a new Button (with a globe icon) and a menu entry in the
"Format" section where you can select a language. Clicking on a language entry adds a language opening and closing
tag to your text at the current cursor position. If there is a selection then the language tags are placed around
the selection. In case you use the highlighting option and see the yellow language tags, you may click one of these
tags then select a new language from the menu and that will change the
language of the existing tag. You may also remove it by hitting the backspace
or delete key. This will remove the tag, and it's counterpart in case the
formatting of the text is correct.

## Current version

The latest release is v1.5 (build 2025042300) for Moodle 4.1 and newer.

## Requirements

There are no additional requirements. To benefit from the plugin capabilities
the TinyMCE editor must be used in text area (either set as a user preference or
being the standard editor set by the Moodle site admin). 
If you use the plugin with the [multilang2 filter][1] the
`{mlang}` tags for the filter are used. If the filter is not installed,
the standard `<span class="multilang">` tags are used.

## Installation

 - Unzip the archive file or copy repository content into `*moodleroot*/lib/editor/tiny/plugins`.
 - Install the plugin within Moodle via the upgrade script.
 - Optional: Adjust the settings in "Site administration" -> "Plugins" -> "Tiny Multi-Language Content (v2)".

## Troubleshooting

If the language selection does not appear in the editor:
 - Check that the [multilang2 filter][1] is installed and enabled.
 - Check that your site has at least two languages installed.
 - If you don't have more than one language installed, enable the `addlanguage` option and
   set a few language iso codes in the `languageoptions` setting.

If your language tags are saved correctly but all language content is displayed, no matter
which user language is selected, then check that you have the language tags for a certain
phrase within the same block element (e.g. paragraph). See details at the
[Moodle documentation](https://docs.moodle.org/en/Multi-language_content_filter).  
This does not apply if you use the [multilang2 filter][1] because there is a strict filtering
even though elements (referring to the same content in different languages) span via multiple
paragraphs.

## Usage

### Editor

Whenever a textfield appears where the editor is used, the plugin can be used either by clicking
the globe icon button in the toolbar or via the *Format* menu. To make a text language dependent
mark the text, then select a language from the menu. As soon as the language is selected, you
see language markers at the beginning and the end of your previously selected text.

The language tags itself cannot be edited. Instead, click on a language tag and the context
menu appears. This allows you to change the language of an existing tag or remove the language
tags for that selection. These options can also be accessed via the *Format* menu or via the
toolbar button.

### Text conversion between markers and html

Whenever the save button is hit to save the text, the language tag marker in the WYSIWYG mode
of the editor are converted to correct language tags that can be used within the text.
If the [multilang2 filter][1] is installed
and active on the Moodle site, the `{mlan xx}` annotation is used, where `xx` is the iso code of
the selected language. If the [multilang2 filter][1] is not installed, the language tags are
transformed into the standard Moodle annotation. These are span elements that look like
`<span class="multilang" lang="xx" dir="ltr">`, where `xx` contains the iso code of the language
and the `dir` attribute annotates whether this is a left to right language or vice versa.

Note: for the moment the `dir="ltr"` attribute is not included in the code because
it breaks the Moodle multilanguage filter.

When loading a text, both annotations are read and translated into marked language tags in the
WYSIWYG mode. If mixed annotations are used in the text, these are preserved upon
saving.

### Possible languages options

The languages that are shown in the toolbar menu, *Format* menu or context menu are the same.
However, there are a few exceptions. The *Remove all language tags* is not displayed in the
context menu. This option removes any language tags that are currently in the edited text.

The context menu has no *Remove all tags* option. Instead, it shows a trash icon that removes
the selected language tag pairs.  
Furthermore, there is no context menu at all if `showalllangs` is enabled, because that would
make the menu having too many options.

The option *Fallback (other)* is available only when the [multilang2 filter][1] is installed
because the standard Moodle has no fallback option.

The languages are ordered alphabetically when using the installed languages or the option
`showalllang` is enabled. If the languages are defined via `languageoptions` the order of the
provided iso codes is preserved.

## Version History

### 1.6
- Fix format of deprecated language strings [#20](https://github.com/bfh/moodle-tiny_multilang2/pull/20), thank you [Jason Platts](https://github.com/jason-platts)
- Remove deprecated capability tiny/multilang2:viewlanguagemenu
- Adjust capability context level according to [MDL-84884](https://tracker.moodle.org/browse/MDL-84884)

### 1.5
- Add support for Moodle 5.0.
- Add support for moodle icon [#19](https://github.com/bfh/moodle-tiny_multilang2/pull/19),
  thank you [Jason Platts](https://github.com/jason-platts).

### 1.4
- Add new capability tiny/multilang2:use that is the default capability required by Moodle.
- Declare capability tiny/multilang2:viewlanguagemenu to be obsolete.
- Rewrite the HTML parser to highlight the language tags.
- Add some js unit tests to the project (HTML parser at the moment only).
- Improve parsing of html when {mlang} tags are misplaced. Also, be more flexible where
  mlang tags can be placed.
- TinyMCE\Lang: mlang tag disappears after viewing the source code Pro #841026,
  thanks to [Tai Le Tan](https://github.com/tailetan)

### 1.3
- Adaptions for Moodle 4.5.

### 1.2
- Removal of the attribute `dir` e.g. `dir="ltr"` for language direction because it
  breaks the standard multilanguage filter in Moodle.
- Support for Moodle 4.4 and PHP 8.3.
- Corrections of content and typos in README.
- Maturity changed to STABLE.

### 1.1
- Fix finally [There are some issues with the latest version #6](https://github.com/bfh/moodle-tiny_multilang2/issues/6)
- Fix problem with already defined behat test steps by switching behat class to use traits
  (thank you, [Jason Platts](https://github.com/jason-platts))
- Removed setting: `fallbackspantag`
    - It was not observed correctly (markup in the editor is still
      applied even if the setting was off).
    - Correcting the first point would have lead to no styling which
      is somewhat awkward when there are `<span class="multilang">`
      tags contained in the text but not marked as such (because
      of the setting being disabled).
- In Site administration -> Plugins -> Text editors the label for this plugin settings uses
  now the same language key as the plugin name itself (the "settings" suffix vanished).
- Add ci workflow for Moodle 4.3 and adapt behat test that used the source view of TinyMCE. 

### 1.0

- Fix issue [TinyMCE link plugin does not work properly when multilang2 is installed](https://github.com/bfh/moodle-tiny_multilang2/issues/4).
  Many thanks to [Mario Wehr](https://github.com/mwehr) for his contribution.
- Fix: tag replacement did not work in the same way when highlighting was on or off.
- Fix: additional CSS for highlighting the code was not applied correctly.
- Fix: context menu sometimes inserted empty tags, when another language was selected.
- Fix: if an entire paragraph is selected, language tags are now applied inside the content
  (within te block element)
- New settings so that the admin may define a list of languages that can be used with the
  plugin, independent whether these language packages are installed in the Moodle site or not.
- New option to remove all language tags within the text at once.
- New feature, `<span>` elements use the `dir` attribute to annotate left to right and
  right to left languages (in standard Moodle annotation).
- New feature: the context menu has an icon to remove the selected language tag pair.
- Enhanced documentation to include new changes and add a chapter about usage. 
- Lift version from 0.3 to 1.0 to reflect maturity.

Many thanks to [Tai Le Tan](https://github.com/tailetan) from the Open University that
provided a comprehensive [pull request](https://github.com/bfh/moodle-tiny_multilang2/pull/3).

### 0.3

- Support for the standard language tags in Moodle without having to install the [filter_multilang2][1].
- Tooltip with possible languages is shown when a highlighted language tag is selected (only when `showalllanguages` is off, otherwise the tooltip list would be too long).
- Fix: when the text editor lost the focus the highlighting of the language tags disappeared.
- Fix: the iso code was not displayed in the language list in the menu when `showalllanguages` was enabled.
- Some internal refactoring because of the changes from above that made them necessary.
- Adjust behat tests to test both, standard annotation and multilang2 filter annotation.
- Change code maturity to RC.

### 0.2.3

- Fix links in README file and add license file.

### 0.2.2

- Lift software maturity level to STABLE.
- Adapt CI to test against Moodle 4.2.
- Fix issue [Probably, $string['helplinktext'] = 'Multi-Language Content (v2)'; is needed in the lang strings](https://github.com/bfh/moodle-tiny_multilang2/issues/1).
- Add version history to README file.

### 0.2.1

Initial release

[1]: <https://moodle.org/plugins/filter_multilang2> "Multilang v2 Filter Plugin"
