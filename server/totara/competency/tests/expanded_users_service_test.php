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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_core\basket\session_basket;
use totara_core\phpunit\webservice_utils;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/expanded_users_testcase.php';

/**
 * @group totara_competency
 */
class totara_competency_expanded_users_service_testcase extends expanded_users_testcase {

    use webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_with_empty_basket() {
        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'user' => 'foobar'
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_with_non_existent_users_in_basket() {
        $user_basket = new session_basket('user_basket');
        $user_basket->add([666, 667, 668]);

        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'user' => 'user_basket'
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_users() {
        $data = $this->generate_data();

        $user_basket = new session_basket('user_basket');
        $user_basket->add([$data->user17->id, $data->user18->id]);

        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'user' => 'user_basket'
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user17),
            'user_id' => (int) $data->user17->id,
            'user_group_names' => [[ 'user_group_name' => 'Individual' ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user18),
            'user_id' => (int) $data->user18->id,
            'user_group_names' => [[ 'user_group_name' => 'Individual' ]]
        ], $result['items']);

        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(0, $result['page']);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(2, $result['total']);
    }

    public function test_positions() {
        $data = $this->generate_data();

        $pos_basket = new session_basket('position_basket');
        $pos_basket->add([$data->pos1->id]);

        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'position' => 'position_basket'
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(3, $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user6),
            'user_id' => (int) $data->user6->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user7),
            'user_id' => (int) $data->user7->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user8),
            'user_id' => (int) $data->user8->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(0, $result['page']);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(3, $result['total']);
    }

    public function test_multiple_user_grous() {
        $data = $this->generate_data();

        $pos_basket = new session_basket('position_basket');
        $pos_basket->add([$data->pos1->id]);

        job_assignment::create([
            'userid' => $data->user16->id,
            'idnumber' => 'dev3',
            'positionid' => $data->pos1->id
        ]);

        job_assignment::create([
            'userid' => $data->user16->id,
            'idnumber' => 'dev4',
            'positionid' => $data->pos2->id
        ]);

        $pos_basket = new session_basket('position_basket');
        $pos_basket->add([$data->pos1->id]);

        $pos_basket = new session_basket('user_basket');
        $pos_basket->add([$data->user16->id, $data->user17->id]);

        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'position' => 'position_basket',
                'user' => 'user_basket',
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(5, $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user6),
            'user_id' => (int) $data->user6->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user7),
            'user_id' => (int) $data->user7->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user8),
            'user_id' => (int) $data->user8->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user17),
            'user_id' => (int) $data->user17->id,
            'user_group_names' => [[ 'user_group_name' => 'Individual' ]]
        ], $result['items']);

        // Build the expected result, position name is random so we make sure the order matches the result
        $expected_user_groups = [
            $data->pos1->fullname,
            'Individual'
        ];
        sort($expected_user_groups);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user16),
            'user_id' => (int) $data->user16->id,
            'user_group_names' => array_map(function ($item) {
                return ['user_group_name' => $item];
            }, $expected_user_groups)
        ], $result['items']);

        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(0, $result['page']);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(5, $result['total']);
    }

    public function test_full() {
        $data = $this->generate_data();

        $user_basket = new session_basket('user_basket');
        $user_basket->add([$data->user17->id, $data->user18->id]);

        $pos_basket = new session_basket('position_basket');
        $pos_basket->add([$data->pos1->id]);

        $org_basket = new session_basket('organisation_basket');
        $org_basket->add([$data->org2->id]);

        $coh_basket = new session_basket('cohort_basket');
        $coh_basket->add([$data->cohort1->id]);

        $res = $this->call_webservice_api('totara_competency_expand_user_groups_index', [
            'baskets' => [
                'position' => 'position_basket',
                'user' => 'user_basket',
                'cohort' => 'cohort_basket',
                'organisation' => 'organisation_basket'
            ],
            'filters' => [],
            'page' => 0,
            'order' => '',
            'direction' => ''
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(10, $result['items']);

        // Expected positions

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user6),
            'user_id' => (int) $data->user6->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user7),
            'user_id' => (int) $data->user7->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user8),
            'user_id' => (int) $data->user8->id,
            'user_group_names' => [[ 'user_group_name' => $data->pos1->fullname ]]
        ], $result['items']);

        // Expected individuals

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user17),
            'user_id' => (int) $data->user17->id,
            'user_group_names' => [[ 'user_group_name' => 'Individual' ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user18),
            'user_id' => (int) $data->user18->id,
            'user_group_names' => [[ 'user_group_name' => 'Individual' ]]
        ], $result['items']);

        // Expected cohorts

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user1),
            'user_id' => (int) $data->user1->id,
            'user_group_names' => [[ 'user_group_name' => $data->cohort1->name ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user2),
            'user_id' => (int) $data->user2->id,
            'user_group_names' => [[ 'user_group_name' => $data->cohort1->name ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user3),
            'user_id' => (int) $data->user3->id,
            'user_group_names' => [[ 'user_group_name' => $data->cohort1->name ]]
        ], $result['items']);

        // Expected organisations

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user14),
            'user_id' => (int) $data->user14->id,
            'user_group_names' => [[ 'user_group_name' => $data->org2->fullname ]]
        ], $result['items']);

        $this->assertContainsEquals([
            'full_name' => $this->get_full_name($data->user15),
            'user_id' => (int) $data->user15->id,
            'user_group_names' => [[ 'user_group_name' => $data->org2->fullname ]]
        ], $result['items']);

        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(0, $result['page']);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(10, $result['total']);
    }

}
