<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core
 */

use core\format;
use core\webapi\formatter\field\textarea_field_formatter;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_textarea_field_formatter_testcase extends testcase {

    /**
     * Test data provider.
     *
     * @return array[test_label, value, html_value, plain_value]
     */
    public function test_textara_formatter_data_provider(): array {
        return [
            [
                'Requirements - multilang-filtered, encoded, whitespace preserved.',
                "This > <h1>Is > <span class=\"multilang\" lang=\"en\">Health and Safety</span><span class=\"multilang\" lang=\"de\"> Terveys ja turvallisuus</span>\n  And\n    It is\n      Also\n  Poetry.",
                "This &#62; &#60;h1&#62;Is &#62; Health and Safety<br />\n&nbsp; And<br />\n&nbsp; &nbsp; It is<br />\n&nbsp; &nbsp; &nbsp; Also<br />\n&nbsp; Poetry.",
                "This > <h1>Is > Health and Safety\n  And\n    It is\n      Also\n  Poetry.",
            ],
            [
                'Encoded character in multilang value',
                "This > <h1>Is > <span class=\"multilang\" lang=\"en\">Health & Safety</span><span class=\"multilang\" lang=\"de\"> Terveys ja turvallisuus</span>\n  &\n    It is\n      Also\n  Poetry.",
                "This &#62; &#60;h1&#62;Is &#62; Health &#38; Safety<br />\n&nbsp; &#38;<br />\n&nbsp; &nbsp; It is<br />\n&nbsp; &nbsp; &nbsp; Also<br />\n&nbsp; Poetry.",
                "This > <h1>Is > Health & Safety\n  &\n    It is\n      Also\n  Poetry.",
            ],
            [
                'Simple',
                "The quick brown fox jumped over the lazy dog.",
                "The quick brown fox jumped over the lazy dog.",
                "The quick brown fox jumped over the lazy dog.",
            ],
            [
                'Unicode',
                "The quick brown лисиця jumped over the lazy пес.",
                "The quick brown лисиця jumped over the lazy пес.",
                "The quick brown лисиця jumped over the lazy пес.",
            ],
        ];
    }

    /**
     * Test formatter with multilang filtering enabled.
     *
     * @param string $test_label
     * @param string $value
     * @param string $html_value
     * @param string $plain_value
     * @dataProvider test_textara_formatter_data_provider
     */
    public function test_textarea_formatter_with_multilang_filter(string $test_label, string $value, string $html_value, string $plain_value) {
        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        $context = context_system::instance();

        // Test HTML
        $html_formatter = new textarea_field_formatter(format::FORMAT_HTML, $context);
        $this->assertEquals($html_value, $html_formatter->format($value), "{$test_label} test HTML format regression");

        // Test PLAIN
        $plain_formatter = new textarea_field_formatter(format::FORMAT_PLAIN, $context);
        $result = $plain_formatter->format($value);
        $this->assertEquals($plain_value, $plain_formatter->format($value), "{$test_label} test PLAIN format regression");

        // Test RAW
        $raw_formatter = new textarea_field_formatter(format::FORMAT_RAW, $context);
        $this->assertEquals($value, $raw_formatter->format($value), "{$test_label} test RAW format regression");
    }

    public function test_html_format() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_HTML, context_system::instance());

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_string($value, false, ['context' => $context]);

        // format_string() should have been applied
        $this->assertEquals($result, $value);
        // Tags are not stripped
        $this->assertMatchesRegularExpression("/span class=/", $result);
    }

    public function test_html_format_with_stripping_tags() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_HTML, context_system::instance());
        $formatter->set_strip_tags(true);

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_string($value, true, ['context' => $context]);

        // format_string() should have been applied
        $this->assertEquals($result, $value);
        // Tags are still there
        $this->assertDoesNotMatchRegularExpression("/span class=/", $result);
    }

    public function test_html_format_with_additional_options() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_HTML, context_system::instance());
        $formatter->set_strip_tags(true);
        // Escape option acts as an inverse strip_tags, so escape = true means strip_tags = false
        $formatter->set_additional_options(['escape' => true]);

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_string($value, false, ['context' => $context]);

        // format_string() should have been applied
        $this->assertEquals($result, $value);
        // Tags are not stripped
        $this->assertMatchesRegularExpression("/span class=/", $result);
    }

    public function test_html_format_with_multi_lang_strings() {
        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_HTML, $context);

        $value = '<span lang="en" class="multilang">Summer</span><span lang="de" class="multilang">Sommer</span>';
        $result = $formatter->format($value);

        $this->assertEquals('Summer', $result);
    }

    public function test_plain_format() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $expected = '<span class="myhtml">_)(*&^%$#test</span>';
        $value = format_string($value, false, ['context' => $context]);

        // We should have plain text now
        $this->assertNotEquals($result, $value);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_long_lines() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">Ko te Kuini o Ingarani ka wakarite ka wakaae ki nga Rangatira ki nga hapu – ki nga tangata katoa o Nu Tirani te tino rangatiratanga o o ratou wenua o ratou kainga me o ratou taonga katoa</span>';
        $result = $formatter->format($value);

        $expected = '<span class="myhtml">Ko te Kuini o Ingarani ka wakarite ka wakaae ki nga Rangatira ki nga hapu – ki nga tangata katoa o Nu Tirani te tino rangatiratanga o o ratou wenua o ratou kainga me o ratou taonga katoa</span>';

        // html_to_text() will have inserted linebreaks.
        $this->assertNotEquals(html_to_text($value), $result);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_links() {
        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">KO <a href="https://en.wikipedia.org/wiki/Queen_Victoria">WIKITORIA</a> te Kuini o Ingarani</span>';
        $result = $formatter->format($value);

        $expected = '<span class="myhtml">KO <a href="https://en.wikipedia.org/wiki/Queen_Victoria">WIKITORIA</a> te Kuini o Ingarani</span>';

        // html_to_text() will have made the URL a footnote.
        $this->assertNotEquals(html_to_text($value), $result);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_multi_lang_strings() {
        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        $context = context_system::instance();
        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span lang="en" class="multilang">Summer</span><span lang="de" class="multilang">Sommer</span>';
        $result = $formatter->format($value);

        $this->assertEquals('Summer', $result);
    }

    public function test_plain_special_chars_are_not_encoded() {
        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, context_system::instance());

        $value = '';
        for ($i = 33; $i < 255; $i++) {
            // Skip < > as strip tags will likely strip them out
            if ($i == 60 || $i == 62) {
                continue;
            }
            $value .= utf8_encode(chr($i));
        }
        $value = trim($value);

        $result = $formatter->format($value);

        // No character should be encoded
        $this->assertEquals($value, $result);

        $value = "This is a special text &apos;with encoded characters Foo &amp; Special character&quot;s";
        $expected = 'This is a special text \'with encoded characters Foo & Special character"s';
        $result = $formatter->format($value);
        $this->assertEquals($expected, $result);
    }

    public function test_raw_format() {
        $formatter = new textarea_field_formatter(format::FORMAT_RAW, context_system::instance());

        $value = '<span class="myhtml">Foo &amp; Special \' <script></script>character&quot;s</span>';

        $result = $formatter->format($value);

        // Nothing should have changed
        $this->assertEquals($result, $value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_markdown_format() {
        $formatter = new textarea_field_formatter(format::FORMAT_MARKDOWN, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('MARKDOWN format is currently not supported by the textarea formatter.');

        $formatter->format($value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_json_editor_format() {
        $formatter = new textarea_field_formatter(format::FORMAT_JSON_EDITOR, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('JSON_EDITOR format is currently not supported by the textarea formatter.');

        $formatter->format($value);
    }

    /**
     * Test the exception given by invalid formats
     */
    public function test_unknown_format() {
        $formatter = new textarea_field_formatter('foo', context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format($value);
    }

    public function test_null_value() {
        $formatter = new textarea_field_formatter(format::FORMAT_HTML, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new textarea_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new textarea_field_formatter(format::FORMAT_RAW, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);
    }

}
