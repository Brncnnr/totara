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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_competency
 */

use core\orm\query\builder;
use totara_competency\admin_setting_unassign_behaviour;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\models\assignment as assignment_model;
use totara_job\job_assignment;

global $CFG;
require_once $CFG->dirroot . '/totara/competency/tests/profile_query_resolver_test.php';

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_profile_progress_testcase extends profile_query_resolver_test {

    /**
     * @inheritDoc
     */
    protected function get_query_name(): string {
        return 'totara_competency_profile_progress';
    }

    public function test_view_own_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->user);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertCount(1, $result->items);
    }

    public function test_view_other_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->manager);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];
        
        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertCount(1, $result->items);
    }

    public function test_view_with_status_filter_with_continuous_tracking_enabled() {
        $this->setAdminUser();
        // Create user
        $user = $this->getDataGenerator()->create_user();

        // Make sure we keep the data
        set_config('unassign_behaviour', admin_setting_unassign_behaviour::KEEP, 'totara_competency');

        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Pos Framework']);
        // Create position 1
        $position1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        // Create position 2
        $position2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);
        // Create position 3
        $position3 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 3']);

        /** @var totara_competency_assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()
            ->get_plugin_generator('totara_competency')
            ->assignment_generator();

        // Create competency
        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        // Create position 1 assignment
        $pos_assignment1 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position1->id,
            ['status' => assignment::STATUS_ACTIVE]
        );
        // Create position 2 assignment
        $pos_assignment2 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position2->id,
            ['status' => assignment::STATUS_ACTIVE]
        );
        // Create position 3 assignment
        $pos_assignment3 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position3->id,
            ['status' => assignment::STATUS_ACTIVE]
        );

        $ja1 = job_assignment::create_default($user->id, ['positionid' => $position1->id]);
        $ja2 = job_assignment::create_default($user->id, ['positionid' => $position2->id]);
        $ja3 = job_assignment::create_default($user->id, ['positionid' => $position3->id]);

        (new expand_task(builder::get_db()))->expand_all();

        // Query with status 1
        $args_active = [
            'user_id' => $user->id,
            'competency_id' => $comp->id,
            'filters' => [
                'status' => assignment::STATUS_ACTIVE,
            ],
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args_active);
        $this->assertCount(3, $result->items);

        // Check that all three positions are returned in the filters
        $this->assertCount(3, $result->filters);
        $positions = array_column($result->filters, 'name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 1',
                'Position 2',
                'Position 3',
            ],
            $positions
        );
        $this->assertEqualsCanonicalizing(
            [
                sprintf('%s/%s/%s/%s', assignment::STATUS_ACTIVE, 'admin', 'position', $position1->id),
                sprintf('%s/%s/%s/%s', assignment::STATUS_ACTIVE, 'admin', 'position', $position2->id),
                sprintf('%s/%s/%s/%s', assignment::STATUS_ACTIVE, 'admin', 'position', $position3->id),
            ],
            array_keys($result->filters)
        );

        // Check that both assignments are loaded
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 1',
                'Position 2',
                'Position 3',
            ],
            $positions
        );

        // Query with status 2
        $args_archived = [
            'user_id' => $user->id,
            'competency_id' => $comp->id,
            'filters' => [
                'status' => assignment::STATUS_ARCHIVED,
            ],
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args_archived);

        // Check that no assignments are loaded
        $this->assertCount(0, $result->items);

        // Now unassign user from position 1
        $ja1->update(['positionid' => null]);

        // Run expand task
        (new expand_task(builder::get_db()))->expand_all();

        // Query with status 1
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_active);
        $this->assertCount(2, $result->items);

        // Check that the one the user got unassigned from is gone
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 2',
                'Position 3',
            ],
            $positions
        );

        // Query with status 2
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_archived);
        $this->assertCount(1, $result->items);

        // Check that there's the one the user got unassigned from
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 1'
            ],
            $positions
        );

        // Now archive position 2 assignment
        $pos_assignment2 = assignment_model::load_by_id($pos_assignment2->id);
        $pos_assignment2->archive();

        // Run expand task
        (new expand_task(builder::get_db()))->expand_all();

        // Query with status 1
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_active);
        // Check that there's one assignment left
        $this->assertCount(1, $result->items);
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 3'
            ],
            $positions
        );

        // Query with status 2
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_archived);
        $this->assertCount(2, $result->items);

        // Check that there are two assignments now
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 1',
                'Position 2'
            ],
            $positions
        );

        // Now archive position 3 assignment with continuous tracking
        $pos_assignment3 = assignment_model::load_by_id($pos_assignment3->id);
        $pos_assignment3->archive(true);

        // Run expand task
        (new expand_task(builder::get_db()))->expand_all();

        // Query with status 1
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_active);
        // Check that there's one continuous tracking assignment
        $this->assertCount(1, $result->items);
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Continuous tracking'
            ],
            $positions
        );

        // Query with status 2
        $result = $this->resolve_graphql_query($this->get_query_name(), $args_archived);
        $this->assertCount(3, $result->items);

        // Check that there are three assignments now
        $positions = $result->items->pluck('name');
        $this->assertEqualsCanonicalizing(
            [
                'Position 1',
                'Position 2',
                'Position 3',
            ],
            $positions
        );

        // Check that all three positions are returned in the filters
        $this->assertCount(4, $result->filters);
        $filters = array_column($result->filters, 'name');
        $this->assertEqualsCanonicalizing(
            [
                'Continuous tracking',
                'Position 1',
                'Position 2',
                'Position 3',
            ],
            $filters
        );
        $this->assertEqualsCanonicalizing(
            [
                sprintf('%s/%s/%s/%s', assignment::STATUS_ACTIVE, 'system', 'user', $user->id),
                sprintf('%s/%s/%s/%s', assignment::STATUS_ARCHIVED, 'admin', 'position', $position1->id),
                sprintf('%s/%s/%s/%s', assignment::STATUS_ARCHIVED, 'admin', 'position', $position2->id),
                sprintf('%s/%s/%s/%s', assignment::STATUS_ARCHIVED, 'admin', 'position', $position3->id),
            ],
            array_keys($result->filters)
        );
    }

}