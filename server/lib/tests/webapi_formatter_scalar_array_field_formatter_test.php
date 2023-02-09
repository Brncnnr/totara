<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

use core\format;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_scalar_array_field_formatter_testcase extends advanced_testcase {

    public function test_nonscalar_array_format() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());

        // Arrays of objects are not allowed.
        try {
            $value = [0 => new \stdClass(), 1 => new \stdClass()];
            $result = $formatter->format($value);
        } catch (\coding_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Invalid array values, only scalar values can be formatted', $ex->getMessage());
        }

        // Arrays of arrays are not allowed.
        try {
            $value = [0 => [0 => 'string'], 1 => [0 => 'another']];
            $result = $formatter->format($value);
        } catch (\coding_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Invalid array values, only scalar values can be formatted', $ex->getMessage());
        }
    }

    public function test_string_array_html_format() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());

        $value = ['<span class="myhtml">_)(*&^%$#test</span>', '<span class="myarray">_)(*&^%$#test2</span>'];

        $result = $formatter->format($value);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // format_string() should have been applied, and tags should be stripped.
        $res1 = array_shift($result);
        $expected = format_string($value[0], true, ['context' => $context]);
        $this->assertEquals($res1, $expected);
        $this->assertDoesNotMatchRegularExpression("/span class=/", $res1);

        $res2 = array_shift($result);
        $expected = format_string($value[1], true, ['context' => $context]);
        $this->assertEquals($res2, $expected);
        $this->assertDoesNotMatchRegularExpression("/span class=/", $res2);
    }

    public function test_string_array_html_format_without_stripping_tags() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, context_system::instance());
        $formatter->set_strip_tags(false);

        $value = ['<span class="myhtml">_)(*&^%$#test</span>', '<span class="myarray">_)(*&^%$#test2</span>'];

        $result = $formatter->format($value);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // format_string() should have been applied, and tags should be stripped.
        $res1 = array_shift($result);
        $expected = format_string($value[0], false, ['context' => $context]);
        $this->assertEquals($res1, $expected);
        $this->assertMatchesRegularExpression("/span class=/", $res1);

        $res2 = array_shift($result);
        $expected = format_string($value[1], false, ['context' => $context]);
        $this->assertEquals($res2, $expected);
        $this->assertMatchesRegularExpression("/span class=/", $res2);
    }

    public function test_string_array_html_format_with_multi_lang_strings() {
        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_HTML, $context);

        $value = [
            '<span lang="en" class="multilang">Spring</span><span lang="de" class="multilang">Frühling</span>',
            '<span lang="en" class="multilang">Summer</span><span lang="de" class="multilang">Sommer</span>',
            '<span lang="en" class="multilang">Autumn</span><span lang="de" class="multilang">Herbst</span>',
            '<span lang="en" class="multilang">Winter</span><span lang="de" class="multilang">Sommer</span>'
        ];
        $result = $formatter->format($value);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        $expected = ['Spring', 'Summer', 'Autumn', 'Winter'];
        foreach ($result as $season) {
            $this->assertContains($season, $expected);
            $this->assertDoesNotMatchRegularExpression("/span lang=/", $season);
        }
    }

    public function test_string_array_plain_format() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = ['<span class="myhtml">_)(*&^%$#test</span>', '<div class="secondary">_)(*&^%$#secondary</div>'];
        $result = $formatter->format($value);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // We should have plain text now
        $val1 = array_shift($result);
        $expected = '_)(*&^%$#test';
        $unexpected = format_string($value[0], true, ['context' => $context]);
        $this->assertNotEquals($val1, $unexpected);
        $this->assertEquals($val1, $expected);

        $val2 = array_shift($result);
        $expected = '_)(*&^%$#secondary';
        $unexpected = format_string($value[1], true, ['context' => $context]);
        $this->assertNotEquals($val2, $unexpected);
        $this->assertEquals($val2, $expected);
    }

    public function test_string_array_plain_format_with_long_lines() {
        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        $value = [
            '<span class="myhtml">KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani</span>',
            '<span class="myhtml">VICTORIA is the Queen of England when she remembers the leaders and captives of New Zealand</span>',
        ];
        $result = $formatter->format($value);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // This shouldn't match html_to_text() since it wont have inserted linebreaks.
        $val1 = array_shift($result);
        $expected = 'KO WIKITORIA te Kuini o Ingarani i tana mahara atawai ki nga Rangatira me nga Hapu o Nu Tirani';
        $this->assertNotEquals(html_to_text($value[0]), $val1);
        $this->assertEquals($expected, $val1);

        $val2 = array_shift($result);
        $expected = 'VICTORIA is the Queen of England when she remembers the leaders and captives of New Zealand';
        $this->assertNotEquals(html_to_text($value[1]), $val2);
        $this->assertEquals($expected, $val2);
    }
}
