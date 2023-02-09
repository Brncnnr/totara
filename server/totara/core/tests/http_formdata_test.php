<?php
/**
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
 * @package totara_msteams
 */

use totara_core\http\formdata;

class totara_core_http_formdata_testcase extends advanced_testcase {
    /** @var formdata */
    private $formdata;

    /** @var ReflectionProperty */
    private $prop;

    public function setUp(): void {
        $this->formdata = new formdata();
        $this->prop = new ReflectionProperty($this->formdata, 'data');
        $this->prop->setAccessible(true);
    }

    public function tearDown(): void {
        $this->formdata = null;
        $this->prop = null;
    }

    public function data_constructor(): array {
        return [
            [[], []],
            [['kia' => 'ora', 'kou' => 'tou'], ['kia' => 'ora', 'kou' => 'tou']],
            [['x' => [3, '1'], 'y' => 4], ['x' => ['3', '1'], 'y' => '4']],
        ];
    }

    /**
     * @dataProvider data_constructor
     */
    public function test_constructor($value, $expected) {
        $formdata = new formdata($value);
        $result = $this->prop->getValue($formdata);
        $this->assertEquals($expected, $result);
    }

    public function data_set(): array {
        return [
            ['kia ora', 'kia ora'],
            ['42', '42'],
            [42, '42'],
            [['kia', 'ora'], ['kia', 'ora']],
            [['3', '1', '4'], ['3', '1', '4']],
            [[3, 1, 4], ['3', '1', '4']],
        ];
    }

    /**
     * @dataProvider data_set
     */
    public function test_set($value, $expected) {
        $this->formdata->set('test', $value);
        $result = $this->prop->getValue($this->formdata);
        $this->assertEquals(['test' => $expected], $result);
    }

    public function test_delete() {
        $this->formdata->set('kia', 'ora');
        $result = $this->prop->getValue($this->formdata);
        $this->assertEquals(['kia' => 'ora'], $result);
        $this->formdata->delete('kia');
        $result = $this->prop->getValue($this->formdata);
        $this->assertEquals([], $result);
    }

    public function data_as_string_1(): array {
        return [
            ['Kia ora', 'test=Kia+ora'],
            [42, 'test=42'],
            [['kia', 'ora'], 'test[]=kia&test[]=ora'],
            [['3', '1', '4'], 'test[]=3&test[]=1&test[]=4'],
        ];
    }

    /**
     * @dataProvider data_as_string_1
     */
    public function test_as_string_1($value, string $expected) {
        $this->formdata->set('test', $value);
        $result = $this->formdata->as_string();
        $this->assertEquals($expected, $result);
    }

    public function data_as_string_2(): array {
        return [
            ['Kia ora', '#koutou', 'foo=Kia+ora&bar=%23koutou'],
        ];
    }

    /**
     * @dataProvider data_as_string_2
     */
    public function test_as_string_2($value1, $value2, string $expected) {
        $this->formdata->set('foo', $value1);
        $this->formdata->set('bar', $value2);
        $result = $this->formdata->as_string();
        $this->assertEquals($expected, $result);
    }
}
