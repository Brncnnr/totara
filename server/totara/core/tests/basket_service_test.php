<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 * @category test
 */

use totara_core\basket\session_basket;

defined('MOODLE_INTERNAL') || die();

class totara_core_basket_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_show() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_show', [
            'basket' => 'test'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    public function test_show_empty() {
        $res = $this->call_webservice_api('totara_core_basket_show', [
            'basket' => 'test123'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    public function test_update_add() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_update', [
            'basket' => 'test',
            'action' => 'add',
            'ids' => [5, 6, 7]
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $basket->load());
    }

    public function test_update_replace() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_update', [
            'basket' => 'test',
            'action' => 'replace',
            'ids' => [5, 6, 7]
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([5, 6, 7], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $this->assertEquals([5, 6, 7], $basket->load());
    }

    public function test_update_remove() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_update', [
            'basket' => 'test',
            'action' => 'remove',
            'ids' => [3, 4, 7]
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $this->assertEquals([1, 2], $basket->load());
    }

    public function test_update_remove_no_overlap() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_update', [
            'basket' => 'test',
            'action' => 'remove',
            'ids' => [5, 6, 7]
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $this->assertEquals([1, 2, 3, 4], $basket->load());
    }

    public function test_update_delete() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_delete', [
            'basket' => 'test',
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $this->assertEquals([], $basket->load());
    }

    public function test_copy_keep_source_no_options() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $basket = new session_basket('test2');
        $this->assertEquals([1, 2, 3, 4], $basket->load());

        $basket = new session_basket('test');
        $this->assertEquals([1, 2, 3, 4], $basket->load());
    }

    public function test_copy_keep_source_with_options() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => [
                'replace' => false,
                'deletesource' => false
            ]
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $basket = new session_basket('test2');
        $this->assertEquals([1, 2, 3, 4], $basket->load());

        $basket = new session_basket('test');
        $this->assertEquals([1, 2, 3, 4], $basket->load());
    }

    public function test_copy_delete_source() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => [
                'deletesource' => true
            ]
        ]);

        $this->assert_webservice_success($res);
        $basket = new session_basket('test2');
        $this->assertEquals([1, 2, 3, 4], $basket->load());

        $basket = new session_basket('test');
        $this->assertEmpty($basket->load());
    }

    public function test_copy_to_empty() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);

        $basket = new session_basket('test2');
        $this->assertEquals([1, 2, 3, 4], $basket->load());
    }

    public function test_copy_to_existing_add() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $basket = new session_basket('test2');
        $basket->add([5, 6, 7, 8]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    public function test_copy_to_existing_replace() {
        $basket = new session_basket('test');
        $basket->add([1, 2, 3, 4]);

        $basket = new session_basket('test2');
        $basket->add([5, 6, 7, 8]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => [
                'replace' => true
            ]
        ]);
        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([1, 2, 3, 4], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    public function test_copy_to_existing_replace_empty() {
        $basket = new session_basket('test');
        $basket->add([]);

        $basket = new session_basket('test2');
        $basket->add([5, 6, 7, 8]);

        $res = $this->call_webservice_api('totara_core_basket_copy', [
            'sourcebasket' => 'test',
            'targetbasket' => 'test2',
            'options' => [
                'replace' => true
            ]
        ]);
        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([], $result['ids']);
        $this->assertGreaterThan(0, $result['limit']);
    }

}
