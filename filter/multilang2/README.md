moodle-filter_multilang2
========================

[![Latest Release](https://img.shields.io/github/v/release/iarenaza/moodle-filter_multilang2?sort=semver&color=orange)](https://github.com/iarenaza/moodle-filter_multilang2/releases)
[![Moodle plugin CI](https://github.com/iarenaza/moodle-filter_multilang2/actions/workflows/moodle-plugin-ci.yml/badge.svg?branch=master)](https://github.com/iarenaza/moodle-filter_multilang2/actions/workflows/moodle-plugin-ci.yml)

# To Install it manually #
- Unzip the plugin in the moodle .../filter/ directory.

# To Enable it #
- Go to "Site Administration &gt;&gt; Plugins &gt;&gt; Filters &gt;&gt; Manage filters" and enable the plugin there.

# To Use it #
- Create your contents in multiple languages.
- Enclose every language content between `{mlang XX}` and `{mlang}` tags (also known as a "language blocks"):
  <pre>
    {mlang XX}content in language XX{mlang}
    {mlang YY}content in language YY{mlang}
    {mlang other}content for other languages{mlang}</pre>
- where **XX** and **YY** are the Moodle short names for the language packs (i.e., `en`, `en_CA`, `en_kids`, `fr_CA`, `de`, etc.) or the special language name `other`.
- **Version 1.1.1** and later: a new enhanced syntax to be able to specify multiple languages for a single tag is now available. Just specify the list of the languages separated by commas:
  <pre>
  {mlang en,es,fr_CA}Text displayed if current language is en, es or fr_CA, or one of their parent laguages.{mlang}</pre>
- Test it (by changing your browsing language in Moodle).

# How it works #
## Default behaviour ##
- Look for "language blocks" in the text to be filtered.
- For each "language block":
  - If there are texts in the currently active language, print them.
  - Else, if there exist texts in the parent language(s) of the currently active language, unless the parent language is 'en', print them. This behaviour is configurable in version 2.0.5 and later (see "Configurable parent languages behaviour" below).
  - Else, as fallback, print the text with language 'other' if such one is set.
  - Else, don't print any text inside the language block.
- Text outside of "language blocks" will always be shown.

## Configurable parent languages behaviour ##
Since version 2.0.5, the plugin offers a setting to configure how the filter will behave, with respect to parent languages, when processing a language block.

The filter determines whether a language block should be displayed based on the languages specified in the block and the current language being used by the user ("the user's current language"). This matching process can follow three different approaches, known as "_parent languages behaviour_":

1. **Always use parent languages, excluding 'en'.**
  * This is the default setting. The filter considers the languages listed in the language block's {mlang} tag, and all of their parent languages (up to but not including the root 'en' language).
  * Example: If a language block specifies '{mlang en_us_k12}', it will only display if the user's current language is 'en_us_k12' or 'en_us' but not 'en'.
  * Note: English can still be used explicitly in the language block. For example, {mlang en}This text will be shown when the user’s current language is 'en'.{mlang} will display the content when the user's current language is 'en'.
1. **Always use parent languages, including 'en'.**
  * This setting works like the previous one but includes the root 'en' as a valid parent language.
  * Example: If a language block specifies '{mlang en}', it will display if the user's current language is either 'en_us_k12', 'en_us' or 'en'.
1. **Never use parent languages.**
  * As the name suggests, no parent languages are used. The filter only matches the languages explicitly listed in the language block, without considering any parent languages.
  * Example: If a language block specifies '{mlang en_us_k12}', it will only display if the user's current language is 'en_us_k12', not 'en_us' or 'en'.

## Definition of "language block" ##
Is any text (including spaces, tabs, linefeeds or return characters) placed between '{mlang XX}' and '{mlang}' markers. You can not only put text inside "language block", but also images, videos or external embedded content. For example, this is a valid "language block":

<pre>
{mlang es,es_mx,es_co}
First paragraph of text. First paragraph of text. First paragraph of text.

Second paragraph of text. Second paragraph of text. Second paragraph of text.

                   An image could go here

Third paragraph of text. Third paragraph of text. Third paragraph of text.

                   An embedded Youtube video could go here

Fourth paragraph of text. Fourth paragraph of text. Fourth paragraph of text.
{mlang}
</pre>

## A couple of examples in action ##

### Using text only

This text:
  <pre>
  {mlang other}Hello!{mlang}{mlang es,es_mx}¡Hola!{mlang}
  This text is common for all languages because it's outside of all language blocks.
  {mlang other}Bye!{mlang}{mlang it}Ciao!{mlang}</pre>
- If the current language is any language except "Spanish International", "Spanish - Mexico" or Italian, it will print:
  <pre>
  Hello!
  This text is common for all languages because it's outside of all language blocks.
  Bye!</pre>
- If the current language is "Spanish International" or "Spanish - Mexico", it will print:
  <pre>
  ¡Hola!
  This text is common for all languages because it's outside of all language blocks.</pre>
-  Notice the final 'Bye!' / 'Ciao!' is not printed.
- If the current language is Italian, it will print:
  <pre>
  This text is common for all languages because it's outside of all language blocks.
  Ciao!</pre>
-  Notice the leading 'Hello!' / '¡Hola!' and the final 'Bye!' are not printed.

### Using text, images and external embedded content

We create a label with the content shown in the following image:

<img src="https://moodle.org/pluginfile.php/50/local_plugins/plugin_screenshots/1560/multilang-example-1-modificado.png" height="694" width="800" alt="Multi-Language content in Spanish, English, an language-independent content" />

The "language block" tags are highlighted using blue boxes. You can see that we have three pieces of content: the Spanish-only content (light yellow box), the language-independent content (light blue) and the English-only content (light red).

If the user browses the page with English as her configured language, she will see the common content (light blue box) and the English-only content (light red):

<img src="https://moodle.org/pluginfile.php/50/local_plugins/plugin_screenshots/1560/multilang-example-2.png" width="800" height="586" alt="Multi-Language content when browsed in English" />

If the user browses the page with Spanish as her configured language, she will see the Spanish-only content (light yellow) plus the common content (light blue box):

<img src="https://moodle.org/pluginfile.php/50/local_plugins/plugin_screenshots/1560/multilang-example-3.png" width="800" height="586" alt="Multi-Language content when browsed in Spanish" />
