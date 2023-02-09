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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package pathway_manual
 * @subpackage test
 */

use core\webapi\execution_context;
use pathway_manual\models\roles\manager;
use pathway_manual\webapi\resolver\mutation\create_manual_ratings;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * Tests the mutation to create manual ratings.
 *
 * @group totara_competency
 */
class pathway_manual_webapi_resolver_mutation_create_manual_ratings_testcase extends pathway_manual_base_testcase {

    private function get_execution_context() {
        return execution_context::create('ajax', 'pathway_manual_create_manual_ratings');
    }

    /**
     * Test the mutation resolver.
     */
    public function test_resolve_successful() {
        global $DB;

        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency2, [manager::class]);

        $scale_value_id_1 = $this->get_scale_value_id('11');
        $scale_value_id_2 = $this->get_scale_value_id('22');

        $this->assertFalse($DB->record_exists('pathway_manual_rating', ['competency_id' => $this->competency1->id]));
        $this->assertFalse($DB->record_exists('pathway_manual_rating', ['competency_id' => $this->competency2->id]));

        $args = [
            'user_id' => $this->user1->id,
            'role' => manager::class,
            'ratings' => [
                [
                    'competency_id' => $this->competency1->id,
                    'scale_value_id' => $scale_value_id_1,
                    'comment' => 'Test comment 1',
                ],
                [
                    'competency_id' => $this->competency2->id,
                    'scale_value_id' => $scale_value_id_2,
                    'comment' => 'Test comment 2',
                ],
            ]
        ];

        create_manual_ratings::resolve($args, $this->get_execution_context());

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency1->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_1,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 1', $record->comment);

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency2->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_2,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 2', $record->comment);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_ajax_query_successful() {
        global $DB;

        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency2, [manager::class]);
        $scale_value_id_1 = $this->get_scale_value_id('11');
        $scale_value_id_2 = $this->get_scale_value_id('22');

        $args = [
            'user_id' => $this->user1->id,
            'role' => manager::class,
            'ratings' => [
                [
                    'competency_id' => $this->competency1->id,
                    'scale_value_id' => $scale_value_id_1,
                    'comment' => 'Test comment 1',
                ],
                [
                    'competency_id' => $this->competency2->id,
                    'scale_value_id' => $scale_value_id_2,
                    'comment' => 'Test comment 2',
                ],
            ]
        ];

        $result = \totara_webapi\graphql::execute_operation(
            $this->get_execution_context(),
            $args
        );
        $this->assertEquals([], $result->errors);
        $this->assertTrue($result->data['pathway_manual_create_manual_ratings']);

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency1->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_1,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 1', $record->comment);

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency2->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_2,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 2', $record->comment);
    }

    /**
     * Test failing mutation.
     *
     * Just test that it gives us an error message and that nothing is saved.
     * Don't test all the ways it could fail. That is done in the rating model tests.
     */
    public function test_ajax_query_failure() {
        global $DB;

        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency2, [manager::class]);
        $scale_value_id_1 = $this->get_scale_value_id('11');

        $args = [
            'user_id' => $this->user1->id,
            'role' => manager::class,
            'ratings' => [
                [
                    'competency_id' => $this->competency1->id,
                    'scale_value_id' => $scale_value_id_1,
                    'comment' => 'Test comment 1',
                ],
                [
                    'competency_id' => $this->competency2->id,
                    // Provoke exception (scale_value_id_1 doesn't belong to comp2).
                    'scale_value_id' => $scale_value_id_1,
                    'comment' => 'Test comment 2',
                ],
            ]
        ];

        $result = \totara_webapi\graphql::execute_operation(
            $this->get_execution_context(),
            $args
        );
        $this->assertNull($result->data['pathway_manual_create_manual_ratings']);
        $this->assertCount(1, $result->errors);
        $this->assertStringContainsString('Invalid scale value', $result->errors[0]->getMessage());

        // None of the records should exist, even though only one had bad data.
        $this->assertFalse($DB->record_exists('pathway_manual_rating', ['competency_id' => $this->competency1->id]));
        $this->assertFalse($DB->record_exists('pathway_manual_rating', ['competency_id' => $this->competency2->id]));
    }
}
