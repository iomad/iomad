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

declare(strict_types=1);

namespace core\output;

use Mustache\LambdaHelper;

/**
 * Unit tests for mustache_react_helper.
 *
 * @package    core
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mustache_react_helper::class)]
final class mustache_react_helper_test extends \advanced_testcase {
    /** @var LambdaHelper|null Helper to handle lambda rendering. */
    private $lambdahelper = null;
    /** @var mustache_react_helper|null Instance of the React mustache helper under test. */
    private $helper = null;

    /**
     * Sets up the test environment before each test case is run.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->lambdahelper = new LambdaHelper(new \Mustache\Engine(), new \Mustache\Context());
        $this->helper = new mustache_react_helper();
    }

    /**
     * Cleans up the test environment after each test case has run.
     */
    public function tearDown(): void {
        $this->lambdahelper = null;
        $this->helper = null;
        parent::tearDown();
    }

    /**
     * Data provider for test_react_output.
     *
     * Each entry: [ input, strings_that_must_be_present, strings_that_must_be_absent ]
     *
     * @return array[]
     */
    public static function react_output_provider(): array {
        return [
            'basic component with props' => [
                '{"component":"@moodle/lms/mod_book/viewer","props":{"label":"Save"}}',
                ['data-react-component="@moodle/lms/mod_book/viewer"', 'data-react-props=\'{"label":"Save"}\''],
                [],
            ],
            'component without props' => [
                '{"component":"@moodle/lms/mod_book/viewer"}',
                ['data-react-component="@moodle/lms/mod_book/viewer"'],
                ['data-react-props'],
            ],
            'props without component' => [
                '{"props":{"user":"John","role":"admin"}}',
                ['data-react-props=\'{"user":"John","role":"admin"}\''],
                ['data-react-component'],
            ],
            'custom HTML attributes' => [
                '{"component":"@moodle/lms/mod_book/viewer","id":"test-modal","class":"large"}',
                ['id="test-modal"', 'class="large"'],
                [],
            ],
            'boolean true attribute is rendered, false is omitted' => [
                '{"component":"@moodle/lms/mod_book/viewer","disabled":true,"hidden":false}',
                [' disabled'],
                ['hidden'],
            ],
            'array values in attributes are JSON-encoded' => [
                '{"component":"@moodle/lms/mod_book/viewer","data-values":[10,20,30]}',
                ['data-values="[10,20,30]"'],
                [],
            ],
            'array values containing strings break attribute quoting' => [
                '{"component":"@moodle/lms/mod_book/viewer","data-values":["a","b"]}',
                ['data-values="[&quot;a&quot;,&quot;b&quot;]"'],
                [],
            ],
            'inner content is preserved' => [
                '{"component":"@moodle/lms/mod_book/toc"}<p>Loading...</p>',
                ['<p>Loading...</p>', 'data-react-component="@moodle/lms/mod_book/toc"'],
                [],
            ],
            'multiline JSON with content' => [
                '{
    "component": "@moodle/lms/mod_book/viewer",
    "props": {
        "title": "Confirm"
    }
}
<div class="skeleton"></div>',
                ['data-react-component="@moodle/lms/mod_book/viewer"', '<div class="skeleton"></div>'],
                [],
            ],
            'trailing comma after last top-level property is auto-fixed' => [
                '{"component":"@moodle/lms/mod_book/viewer","class":"primary",}',
                ['data-react-component="@moodle/lms/mod_book/viewer"', 'class="primary"'],
                [],
            ],
            'trailing comma inside nested props object is auto-fixed' => [
                '{"component":"@moodle/lms/mod_book/viewer","props":{"label":"Save","type":"submit",}}',
                ['data-react-component="@moodle/lms/mod_book/viewer"', '"label":"Save"', '"type":"submit"'],
                [],
            ],
            'trailing comma inside props array is auto-fixed' => [
                '{"component":"@moodle/lms/mod_book/viewer","props":{"tags":["php","moodle",]}}',
                ['data-react-component="@moodle/lms/mod_book/viewer"', '"tags":["php","moodle"]'],
                [],
            ],
            'plain div without component or props' => [
                '{"id":"wrapper","class":"container"}<h1>Title</h1>',
                ['id="wrapper"', 'class="container"', '<h1>Title</h1>'],
                ['data-react-component', 'data-react-props'],
            ],
            'escaped values with preserved inner content' => [
                '{"id":"wrapper","class":"container","props":{"user":{"name":"J\\\\D"}}}<h1>Title\\Thing{}s</h1>',
                [
                    'id="wrapper"',
                    'class="container"',
                    '<h1>Title\\Thing{}s</h1>',
                    '"user":{"name":"J\\\\D"}',
                ],
                [],
            ],
            'extra closing brace' => [
                '{"id":"wrapper","class":"container","props":{"user":{"name":"J\\\\D"}}}}<h1>Title\\Thing{}s</h1>',
                [
                    'id="wrapper"',
                    'class="container"',
                    '<h1>Title\\Thing{}s</h1>',
                    '"user":{"name":"J\\\\D"}',
                ],
                [],
            ],
            'XSS in attribute value is escaped' => [
                '{"component":"@moodle/lms/mod_book/viewer","class":"<script>alert(1)</script>"}',
                ['&lt;script&gt;'],
                ['<script>'],
            ],
            'XSS in component name is escaped' => [
                '{"component":"@moodle/lms/mod_book/viewer\"><script>alert(1)</script>"}',
                ['&lt;script&gt;', '&quot;'],
                ['<script>'],
            ],
            'single quote in prop value is encoded for single-quoted attribute' => [
                '{"component":"@moodle/lms/mod_book/viewer","props":{"label":"it\'s fine"}}',
                ["\u0027s fine"],
                ["data-react-props='it's"],
            ],
            'null and empty string attribute values are omitted' => [
                '{"component":"@moodle/lms/mod_book/viewer","data-x":null,"data-y":""}',
                ['data-react-component="@moodle/lms/mod_book/viewer"'],
                ['data-x', 'data-y'],
            ],
            'integer attribute value is cast to string' => [
                '{"component":"@moodle/lms/mod_book/viewer","data-count":42}',
                ['data-count="42"'],
                [],
            ],
            'non-array props is silently ignored' => [
                '{"component":"@moodle/lms/mod_book/viewer","props":"invalid"}',
                ['data-react-component="@moodle/lms/mod_book/viewer"'],
                ['data-react-props'],
            ],
        ];
    }

    /**
     * Test that react() produces correct HTML for a variety of valid inputs.
     *
     * @param string $input JSON config and optional inner content.
     * @param string[] $contains Substrings that must appear in the output.
     * @param string[] $notcontains Substrings that must not appear in the output.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('react_output_provider')]
    public function test_react_output(string $input, array $contains, array $notcontains): void {
        $output = $this->helper->react($input, $this->lambdahelper);
        foreach ($contains as $str) {
            $this->assertStringContainsString($str, $output);
        }
        foreach ($notcontains as $str) {
            $this->assertStringNotContainsString($str, $output);
        }
    }

    /**
     * Test that empty input returns an empty string.
     */
    public function test_empty_input(): void {
        $output = $this->helper->react('', $this->lambdahelper);
        $this->assertSame('', $output);
    }

    /**
     * Data provider for test_invalid_json.
     *
     * @return array[]
     */
    public static function invalid_json_provider(): array {
        return [
            'invalid JSON with content falls back to plain div' => [
                '{invalid json}<p>Content</p>',
                '<div><p>Content</p></div>',
            ],
            'invalid JSON without content returns empty string' => [
                '{invalid json}',
                '',
            ],
        ];
    }

    /**
     * Test that invalid JSON triggers a debugging notice and returns the expected fallback.
     *
     * @param string $input Malformed JSON input.
     * @param string $expected Expected output string.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalid_json_provider')]
    public function test_invalid_json(string $input, string $expected): void {
        $output = $this->helper->react($input, $this->lambdahelper);
        $this->assertSame($expected, $output);
        $this->assertDebuggingCalled();
    }
}
