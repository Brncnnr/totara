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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_evidence
 */

use core\entity\user;
use totara_evidence\data_providers\evidence;
use totara_evidence\data_providers\provider;
use totara_evidence\models\evidence_item;
use totara_evidence\models\evidence_type;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class totara_evidence_data_provider_testcase extends totara_evidence_testcase {

    /** @var user|null */
    private $users = null;

    /** @var evidence_type[]|null */
    private $evidence_types = null;

    /** @var evidence_item[]|null */
    private $evidence_items = null;

    /** @var provider|null */
    private $data_provider = null;

    /**
     * @return void
     */
    public function setUp(): void {
        $this->setAdminUser();

        // Create evidence types.
        $this->evidence_types[] = $this->generator()->create_evidence_type([
            'name' => 'Completion',
        ]);
        $this->evidence_types[] = $this->generator()->create_evidence_type([
            'name' => 'Enrolment',
        ]);
        $this->evidence_types[] = $this->generator()->create_evidence_type([
            'name' => 'Something',
        ]);
        $this->evidence_types[] = $this->generator()->create_evidence_type([
            'name' => 'Anything',
        ]);

        // Change to user1.
        $user1 = $this->generator()->create_evidence_user();
        $this->users[] = $user1;
        $this->setUser($user1);

        // Create evidence bank items.
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[0]->get_id(),
            'user_id' => $user1->id,
            'name' => 'Conference attendance',
        ]);
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[0]->get_id(),
            'user_id' => $user1->id,
            'name' => 'Course transcript',
        ]);
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[1]->get_id(),
            'user_id' => $user1->id,
            'name' => 'Confirmation letter',
        ]);

        // Change to user2.
        $user2 = $this->generator()->create_evidence_user();
        $this->users[] = $user2;
        $this->setUser($user2);

        // Create evidence bank items.
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[0]->get_id(),
            'user_id' => $user2->id,
            'name' => 'Hello',
        ]);
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[1]->get_id(),
            'user_id' => $user2->id,
            'name' => 'Corn',
        ]);
        $this->evidence_items[] = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_types[1]->get_id(),
            'user_id' => $user2->id,
            'name' => 'Confirmation letter',
        ]);

        // Create evidence data provider instance.
        $this->data_provider = evidence::create();
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->users = null;
        $this->evidence_types = null;
        $this->evidence_items = null;
        $this->data_provider = null;
    }

    /**
     * @return void
     */
    public function test_sort_order(): void {
        $user = $this->users[0];
        $this->setUser($user);
        $this->data_provider->set_filters(['user_id' => $user->id]);
        $user1_evidence_items = array_filter(
            $this->evidence_items,
            function ($evidence_item) use ($user) {
                return $evidence_item->user_id == $user->id;
            }
        );

        // Test id sort ascending.
        $this->data_provider->set_order('id', 'asc');
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            array_map(
                function ($evidence_item) {
                    return $evidence_item->id;
                },
                $user1_evidence_items
            ),
            $records->keys()
        );

        // Test id sort descending.
        $this->data_provider->set_order('id', 'desc');
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            array_reverse(array_map(
                function ($evidence_item) {
                    return $evidence_item->id;
                },
                $user1_evidence_items
            )),
            $records->keys()
        );

        // Test name sort ascending.
        $this->data_provider->set_order('name', 'asc');
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                'Conference attendance',
                'Confirmation letter',
                'Course transcript',
            ],
            $records->pluck('name')
        );

        // Test name sort descending.
        $this->data_provider->set_order('name', 'desc');
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                'Course transcript',
                'Confirmation letter',
                'Conference attendance',
            ],
            $records->pluck('name')
        );
    }

    /**
     * @return void
     */
    public function test_filters(): void {
        // Test ids filter.
        $this->data_provider->set_filters(['ids' => $this->evidence_items[1]->get_id()]);
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                $this->evidence_items[1]->name,
            ],
            $records->pluck('name')
        );

        // Test name filter (no user filter so should have two 'confirmation letters').
        $this->data_provider->set_filters(['name' => 'etter']);
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                'Confirmation letter',
                'Confirmation letter'
            ],
            $records->pluck('name')
        );

        // Test user_id filter.
        $this->data_provider->set_filters(['user_id' => $this->users[1]->id]);
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                'Hello',
                'Corn',
                'Confirmation letter',
            ],
            $records->pluck('name')
        );

        // Test type_id filter.
        $this->data_provider->set_filters(['type_id' => $this->evidence_types[0]->id]);
        $records = $this->data_provider->fetch();
        $this->assertEquals(
            [
                'Conference attendance',
                'Course transcript',
                'Hello',
            ],
            $records->pluck('name')
        );
    }

    /**
     * @return void
     */
    public function test_fetch_paginated(): void {
        $this->data_provider->set_page_size(1);
        /** @var array $records */
        $records = $this->data_provider->fetch_paginated();
        $this->assertEquals(6, $records['total']);
        $this->assertCount(1, $records['items']);

        $this->data_provider->set_page_size(3);
        /** @var array $records */
        $records = $this->data_provider->fetch_paginated();
        $this->assertEquals(6, $records['total']);
        $this->assertCount(3, $records['items']);
    }

}