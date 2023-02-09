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
use core\webapi\formatter\field\text_field_formatter;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_text_field_formatter_testcase extends advanced_testcase {

    public function test_html_format() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_text($value, FORMAT_HTML, ['context' => $context]);

        // format_text() should have been applied
        $this->assertEquals($result, $value);
    }

    public function test_html_format_replace_urls() {
        global $CFG;

        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">@@PLUGINFILE@@/</span>';

        $result = $formatter->format($value);

        $url = "{$CFG->wwwroot}/file.php/{$context->id}/component/filearea/1/";

        // url should have been replaced
        // Tags should be there
        $this->assertMatchesRegularExpression("/<span class/", $result);
        $this->assertMatchesRegularExpression('/'.preg_quote($url, '/').'/', $result);
        $this->assertDoesNotMatchRegularExpression("/@@PLUGINFILE@@\\//", $result);

        // set additional pluginfile url rewrite options
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php', ['reverse' => true]);
        $result = $formatter->format($result);
        $this->assertDebuggingCalled('Before calling format_text(), the content must be processed with file_rewrite_pluginfile_urls()');
        $this->assertMatchesRegularExpression("/@@PLUGINFILE@@\\//", $result);
    }

    public function test_html_format_replace_urls_without_item_and_pluginfile() {
        global $CFG;

        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea');

        $value = '<span class="myhtml">@@PLUGINFILE@@/</span>';

        $result = $formatter->format($value);

        $url = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/component/filearea/";

        // url should have been replaced
        // Tags should be there
        $this->assertMatchesRegularExpression("/<span class/", $result);
        $this->assertMatchesRegularExpression('/'.preg_quote($url, '/').'/', $result);
        $this->assertDoesNotMatchRegularExpression("/@@PLUGINFILE@@\\//", $result);
    }

    public function test_html_format_different_text_format() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');
        $formatter->set_text_format(FORMAT_PLAIN);

        $value = '<span class="myhtml">@@PLUGINFILE@@/</span>';

        $result = $formatter->format($value);

        // Should be plain now, special characters are encoded
        $this->assertMatchesRegularExpression("/&lt;span class=&quot;/", $result);
    }

    public function test_html_format_with_additional_options() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');
        $formatter->set_additional_options(['overflowdiv' => true]);

        $value = '<span class="myhtml">@@PLUGINFILE@@/</span>';

        $result = $formatter->format($value);

        // We expect the string wrapped in a div as we've passed the option above
        $this->assertMatchesRegularExpression("/<div class=\"no\-overflow\"><span class=\"/", $result);
    }

    public function test_plain_format() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_PLAIN, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">_)(*&^%$#test</span>';

        $result = $formatter->format($value);

        $value = format_text($value, FORMAT_HTML, ['context' => $context]);

        // We should have plain text now
        $this->assertNotEquals($result, $value);
        $this->assertEquals('_)(*&^%$#test', $result);
    }

    public function test_plain_format_with_long_lines() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_PLAIN, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani</span>';
        $result = $formatter->format($value);

        $value = format_text($value, FORMAT_HTML, ['context' => $context]);
        $expected = 'KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani';

        // We should have plain text now.
        $this->assertNotEquals($result, $value);
        // html_to_text() will have inserted linebreaks.
        $this->assertNotEquals(html_to_text($value), $result);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_br() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_PLAIN, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">KO WIKITORIA te Kuini o Ingarani i tana<br>mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani</span>';
        $result = $formatter->format($value);

        $expected = "KO WIKITORIA te Kuini o Ingarani i tana\nmahara atawai ki nga Rangatira me nga Hapu o Nu Tirani";
        $this->assertNotEquals($result, $value);
        $this->assertEquals($expected, $result);
    }

    public function test_plain_format_with_links() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_PLAIN, $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">KO <a href="https://en.wikipedia.org/wiki/Queen_Victoria">WIKITORIA</a> te Kuini o Ingarani</span>';
        $result = $formatter->format($value);

        $value = format_text($value, FORMAT_HTML, ['context' => $context]);
        $expected = 'KO WIKITORIA [https://en.wikipedia.org/wiki/Queen_Victoria] te Kuini o Ingarani';

        // We should have plain text now.
        $this->assertNotEquals($result, $value);
        // html_to_text() will have made the URL a footnote.
        $this->assertNotEquals(html_to_text($value), $result);
        $this->assertEquals($expected, $result);
    }

    public function test_raw_format() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_RAW, $context);

        $value = '<span class="myhtml">@@PLUGINFILE@@/</span>';

        $result = $formatter->format($value);

        // Nothing should have changed
        $this->assertEquals($result, $value);
    }

    public function test_missing_pluginfile_options() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You must provide the pluginfile url options via set_pluginfile_url_options()');

        $formatter->format($value);
    }

    public function test_toggle_pluginfile_url_rewrite() {
        $context = context_system::instance();
        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->disabled_pluginfile_url_rewrite();

        $value = '<span class="myhtml">test</span>';

        $result = $formatter->format($value);
        $this->assertMatchesRegularExpression("/<span class/", $result);

        $formatter->enable_pluginfile_url_rewrite();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You must provide the pluginfile url options via set_pluginfile_url_options()');

        $formatter->format($value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_markdown_format() {
        $formatter = new text_field_formatter(format::FORMAT_MARKDOWN, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('MARKDOWN format is currently not supported by the text formatter.');

        $formatter->format($value);
    }

    /**
     * Test the exception given by unsuported formats.
     */
    public function test_json_editor_format() {
        $formatter = new text_field_formatter(format::FORMAT_JSON_EDITOR, context_system::instance());

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('JSON_EDITOR format is currently not supported by the text formatter.');

        $formatter->format($value);
    }

    /**
     * Test format_mobile with valid JSON doc
     */
    public function test_mobile_format_with_json() {
        $formatter = new text_field_formatter(format::FORMAT_MOBILE, context_system::instance());
        $formatter->disabled_pluginfile_url_rewrite();

        $value = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"this is a <test>"}]}]}';
        $result = $formatter->format($value);

        // Nothing should have changed
        $this->assertEquals($result, $value);
    }

    /**
     * Test format_mobile with invalid JSON doc
     */
    public function test_mobile_format_with_invalid_json() {
        $formatter = new text_field_formatter(format::FORMAT_MOBILE, context_system::instance());
        $formatter->disabled_pluginfile_url_rewrite();

        $value = '{"type":"<super>","content":"<values!>"}';
        $result = $formatter->format($value);

        // Tags are stripped
        $expected = '{"type":"","content":""}';
        $this->assertEquals($result, $expected);
    }

    /**
     * Test format_mobile with HTML
     */
    public function test_mobile_format_with_html() {
        $formatter = new text_field_formatter(format::FORMAT_MOBILE, context_system::instance());
        $formatter->disabled_pluginfile_url_rewrite();

        $value = '<p>This <b>is</b></p><p>a test.</p>';
        $result = $formatter->format($value);

        // Tags are stripped, paragraphs to newlines, bold is uppercased
        $expected = "This IS\n\na test.\n";
        $this->assertEquals($result, $expected);
    }

    /**
     * Test format_mobile with plain text
     */
    public function test_mobile_format_with_text() {
        $formatter = new text_field_formatter(format::FORMAT_MOBILE, context_system::instance());
        $formatter->disabled_pluginfile_url_rewrite();

        $value = "The quick brown fox jumped\n   over\nthe lazy dog.";
        $result = $formatter->format($value);

        // Tags are stripped, paragraphs to newlines, bold is uppercased
        $expected = "The quick brown fox jumped over the lazy dog.";
        $this->assertEquals($result, $expected);
    }

    /**
     * Test the exception given by invalid formats
     */
    public function test_unknown_format() {
        $context = context_system::instance();
        $formatter = new text_field_formatter('foo', $context);
        $formatter->set_pluginfile_url_options($context, 'component', 'filearea', 1, 'file.php');

        $value = '<span class="myhtml">test</span>';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format($value);
    }

    public function test_null_value() {
        $formatter = new text_field_formatter(format::FORMAT_HTML, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new text_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);

        $formatter = new text_field_formatter(format::FORMAT_RAW, context_system::instance());
        $value = $formatter->format(null);
        $this->assertNull($value);
    }

}
