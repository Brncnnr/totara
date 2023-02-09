<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

use core\format;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_string_field_formatter_testcase extends advanced_testcase {

    public function test_html_format() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_string($value, true, ['context' => $context]);

        // format_string() should have been applied
        $this->assertEquals($result, $value);
        // Tags are stripped
        $this->assertDoesNotMatchRegularExpression("/span class=/", $result);
    }

    public function test_html_format_without_stripping_tags() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());
        $formatter->set_strip_tags(false);

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_string($value, false, ['context' => $context]);

        // format_string() should have been applied
        $this->assertEquals($result, $value);
        // Tags are still there
        $this->assertMatchesRegularExpression("/span class=/", $result);
    }

    public function test_html_format_with_additional_options() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());
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
        $formatter = new string_field_formatter(format::FORMAT_HTML, $context);

        $value = '<span lang="en" class="multilang">Summer</span><span lang="de" class="multilang">Sommer</span>';
        $result = $formatter->format($value);

        $this->assertEquals('Summer', $result);
    }

    public function test_plain_format() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $expected = '_)(*&^%$#test';
        $value = format_string($value, true, ['context' => $context]);

        // We should have plain text now
        $this->assertNotEquals($result, $value);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_long_lines() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani</span>';
        $result = $formatter->format($value);

        $expected = 'KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani';

        // html_to_text() will have inserted linebreaks.
        $this->assertNotEquals(html_to_text($value), $result);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_links() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span class="myhtml">KO <a href="https://en.wikipedia.org/wiki/Queen_Victoria">WIKITORIA</a> te Kuini o Ingarani</span>';
        $result = $formatter->format($value);

        $expected = 'KO WIKITORIA te Kuini o Ingarani';

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
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = '<span lang="en" class="multilang">Summer</span><span lang="de" class="multilang">Sommer</span>';
        $result = $formatter->format($value);

        $this->assertEquals('Summer', $result);
    }

    public function test_plain_special_chars_are_not_encoded() {
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());

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
        $formatter = new string_field_formatter(format::FORMAT_RAW, context_system::instance());

        $value = '<span class="myhtml">Foo &amp; Special \' <script></script>character&quot;s</span>';

        $result = $formatter->format($value);

        // Nothing should have changed
        $this->assertEquals($result, $value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_markdown_format() {
        $formatter = new string_field_formatter(format::FORMAT_MARKDOWN, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('MARKDOWN format is currently not supported by the string formatter.');

        $formatter->format($value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_json_editor_format() {
        $formatter = new string_field_formatter(format::FORMAT_JSON_EDITOR, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('JSON_EDITOR format is currently not supported by the string formatter.');

        $formatter->format($value);
    }

    /**
     * Test the exception given by invalid formats
     */
    public function test_unknown_format() {
        $formatter = new string_field_formatter('foo', context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format($value);
    }

    public function test_null_value() {
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new string_field_formatter(format::FORMAT_RAW, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);
    }

}
