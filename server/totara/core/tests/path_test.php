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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use totara_core\path;

defined('MOODLE_INTERNAL') || die();

class totara_core_path_testcase extends advanced_testcase {
    /** @var ReflectionProperty */
    private $prop_path;

    /** @var ReflectionProperty */
    private $prop_windows;

    /** @var ReflectionMethod */
    private $method_slashify;

    /** @var ReflectionMethod */
    private $method_unslashify;

    public function setUp(): void {
        $this->prop_path = new ReflectionProperty(path::class, 'path');
        $this->prop_windows = new ReflectionProperty(path::class, 'is_windows_flag');
        $this->method_slashify = new ReflectionMethod(path::class, 'slashify');
        $this->method_unslashify = new ReflectionMethod(path::class, 'unslashify');
        $this->prop_path->setAccessible(true);
        $this->prop_windows->setAccessible(true);
        $this->method_slashify->setAccessible(true);
        $this->method_unslashify->setAccessible(true);
        if (DIRECTORY_SEPARATOR !== '/' && DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('unsupported directory separator');
        }
        parent::setUp();
    }

    public function tearDown(): void {
        $this->prop_path = $this->prop_windows = null;
        $this->method_slashify = $this->method_unslashify = null;
        parent::tearDown();
    }

    /**
     * Assert that $path->path is identical to $expected.
     *
     * @param string $expected
     * @param path $path
     */
    private function assert_path(string $expected, path $path) {
        $this->assertSame($expected, $this->prop_path->getValue($path));
    }

    /**
     * Test as Windows or not. This function does not override the DIRECTORY_SEPARATOR.
     *
     * @param boolean $is_windows
     */
    private function set_is_windows(bool $is_windows) {
        $this->prop_windows->setValue($is_windows);
    }

    public function test_construct() {
        global $CFG;
        $this->set_is_windows(false);
        $this->assert_path('/kia/ora', new path('/kia/ora'));
        $this->assert_path('/kia/ora', new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assert_path('/kia/ora', new path('//kia///ora/'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia/ora', 'koutou', 'katoa'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia/ora', '//', 'koutou/', '/katoa/'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia' . DIRECTORY_SEPARATOR . 'ora', DIRECTORY_SEPARATOR . 'koutou' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'katoa'));
        $this->assert_path('/kia/ora/koutou/katoa', new path(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'kia/' . 'ora', DIRECTORY_SEPARATOR . 'koutou' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'katoa'));
        $this->assert_path(str_replace(DIRECTORY_SEPARATOR, '/', $CFG->dirroot) . '/config.php', new path($CFG->dirroot, 'config.php'));
        $this->set_is_windows(true);
        $this->assert_path('/kia/ora', new path('/kia/ora'));
        $this->assert_path('/kia/ora', new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assert_path('//kia/ora', new path('//kia///ora/'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia/ora', 'koutou', 'katoa'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia/ora', '//', 'koutou/', '/katoa/'));
        $this->assert_path('/kia/ora/koutou/katoa', new path('/kia' . DIRECTORY_SEPARATOR . 'ora', DIRECTORY_SEPARATOR . 'koutou' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'katoa'));
        $this->assert_path('//kia/ora/koutou/katoa', new path(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'kia/' . 'ora', DIRECTORY_SEPARATOR . 'koutou' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'katoa'));
        $this->assert_path(str_replace(DIRECTORY_SEPARATOR, '/', $CFG->dirroot) . '/config.php', new path($CFG->dirroot, 'config.php'));
    }

    public function test_join() {
        $this->set_is_windows(false);
        $this->assert_path('/kia/ora', (new path('/kia'))->join('ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia'))->join('ora', 'koutou/', '/katoa'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia'))->join('ora', 'koutou', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'katoa' . DIRECTORY_SEPARATOR));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/'))->join('kia/')->join('/ora')->join('///')->join('koutou/katoa'));
        $this->set_is_windows(true);
        $this->assert_path('/kia/ora', (new path('/kia'))->join('ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia'))->join('ora', 'koutou/', '/katoa'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia'))->join('ora', 'koutou', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'katoa' . DIRECTORY_SEPARATOR));
        $this->assert_path('/kia/ora/koutou/katoa', (new path(DIRECTORY_SEPARATOR))->join('kia/')->join(DIRECTORY_SEPARATOR . 'ora')->join('/' . DIRECTORY_SEPARATOR . '/')->join('koutou/katoa'));
    }

    public function test_compare_to() {
        $this->set_is_windows(false);
        $this->assertSame(0, (new path('/kia/ora'))->compare_to(new path('/kia', 'ora')));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia/ora/'));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia//ora'));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assertSame(1, (new path('/kia/ora'))->compare_to('/aroha'));
        $this->assertSame(-1, (new path('/kia/ora'))->compare_to('/kia/ora/koutou'));
        $this->assertSame(1, (new path('/kia/ora'))->compare_to('/KIA/ORA'));
        $this->assertSame(1, (new path('/kia/ora'))->compare_to('/KIA/ORA/KOUTOU'));
        $this->set_is_windows(true);
        $this->assertSame(0, (new path('/kia/ora'))->compare_to(new path('/kia', 'ora')));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia/ora/'));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia//ora'));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assertSame(1, (new path('/kia/ora'))->compare_to('/aroha'));
        $this->assertSame(-1, (new path('/kia/ora'))->compare_to('/kia/ora/koutou'));
        $this->assertSame(0, (new path('/kia/ora'))->compare_to('/KIA/ORA'));
        $this->assertSame(-1, (new path('/kia/ora'))->compare_to('/KIA/ORA/KOUTOU'));
    }

    public function test_equals() {
        $this->set_is_windows(false);
        $this->assertTrue((new path('/kia/ora'))->equals(new path('/kia', 'ora')));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia/ora/'));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia//ora'));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assertFalse((new path('/kia/ora'))->equals('/aroha'));
        $this->assertFalse((new path('/kia/ora'))->equals('/kia/ora/koutou'));
        $this->assertFalse((new path('/kia/ora'))->equals('/KIA/ORA'));
        $this->assertFalse((new path('/kia/ora'))->equals('/KIA/ORA/KOUTOU'));
        $this->set_is_windows(true);
        $this->assertTrue((new path('/kia/ora'))->equals(new path('/kia', 'ora')));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia/ora/'));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia//ora'));
        $this->assertTrue((new path('/kia/ora'))->equals('/kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assertFalse((new path('/kia/ora'))->equals('/aroha'));
        $this->assertFalse((new path('/kia/ora'))->equals('/kia/ora/koutou'));
        $this->assertTrue((new path('/kia/ora'))->equals('/KIA/ORA'));
        $this->assertFalse((new path('/kia/ora'))->equals('/KIA/ORA/KOUTOU'));
    }

    public function test_canonicalise() {
        global $CFG;
        $dirroot = $this->method_slashify->invoke(null, $CFG->dirroot);
        $this->assert_path($dirroot . '/totara/core/tests/path_test.php', (new path($CFG->dirroot . '/mod/../totara/appraisal/././db/../../core/tests/path_test.php'))->canonicalise());
        $this->assert_path($dirroot . '/totara/core/tests/path_test.php', (new path($CFG->dirroot . '/mod/..'. DIRECTORY_SEPARATOR . 'totara/appraisal/db/..' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . '../core/tests/path_test.php'))->canonicalise());
    }

    public function test_out() {
        $this->assertSame('/kia/ora', (new path('/kia/ora'))->out());
        $this->assertSame(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora', (new path('/kia/ora'))->out(true));
    }

    public function test_to_string() {
        $this->assertSame('/kia/ora', (new path('/kia/ora'))->to_string());
        $this->assertSame('/kia/ora', (new path('/kia/ora/'))->to_string());
        $this->assertSame('/kia/ora', (new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'))->to_string());
        $this->assertSame('/kia/ora', (new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora' . DIRECTORY_SEPARATOR))->to_string());
    }

    public function test_to_native_string() {
        $this->assertSame(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora', (new path('/kia/ora'))->to_native_string());
        $this->assertSame(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora', (new path('/kia/ora/'))->to_native_string());
        $this->assertSame(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora', (new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'))->to_native_string());
        $this->assertSame(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora', (new path(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora' . DIRECTORY_SEPARATOR))->to_native_string());
    }

    public function test_get_parent() {
        $this->assert_path('/kia', ((new path('/kia/ora'))->get_parent()));
        $this->assert_path('/kia', ((new path('/kia/ora/'))->get_parent()));
        $this->assertNull(((new path('kia ora'))->get_parent()));
    }

    public function test_get_name() {
        $this->assertSame('koutou', ((new path('/kia/ora/koutou'))->get_name()));
        $this->assertSame('koutou', ((new path('/kia/ora/koutou/'))->get_name()));
        $this->assertSame('kia', ((new path('/kia'))->get_name()));
    }

    public function test_get_extension() {
        $this->set_is_windows(false);
        $this->assertSame('.php', ((new path('/kia/ora/koutou.php'))->get_extension()));
        $this->assertSame('', ((new path('/kia/ora.txt/koutou'))->get_extension()));
        $this->assertSame('.koutou katoa', ((new path('/kia/ora.koutou katoa'))->get_extension()));
        $this->set_is_windows(true);
        $this->assertSame('.php', ((new path('/kia/ora/koutou.php'))->get_extension()));
        $this->assertSame('', ((new path('/kia/ora.txt/koutou'))->get_extension()));
        $this->assertSame('', ((new path('/kia/ora.koutou katoa'))->get_extension()));
    }

    public function test_is_absolute() {
        $this->set_is_windows(false);
        $this->assertTrue((new path('/kia/ora'))->is_absolute());
        $this->assertTrue((new path('//kia/ora'))->is_absolute());
        $this->assertFalse((new path('kia/ora'))->is_absolute());
        $this->assertFalse((new path('c:/kia/ora'))->is_absolute());
        $this->assertFalse((new path('C:' . DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'))->is_absolute());
        $this->set_is_windows(true);
        $this->assertTrue((new path('/kia/ora'))->is_absolute());
        $this->assertTrue((new path('//kia/ora'))->is_absolute());
        $this->assertFalse((new path('kia/ora'))->is_absolute());
        $this->assertTrue((new path('C:' . DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'))->is_absolute());
    }

    public function test_get_relative() {
        $this->set_is_windows(false);
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/ora'));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/ora/'));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('kia/ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/kaha'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/KIA/ORA'));
        $this->assertNull((new path('/kia/ora/koutou/katoa'))->get_relative('kia/ora', true));
        $this->assertNull((new path('/kia/ora/koutou/katoa'))->get_relative('kia/kaha', true));
        $this->assertNull((new path('/kia/ora/koutou/katoa'))->get_relative('/KIA/ORA', true));
        $this->set_is_windows(true);
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/ora'));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/ora/'));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative(DIRECTORY_SEPARATOR . 'kia' . DIRECTORY_SEPARATOR . 'ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('kia/ora'));
        $this->assert_path('/kia/ora/koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/kia/kaha'));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/KIA/ORA'));
        $this->assertNull((new path('/kia/ora/koutou/katoa'))->get_relative('kia/ora', true));
        $this->assertNull((new path('/kia/ora/koutou/katoa'))->get_relative('kia/kaha', true));
        $this->assert_path('koutou/katoa', (new path('/kia/ora/koutou/katoa'))->get_relative('/KIA/ORA', true));
    }

    public function test_exists() {
        global $CFG;
        clearstatcache();
        $this->assertTrue((new path($CFG->dirroot, '/totara/core/tests/path_test.php'))->exists());
        $this->assertTrue((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests', 'path_test.php'])))->exists());
        $this->assertFalse((new path($CFG->dirroot, '/totara/core/tests/he_who_must_not_exist.php'))->exists());
        $this->assertFalse((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests', 'he_who_must_not_exist.php'])))->exists());
    }

    public function test_is_directory() {
        global $CFG;
        clearstatcache();
        $this->assertTrue((new path($CFG->dirroot, '/totara/core/tests'))->is_directory());
        $this->assertTrue((new path($CFG->dirroot, 'totara/core/tests/'))->is_directory());
        $this->assertTrue((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests'])))->is_directory());
        $this->assertTrue((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests', ''])))->is_directory());
        $this->assertFalse((new path($CFG->dirroot, '/totara/core/tests/he_who_must_not_exist'))->is_directory());
        $this->assertFalse((new path($CFG->dirroot, 'totara/core/tests/he_who_must_not_exist/'))->is_directory());
        $this->assertFalse((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests', 'he_who_must_not_exist'])))->is_directory());
        $this->assertFalse((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['totara', 'core', 'tests', 'he_who_must_not_exist', ''])))->is_directory());
    }

    public function test_is_file() {
        global $CFG;
        clearstatcache();
        $this->assertTrue((new path($CFG->dirroot, '/totara/core/tests/path_test.php'))->is_file());
        $this->assertTrue((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['', 'totara', 'core', 'tests', 'path_test.php'])))->is_file());
        $this->assertFalse((new path($CFG->dirroot, '/totara/core/tests'))->is_file());
        $this->assertFalse((new path($CFG->dirroot, '/totara/core/tests/'))->is_file());
        $this->assertFalse((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['', 'totara', 'core', 'tests'])))->is_file());
        $this->assertFalse((new path($CFG->dirroot, join(DIRECTORY_SEPARATOR, ['', 'totara', 'core', 'tests', ''])))->is_file());
    }

    public function test_is_readable() {
        global $CFG;
        clearstatcache();
        $this->assertTrue((new path($CFG->dirroot, '/totara/core/tests/path_test.php'))->is_readable());
        $this->assertFalse((new path($CFG->dirroot, '/totara/core/tests/i_do_not_exist.php'))->is_readable());
    }

    public function test_create_directory_iterator() {
        global $CFG;
        $iterator = (new path($CFG->dirroot, 'totara/core/tests'))->create_directory_iterator();
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'path_test.php') {
                return;
            }
        }
        $this->fail('path_test.php was not found');
    }

    public function test_create_recursive_directory_iterator() {
        global $CFG;
        $iterator = (new path($CFG->dirroot, 'totara/core/tests'))->create_recursive_directory_iterator();
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'path_test.php') {
                return;
            }
        }
        $this->fail('path_test.php was not found');
    }

    public function test_slashify() {
        $this->assertSame('kia/ora/koutou/katoa.php', $this->method_slashify->invoke(null, 'kia/ora/koutou/katoa.php'));
        $this->assertSame('kia/ora/koutou/katoa.php', $this->method_slashify->invoke(null, join(DIRECTORY_SEPARATOR, ['kia', 'ora', 'koutou', 'katoa.php'])));
    }

    public function test_unslashify() {
        $this->assertSame(join(DIRECTORY_SEPARATOR, ['kia', 'ora', 'koutou', 'katoa.php']), $this->method_unslashify->invoke(null, 'kia/ora/koutou/katoa.php'));
        $this->assertSame(join(DIRECTORY_SEPARATOR, ['kia', 'ora', 'koutou', 'katoa.php']), $this->method_unslashify->invoke(null, join(DIRECTORY_SEPARATOR, ['kia', 'ora', 'koutou', 'katoa.php'])));
    }

    public function test_export_for_env() {
        if (DIRECTORY_SEPARATOR === '/') {
            $this->assertSame('a:bc:def', path::export('a', 'bc', 'def'));
        } else {
            $this->assertSame('a;bc;def', path::export('a', 'bc', 'def'));
        }
    }
}
