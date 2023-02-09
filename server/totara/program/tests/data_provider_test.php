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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_program
 */

use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use totara_program\data_provider\program as program_provider;
use totara_program\entity\program_completion;
use totara_program\entity\filter\program_filter_factory;
use totara_program\entity\filter\program_progress;
use totara_program\entity\filter\user_programs;
use core_phpunit\testcase;

class totara_program_data_provider_testcase extends testcase {

    /**
     * @return void
     */
    public function test_filter_factory(): void {
        $filter_factory = new program_filter_factory();

        // Confirm user filter is correct.
        $filter = $filter_factory->create('user_id', 2);
        $this->assertInstanceOf(user_programs::class, $filter);

        // Confirm ids filter is correct.
        $filter = $filter_factory->create('ids', [1]);
        $this->assertInstanceOf(in::class, $filter);

        // Confirm search filter is correct.
        $filter = $filter_factory->create('search', 'blah');
        $this->assertInstanceOf(like::class, $filter);

        // Confirm progress filter is correct.
        $filter = $filter_factory->create('progress', 'Completed');
        $this->assertInstanceOf(program_progress::class, $filter);

        // Confirm invalid filter gets expected result.
        $filter = $filter_factory->create('unknown', '');
        $this->assertNull($filter);
    }

    /**
     * @return void
     */
    public function test_provider(): void {
        $this->setAdminUser();

        // Create programs.
        $programs = $this->create_programs(3);

        // Create an instance of the data provider.
        $data_provider = program_provider::create();

        // Confirm that we get any data back (without any filters applied).
        $result = $data_provider->fetch();
        $this->assertNotNull($programs);
        $this->assertEquals(3, $result->count());

        foreach ([$programs[0]->id, $programs[1]->id, $programs[2]->id] as $program_id) {
            $this->assertTrue($result->has('id', $program_id));
        }

        // Confirm pagination works.
        $result = $data_provider->set_page_size(2)->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);

        /** @var array $items **/
        $items = $result['items'];
        $this->assertCount(2, $items);

        $total = $result['total'];
        $this->assertEquals(3, $total);

        $next_cursor = $result['next_cursor'];
        $this->assertNotEmpty($next_cursor);

        // Provider should throw exception if filter factory not set.
        try {
            $data_provider->set_filters(['foo' => 'bar'])->fetch();
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: No filter factory registered',
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_user_filter(): void {
        $gen = $this->getDataGenerator();
        $program_gen = $gen->get_plugin_generator('totara_program');

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $user4 = $gen->create_user();

        $this->setUser($user1);

        // Create programs.
        $programs = $this->create_programs(5);

        // Assign the user to the program as an individual.
        $program_gen->assign_to_program(
            $programs[0]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[2]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[4]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );

        // Assign other users to the program as an individual.
        $program_gen->assign_to_program(
            $programs[3]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user2->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[4]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user3->id,
            null,
            true
        );

        // Confirm that we don't find any results.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $user4->id])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct programs back for a specific user.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $user1->id])->fetch();
        foreach ([$programs[0]->id, $programs[2]->id, $programs[4]->id] as $program_id) {
            $this->assertTrue($result->has('id', $program_id));
        }

        // Confirm that we got the same result back as user programs.
        $programs = \prog_get_all_programs(
            $user1->id,
            '',
            '',
            '',
            false,
            false,
            true
        );
        $this->assertIsArray($programs);
        $this->assertCount(3, $programs);
        foreach ($programs as $program) {
            $this->assertTrue($result->has('id', $program->id));
        }
    }

    /**
     * @return void
     */
    public function test_user_filter_for_tenant(): void {
        $gen = $this->getDataGenerator();
        $program_gen = $gen->get_plugin_generator('totara_program');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $gen->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant1->id
        ]);

        $tenant2_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant2->id
        ]);

        $tenant3_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant1->id
        ]);

        $programs = $this->create_programs(4);

        // Assign the user to the program as an individual.
        $program_gen->assign_to_program(
            $programs[0]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $tenant1_user->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[3]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $tenant1_user->id,
            null,
            true
        );

        // Assign the user to the program as an individual.
        $program_gen->assign_to_program(
            $programs[1]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $tenant2_user->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[2]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $tenant2_user->id,
            null,
            true
        );

        // Confirm that we don't find any results.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $tenant3_user->id])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct programs back for a specific user.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $tenant1_user->id])->fetch();
        foreach ([$programs[0]->id, $programs[3]->id] as $program_id) {
            $this->assertTrue($result->has('id', $program_id));
        }

        // Confirm that we got the same result back as user programs.
        $programs = \prog_get_all_programs(
            $tenant1_user->id,
            '',
            '',
            '',
            false,
            false,
            true
        );
        $this->assertIsArray($programs);
        $this->assertCount(2, $programs);
        foreach ($programs as $program) {
            $this->assertTrue($result->has('id', $program->id));
        }
    }

    /**
     * @return void
     */
    public function test_ids_filter(): void {
        // Create programs.
        $programs = $this->create_programs(10);

        // Confirm that we don't find any results.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['ids' => [$programs[0]->id + 50, $programs[1]->id + 50]])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct programs back based on IDs.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['ids' => [$programs[3]->id, $programs[7]->id]])->fetch();
        $this->assertEquals(2, $result->count());
        foreach ([$programs[3]->id, $programs[7]->id] as $program_id) {
            $this->assertTrue($result->has('id', $program_id));
        }
    }

    /**
     * @return void
     */
    public function test_search_filter(): void {
        $gen = $this->getDataGenerator();
        $program_gen = $gen->get_plugin_generator('totara_program');

        // Create programs.
        $this->create_programs(5);
        $program_gen->create_program([
            'fullname' => 'Vanilla Pepsi',
        ]);

        // Confirm that we don't find any results.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['search' => 'this program does not exist'])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct programs back based on name match.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['search' => 'Vanilla Pepsi'])->fetch();
        $this->assertEquals(1, $result->count());
        $this->assertTrue($result->has('fullname', 'Vanilla Pepsi'));
    }

    /**
     * @return void
     */
    public function test_progress_filter(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $completion_gen = $gen->get_plugin_generator('core_completion');
        $program_gen = $gen->get_plugin_generator('totara_program');
        $time = time();

        // Create user.
        $user1 = $gen->create_user();

        // Create programs.
        $programs = $this->create_programs(5);

        // Get not-tracked program.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'NOT_TRACKED'])->fetch();
        $this->assertTrue($result->has('id', $programs[3]->id));

        // Assign the user to the program as an individual.
        $program_gen->assign_to_program(
            $programs[3]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );

        // Get not started program.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'NOT_STARTED'])->fetch();
        $this->assertTrue($result->has('id', $programs[3]->id));

        // Update program to 'in progress'.
        $DB->set_fields(
            program_completion::TABLE,
            [
                'status' => STATUS_PROGRAM_INCOMPLETE,
                'timestarted' => $time
            ],
            [
                'programid' => $programs[3]->id,
            ]
        );

        // Get in progress program.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'IN_PROGRESS'])->fetch();
        $this->assertTrue($result->has('id', $programs[3]->id));

        // Update program to 'completed'.
        $DB->set_field(
            program_completion::TABLE,
            'status',
            STATUS_PROGRAM_COMPLETE,
            [
                'programid' => $programs[3]->id,
            ]
        );

        // Get completed program.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'COMPLETED'])->fetch();
        $this->assertTrue($result->has('id', $programs[3]->id));
    }

    /**
     * @return void
     */
    public function test_multi_filter(): void {
        $gen = $this->getDataGenerator();
        $program_gen = $gen->get_plugin_generator('totara_program');

        // Create users.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        // Create programs.
        $programs = $this->create_programs(10);

        // Assign the user to the program as an individual.
        $program_gen->assign_to_program(
            $programs[1]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[2]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[3]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user1->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[0]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user2->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[1]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user2->id,
            null,
            true
        );
        $program_gen->assign_to_program(
            $programs[6]->id,
            ASSIGNTYPE_INDIVIDUAL,
            $user2->id,
            null,
            true
        );

        // Confirm that we get the correct programs back for a specific user.
        $data_provider = program_provider::create(new program_filter_factory());
        $result = $data_provider
            ->set_filters([
                'user_id' => $user1->id,
                'ids' => [$programs[2]->id, $programs[1]->id, $programs[6]->id]
            ])
            ->fetch();

        // User 1 is not assigned to program6 so that should not be part of result.
        $this->assertEquals(2, $result->count());

        foreach ([$programs[1]->id, $programs[2]->id] as $program_id) {
            $this->assertTrue($result->has('id', $program_id));
        }
    }

    /**
     * @param int $total
     *
     * @return array
     */
    private function create_programs(int $total): array {
        $gen = $this->getDataGenerator();
        $program_gen = $gen->get_plugin_generator('totara_program');

        $programs = [];
        for ($x = 1; $x <= $total; ++$x) {
            $programs[] = new program($program_gen->create_program());
        }

        return $programs;
    }

}