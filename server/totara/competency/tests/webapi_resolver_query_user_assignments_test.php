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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

use core\collection;
use core\orm\query\builder;
use core\testing\component_generator;
use totara_competency\models\assignment;
use totara_competency\task\expand_assignments_task;
use totara_competency\testing\generator as competency_generator;
use totara_competency\user_groups;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \totara_competency\webapi\resolver\query\user_assignments
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_user_assignments_testcase extends advanced_testcase {

    private const QUERY = 'totara_competency_user_assignments';

    /**
     * @var competency_generator|component_generator
     */
    private $generator;

    use webapi_phpunit_helper;

    protected function tearDown(): void {
        parent::tearDown();
        $this->generator = null;
    }

    public function test_pagination_with_filters(): void {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        self::setUser($user1);

        // Test without filters applied
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user1->id));
        // Default sorting is alphabetical by competency name, with older competencies first.
        // Only the first 2 results is loaded.
        $this->assertEquals(
            [
                'Cleaning',
                'Nursing assistant',
            ],
            $this->get_competency_names_from_assignments($result['items'])
        );
        // Next page of results.
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user1->id, $result['next_cursor']));
        $this->assertEquals(
            ['Planning, organising and flexibility (behavioral)'],
            $this->get_competency_names_from_assignments($result['items'])
        );

        // As the other user
        self::setUser($user2);
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id));
        $this->assertEquals(
            [
                'Confidence and self-control (behavioral)',
                'Integrity (behavioral)',
            ],
            $this->get_competency_names_from_assignments($result['items'])
        );
        // Next page of results.
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id, $result['next_cursor']));
        $this->assertEquals(
            ['Serving the Customer'],
            $this->get_competency_names_from_assignments($result['items'])
        );

        // Apply the search filter
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id, null, null, [
            'search' => 'Behavior',
        ]));
        $this->assertEquals(
            [
                'Confidence and self-control (behavioral)',
                'Integrity (behavioral)',
            ],
            $this->get_competency_names_from_assignments($result['items'])
        );

        // Apply the IDs filter
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id, null, null, [
            'ids' => [$created_assignments->last()->id],
        ]));
        $this->assertEquals(
            ['Confidence and self-control (behavioral)'],
            $this->get_competency_names_from_assignments($result['items'])
        );
        // Empty IDs filter array should mean no results are returned.
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id, null, null, [
            'ids' => [],
        ]));
        $this->assertEquals([], $this->get_competency_names_from_assignments($result['items']));
    }

    public function test_no_assignments() {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user->id));
        $this->assertCount(0, $result['items']);
    }

    public function test_feature_disabled() {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        advanced_feature::disable('competency_assignment');

        self::setUser($user1);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user1->id));
        $this->assertCount(0, $result['items']);

        advanced_feature::enable('competency_assignment');
        advanced_feature::disable('competencies');

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user1->id));
        $this->assertCount(0, $result['items']);
    }

    public function test_require_login() {
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
    }

    public function test_no_input() {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        self::setUser($user1);

        // Test without filters applied
        $result = $this->resolve_graphql_query(self::QUERY, []);

        $this->assertCount(3, $result['items']);
        $this->assertEquals(
            [
                'Cleaning',
                'Nursing assistant',
                'Planning, organising and flexibility (behavioral)'
            ],
            $this->get_competency_names_from_assignments($result['items'])
        );
    }

    public function test_no_user_id() {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        self::setUser($user1);

        // Test without filters applied
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null));

        $this->assertCount(2, $result['items']);
        $this->assertEquals(
            [
                'Cleaning',
                'Nursing assistant',
            ],
            $this->get_competency_names_from_assignments($result['items'])
        );
    }

    public function test_require_view_own_capability() {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        self::setUser($user1);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');

        unassign_capability('totara/competency:view_own_profile', $role_id);

        $result = $this->resolve_graphql_query(self::QUERY, []);

        $this->assertCount(0, $result['items']);
        $this->assertCount(0, $result['filters']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals('', $result['next_cursor']);
    }

    public function test_require_view_other_capability() {
        [$created_assignments, $user1, $user2] = $this->create_test_data();

        self::setUser($user1);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');

        unassign_capability('totara/competency:view_other_profile', $role_id);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($user2->id));

        $this->assertCount(0, $result['items']);
        $this->assertCount(0, $result['filters']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals('', $result['next_cursor']);
    }

    /**
     * @param assignment[] $assignments
     * @return string[]
     */
    private function get_competency_names_from_assignments(array $assignments): array {
        return collection::new($assignments)
            ->map(function (assignment $assignment) {
                return $assignment->get_competency()->fullname;
            })
            ->to_array();
    }

    private function get_query_options($user_id = null, $cursor = null, $result_size = 2, $filters = null): array {
        $options = [
            'input' => [
                'result_size' => $result_size,
                'cursor' => $cursor,
                'user_id' => $user_id,
            ]
        ];
        if (isset($filters)) {
            $options['input']['filters'] = $filters;
        }
        return $options;
    }

    /**
     * @return array
     * @throws coding_exception
     */
    private function create_test_data() {
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $competency_assignments = [
            [
                'competency' => [
                    'name' => 'Nursing assistant',
                ],
                'user_id' => $user1->id,
            ],
            [
                'competency' => [
                    'name' => 'Serving the Customer',
                ],
                'user_id' => $user2->id,
            ],
            [
                'competency' => [
                    'name' => 'Cleaning',
                ],
                'user_id' => $user1->id,
            ],
            [
                'competency' => [
                    'name' => 'Integrity (behavioral)',
                ],
                'user_id' => $user2->id,
            ],
            [
                'competency' => [
                    'name' => 'Planning, organising and flexibility (behavioral)',
                ],
                'user_id' => $user1->id,
            ],
            [
                'competency' => [
                    'name' => 'Confidence and self-control (behavioral)',
                ],
                'user_id' => $user2->id,
            ],
        ];

        $assignments = [];

        foreach ($competency_assignments as $competency_assignment) {
            $competency = $this->generator()->create_competency(
                $competency_assignment['competency']['name'],
                null,
                $competency_assignment['competency']
            );

            if (isset($competency_assignment['user_id'])) {
                $competency_assignment['user_group_type'] = user_groups::USER;
                $competency_assignment['user_group_id'] = $competency_assignment['user_id'];
                unset($competency_assignment['user_id']);
            }

            $assignments[] = $this->generator()->assignment_generator()
                ->create_assignment(array_merge($competency_assignment, [
                    'competency_id' => $competency->id,
                ]));
        }

        (new expand_assignments_task())->execute();

        $created_assignments = collection::new($assignments);

        return [$created_assignments, $user1, $user2];
    }

    /**
     * @return competency_generator|component_generator
     */
    private function generator(): competency_generator {
        if (!isset($this->generator)) {
            $this->generator = self::getDataGenerator()->get_plugin_generator('totara_competency');
        }
        return $this->generator;
    }

}
