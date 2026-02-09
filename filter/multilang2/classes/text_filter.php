<?php
// This file is part of filter_multilang2 - https://moodle.org/
//
// filter_multilang2 is free software: you can redistribute it and/or
// modify it under the terms of the GNU General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// filter_multilang2 is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with filter_multilang2.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Multi-language content filter, with simplified syntax.
 *
 * @package    filter_multilang2
 * @copyright  Gaetan Frenoy <gaetan@frenoy.net>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Given multilinguage text, return relevant text according to current language.
 *
 * The way the filter works is as follows:
 *
 *    - look for multilang blocks in the text.
 *    - if there exist texts in the currently active language, print them.
 *    - else, if there exist texts in the parent language(s) of the
 *      currently active language, unless the parent language is 'en',
 *      print them (default "parent language behaviour", this is now
 *      configurable)
 *    - else, if there exist texts in the language 'other', print them.
 *    - else, don't print any text inside the lang block (this is a change
 *      from previous filter versions behaviour!!!!)
 *
 *  Please note that English texts are not used as default anymore!
 *  Language 'other' can be used for fallbacks.
 *
 *  This version is based on original multilang filter by Gaetan Frenoy, Eloy and skodak.
 *
 *  Following new syntax is not compatible with old one:
 *    {mlang XX}one lang{mlang}Some common text for any language.{mlang YY}another language{mlang}
 *
 *  2019.11.19 A new enhanced syntax to be able to specify multiple languages
 *  for a single tag is now available. Just specify the list of the languages
 *  separated by commas:
 *    {mlang XX,YY,ZZ}Text displayed if current lang is XX, YY or ZZ, or one of their parent laguages.{mlang}
 *
 * @package    filter_multilang2
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_multilang2;

if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'filter_multilang2_base_text_filter');
} else {
    class_alias('\moodle_text_filter', 'filter_multilang2_base_text_filter');
}

/**
 * A Moodle text filter to enable multilangual content.
 *
 * @package    filter_multilang2
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 *             2015 onwards Iñaki Arenaza & Mondragon Unibertsitata
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \filter_multilang2_base_text_filter {

    /**
     * @var array Cache of parent language(s) of a given language
     */
    protected static $parentcache;

    /**
     * @var object An instance of the string_manager class.
     */
    protected static $stringmanager;

    /**
     * @var string The langauge we are currently using to filter multilang blocks.
     *             It can be either the user current language, or the language 'other'
     */
    protected $lang;

    /**
     * @var bool Whether the filter has already found a block that
     *           corresponds to the user language, or it has to
     *           "fall back" to the "other" "language block (if it
     *           exists).
     */
    protected $replacementdone;

    /**
     * @var string What parent languages behaviour is configured for the plugin.
     */
    protected $parentlangbehaviour;

    /**
     * Constructor - Make sure we use the configured parent languages behaviour.
     */
    public function __construct() {
        $this->parentlangbehaviour = get_config('filter_multilang2', 'parentlangbehaviour');
        self::$stringmanager = get_string_manager();
    }

    /**
     * Filter text before changing format to HTML.
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function filter_stage_pre_format(string $text, array $options): string {
        // Ideally we want to get rid of all other languages before any text formatting.
        return $this->filter($text, $options);
    }

    /**
     * Filter HTML text before sanitising text.
     *
     * Text sanitisation might not be performed if $options['noclean'] true.
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function filter_stage_pre_clean(string $text, array $options): string {
        return $text;
    }

    /**
     * Filter HTML text after sanitisation.
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function filter_stage_post_clean(string $text, array $options): string {
        return $text;
    }

    /**
     * Filter simple text coming from format_string().
     *
     * Note that unless $CFG->formatstringstriptags is disabled
     * HTML tags are not expected in returned value.
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function filter_stage_string(string $text, array $options): string {
        return $this->filter($text, $options);
    }

    /**
     * Reset the parent languages cache.
     *
     * We need to reset the parent languages cache every time we
     * switch from one parent languages behaviour to another. Because
     * different behaviours may build different parent languages
     * hierarchies.
     *
     * Specially useful (but not only) for unit tests.
     *
     */
    public static function reset_parentcache(): void {
        self::$parentcache = ['other' => []];
    }

    /**
     * This function filters the received text based on the language
     * tags embedded in the text, and the current user language or
     * 'other', if present.
     *
     * @param string $text The text to filter.
     * @param array $options The filter options.
     * @return string The filtered text for this multilang block.
     */
    public function filter($text, array $options = []) {

        if (!is_string($text) || empty($text)) {
            // Non-string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, 'mlang') === false) {
            // Performance shortcut - if there is no 'mlang' text, nothing can match.
            return $text;
        }

        if (!isset(self::$parentcache)) {
            self::reset_parentcache();
        }

        $this->replacementdone = false;
        $currlang = current_language();
        if ($this->parentlangbehaviour === 'never') {
            self::reset_parentcache();
        } else if (!array_key_exists($currlang, self::$parentcache)) {
            $parentlangs = self::$stringmanager->get_language_dependencies($currlang);
            if ($this->parentlangbehaviour === 'include_en') {
                if (count($parentlangs) > 0) {
                    $realrootisen = self::$stringmanager->get_string('parentlanguage', 'langconfig',
                                                                     null, $parentlangs[0]);
                    if ($realrootisen === 'en') {
                        $parentlangs = array_merge(['en'], $parentlangs);
                    }
                }
            }
            self::$parentcache[$currlang] = $parentlangs;
        }

        $search = '/{\s*mlang\s+(                               # Look for the leading {mlang
                                    (?:[a-z0-9_-]+)             # At least one language must be present
                                                                # (but dont capture it individually).
                                    (?:\s*,\s*[a-z0-9_-]+\s*)*  # More can follow, separated by commas
                                                                # (again dont capture them individually).
                                )\s*}                           # Capture the language list as a single capture.
                   (.*?)                                        # Now capture the text to be filtered.
                   {\s*mlang\s*}                                # And look for the trailing {mlang}.
                   /isx';

        $replacelang = $currlang;
        $result = preg_replace_callback($search,
                                        function ($matches) use ($replacelang) {
                                            return $this->replace_callback($replacelang, $matches);
                                        },
                                        $text);
        if (is_null($result)) {
            return $text; // Error during regex processing, keep original text.
        }
        if ($this->replacementdone) {
            return $result;
        }

        $replacelang = 'other';
        $result = preg_replace_callback($search,
                                        function ($matches) use ($replacelang) {
                                            return $this->replace_callback($replacelang, $matches);
                                        },
                                        $text);
        if (is_null($result)) {
            return $text;
        }
        return $result;
    }

    /**
     * This function filters the current block of multilang tag. If
     * any of the tag languages (or their parent languages) match the
     * specified filtering language, it returns the text of the
     * block. Otherwise it returns an empty string.
     *
     * @param string $replacelang A string that specifies the language used to
     *                            filter the matches.
     * @param array $langblock An array containing the matching captured pieces of the
     *                         regular expression. They are the languages of the tag,
     *                         and the text associated with those languages.
     * @return string
     */
    protected function replace_callback($replacelang, $langblock): string {
        /* Normalize languages. We can use strtolower instead of
         * core_text::strtolower() as language short names are ASCII
         * only, and strtolower is much faster. We have to remove the
         * white space between language names to be able to match them
         * to official language names.
         */
        $blocklangs = explode(',', str_replace(' ', '', str_replace('-', '_', strtolower($langblock[1]))));
        $blocktext = $langblock[2];
        if ($this->parentlangbehaviour === 'never') {
            $parentlangs = [];
        } else {
            $parentlangs = self::$parentcache[$replacelang];
        }
        foreach ($blocklangs as $blocklang) {
            /* We don't check for empty values of $blocklang as they simply don't
             * match any language and they don't produce any errors or warnings.
             */
            if (($blocklang === $replacelang) || in_array($blocklang, $parentlangs)) {
                $this->replacementdone = true;
                return $blocktext;
            }
        }

        return '';
    }
}
