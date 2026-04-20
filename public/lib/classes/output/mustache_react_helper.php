<?php
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

namespace core\output;

use Mustache\LambdaHelper;

/**
 * Mustache helper for rendering React component mount points.
 *
 * Generates a `<div>` with data attributes that contain the component reference and props.
 * The component value must be a fully-qualified ESM specifier in the form `@moodle/lms/<component>/<module>`,
 * for example `@moodle/lms/mod_book/viewer` or `@moodle/lms/mod_forum/discussion`.
 *
 * ```
 * {{#react}}
 * {
 *     "component": "@moodle/lms/mod_book/viewer",
 *     "props": {
 *         "title": "{{#str}}confirm, core{{/str}}",
 *         "buttons": ["cancel", "confirm"]
 *     },
 *     "id": "confirmation-modal",
 *     "class": "modal-wrapper",
 * }
 * <p>Loading...</p>
 * {{/react}}
 * ```
 *
 * @package    core
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_react_helper {
    /**
     * Render React component mount point.
     *
     * @param string $text JSON config and optional inner content
     * @param LambdaHelper $helper Mustache lambda helper
     * @return string HTML output
     */
    public function react(string $text, LambdaHelper $helper): string {
        $text = trim($helper->render($text));

        if (empty($text)) {
            return '';
        }

        [$json, $content] = $this->split_json_content($text);
        $config = $this->decode_json($json);

        // Fallback to plain div if JSON invalid but has content.
        if ($config === null) {
            return $content ? '<div>' . $content . '</div>' : '';
        }

        $attrs = $this->get_attributes($config);
        return '<div' . $attrs . '>' . $content . '</div>';
    }

    /**
     * Split input into JSON block and remaining content.
     *
     * @param string $text Input text
     * @return array [json_string, inner_content]
     */
    private function split_json_content(string $text): array {
        if ($text[0] !== '{') {
            return ['', $text];
        }

        // The most common case is that the JSON config is the only content, so we can skip the more complex parsing.
        if (json_validate($text)) {
            return [$text, ''];
        }

        // The next simplest case is that the JSON config is at the start, so we can just find the closing brace of the first JSON block.
        $lastcurlindex = strrpos($text, '}');
        if ($lastcurlindex !== false) {
            $potentialjson = substr($text, 0, $lastcurlindex + 1);
            if (json_validate($potentialjson)) {
                return [$potentialjson, trim(substr($text, $lastcurlindex + 1))];
            }
        }

        $len = strlen($text);
        $depth = 0;
        $inquotes = false;
        $escaped = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inquotes = !$inquotes;
                continue;
            }

            if ($inquotes) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } else if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return [
                        substr($text, 0, $i + 1),
                        trim(substr($text, $i + 1)),
                    ];
                }
            }
        }

        return [$text, ''];
    }

    /**
     * Decode JSON with automatic cleanup.
     *
     * @param string $json JSON string
     * @return array|null Decoded array or null on failure
     */
    private function decode_json(string $json): ?array {
        if (json_validate($json) === false) {
            // Attempt to clean common issues like trailing commas and re-validate.
            $json = preg_replace('/,\s*([}\]])/', '$1', $json);
            if (json_validate($json) === false) {
                debugging('Invalid JSON in mustache react helper.' . "\n" . $json, DEBUG_DEVELOPER);
                return null;
            }
        }

        $result = json_decode($json, true);

        return is_array($result) ? $result : null;
    }

    /**
     * Build an HTML attribute string from config.
     *
     * `data-react-props` is single-quoted so that JSON's double quotes do not
     * need HTML-encoding and remain parseable by JSON.parse() without decoding.
     * All other attribute values are escaped with s() to prevent XSS.
     *
     * @param array $config Configuration array
     * @return string Attribute string with a leading space, ready to splice into a tag.
     */
    private function get_attributes(array $config): string {
        $out = '';

        if (!empty($config['component'])) {
            $out .= ' data-react-component="' . s($config['component']) . '"';
        }

        if (isset($config['props']) && is_array($config['props'])) {
            $props = json_encode($config['props'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
            $out .= ' data-react-props=\'' . $props . '\'';
        }

        foreach ($config as $name => $val) {
            if ($name === 'component' || $name === 'props' || $val === null || $val === '') {
                continue;
            }

            if (is_bool($val)) {
                if ($val) {
                    $out .= ' ' . s($name);
                }
            } else if (is_array($val)) {
                $encoded = json_encode($val, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
                $out .= ' ' . s($name) . '="' . s($encoded) . '"';
            } else {
                $out .= ' ' . s($name) . '="' . s($val) . '"';
            }
        }

        return $out;
    }
}
