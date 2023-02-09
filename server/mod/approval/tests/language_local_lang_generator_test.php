<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\language_pack_faker_trait;
use core_phpunit\testcase;
use mod_approval\language\local_lang_generator;
use mod_approval\language\substitutor;

global $CFG;

/**
 * @coversDefaultClass mod_approval\language\local_lang_generator
 * @group approval_workflow
 */
class mod_approval_language_local_lang_generator_testcase extends testcase {
    use language_pack_faker_trait;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     */
    public function test_constructor(): void {
        $prop = new ReflectionProperty(local_lang_generator::class, 'lang');
        $prop->setAccessible(true);
        $this->add_fake_language_pack('xo_ox', []);
        $this->setAdminUser();

        $mocking = function (string $lang) {
            $mock = $this->getMockBuilder(local_lang_generator::class)
                ->disableOriginalConstructor()
                ->getMock();
            $ctor = (new ReflectionClass(local_lang_generator::class))->getConstructor();
            $ctor->invoke($mock, $lang);
            return $mock;
        };

        force_current_language('xo_ox');
        $mock = $mocking('');
        $this->assertEquals('xo_ox', $prop->getValue($mock));

        force_current_language('en');
        $mock = $mocking('');
        $this->assertEquals('en', $prop->getValue($mock));
        $mock = $mocking('xo_ox');
        $this->assertEquals('xo_ox', $prop->getValue($mock));
    }

    /**
     * @covers ::get_local_path
     */
    public function test_get_local_path(): void {
        global $CFG;
        $method = new ReflectionMethod(local_lang_generator::class, 'get_local_path');
        $method->setAccessible(true);
        $this->add_fake_language_pack('xo_ox', []);

        $mocking = function (string $component) {
            $mock = $this->getMockBuilder(local_lang_generator::class)
                ->disableOriginalConstructor()
                ->getMock();
            $ctor = (new ReflectionClass(local_lang_generator::class))->getConstructor();
            $ctor->invoke($mock, 'xo_ox');
            $mock->method('get_component')->willReturn($component);
            return $mock;
        };

        $mock = $mocking('mod_approval');
        $expected = "{$CFG->langlocalroot}/xo_ox_local/approval.php";
        $result = $method->invoke($mock);
        $this->assertEquals($expected, $result);

        $mock = $mocking('approvalform_simple');
        $expected = "{$CFG->langlocalroot}/xo_ox_local/approvalform_simple.php";
        $result = $method->invoke($mock);
        $this->assertEquals($expected, $result);

        $mock = $mocking('rb_source_approvalform_simple');
        $expected = "{$CFG->langlocalroot}/xo_ox_local/rb_source_approvalform_simple.php";
        $result = $method->invoke($mock);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::generate_strings
     */
    public function test_generate_strings(): void {
        $method = new ReflectionMethod(local_lang_generator::class, 'generate_strings');
        $method->setAccessible(true);

        $substitutor = $this->getMockBuilder(substitutor::class)->getMock();
        $substitutor->method('substitute_string')->willReturnArgument(0);

        $mock = $this->getMockBuilder(local_lang_generator::class)->getMock();
        $mock->method('get_strings')->willReturn(['b' => 'Second', 'a' => 'First', 'c' => 'Third']);
        $mock->method('get_substitutor')->willReturn($substitutor);

        $input = ['a' => '?'];
        $expected = ['a' => 'First', 'b' => 'Second', 'c' => 'Third'];
        $result = $method->invoke($mock, $input);
        $this->assertEquals(array_keys($expected), array_keys($result));
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::get_substituted_strings
     */
    public function test_get_substituted_strings(): void {
        $method = new ReflectionMethod(local_lang_generator::class, 'get_substituted_strings');
        $method->setAccessible(true);

        $substitutor = $this->getMockBuilder(substitutor::class)->getMock();
        $substitutor->method('substitute_string')->willReturnCallback(function (string $string): string {
            if ($string == 'Third') {
                return 'New?';
            } else {
                return $string;
            }
        });

        $mock = $this->getMockBuilder(local_lang_generator::class)->getMock();
        $mock->method('get_substitutor')->willReturn($substitutor);

        $input = ['b' => 'Second', 'a' => 'First', 'c' => 'Third'];
        $expected = ['c' => 'New?'];
        $result = $method->invoke($mock, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers ::serialise_strings
     */
    public function test_serialise_strings(): void {
        $method = new ReflectionMethod(local_lang_generator::class, 'serialise_strings');
        $method->setAccessible(true);

        $mock = $this->getMockBuilder(local_lang_generator::class)->getMock();
        $mock->method('get_component')->willReturn('container_approval');

        $input = ['a' => 'First', 'b' => 'Second'];
        $expected = "<?php\n/**\n * @package    container\n * @subpackage approval\n * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later\n */\n\$string['a'] = 'First';\n\$string['b'] = 'Second';\n";
        $this->assertEquals($expected, $method->invoke($mock, $input));
    }
}
