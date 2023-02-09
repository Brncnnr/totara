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

use core\date_format;
use core\format;
use totara_competency\models\assignment as assignment_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_assignment_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_assignment';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only assignment models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_query_successful() {
        $assignment = $this->create_data();

        // resolve id
        $this->assertEquals($assignment->get_id(), $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $assignment));

        // resolve type
        $this->assertEquals($assignment->get_type(), $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $assignment));

        // resolve competency_id
        $this->assertEquals(
            $assignment->get_competency()->id, $this->resolve_graphql_type(self::QUERY_TYPE, 'competency_id', $assignment)
        );

        // resolve user_group_type
        $this->assertEquals(
            $assignment->get_user_group()->get_type(), $this->resolve_graphql_type(self::QUERY_TYPE, 'user_group_type', $assignment)
        );

        // resolve user_group_id
        $this->assertEquals(
            $assignment->get_entity()->user_group_id, $this->resolve_graphql_type(self::QUERY_TYPE, 'user_group_id', $assignment)
        );

        // resolve status
        $this->assertEquals($assignment->get_status(), $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $assignment));

        // resolve created_by
        $this->assertEquals(
            $assignment->get_entity()->created_by, $this->resolve_graphql_type(self::QUERY_TYPE, 'created_by', $assignment)
        );

        // resolve created_at
        $this->assertEquals(
            $assignment->get_entity()->created_at,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'created_at', $assignment, ['format' => date_format::FORMAT_TIMESTAMP])
        );

        // resolve updated_at
        $this->assertEquals(
            $assignment->get_entity()->updated_at,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'updated_at', $assignment, ['format' => date_format::FORMAT_TIMESTAMP])
        );

        // resolve archived_at
        $this->assertEquals(
            $assignment->get_entity()->archived_at,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'archived_at', $assignment, ['format' => date_format::FORMAT_TIMESTAMP])
        );

        // resolve competency
        $competency = $this->resolve_graphql_type(self::QUERY_TYPE, 'competency', $assignment);
        $this->assertEquals($assignment->get_competency()->id, $competency->id);

        // resolve status_name
        $this->assertEquals(
            $assignment->get_status_name(), $this->resolve_graphql_type(self::QUERY_TYPE, 'status_name', $assignment)
        );

        // resolve type_name
        $this->assertEquals($assignment->get_type_name(), $this->resolve_graphql_type(self::QUERY_TYPE, 'type_name', $assignment));

        // resolve progress_name
        $this->assertEquals(
            $assignment->get_progress_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'progress_name', $assignment, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $assignment->get_progress_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'progress_name', $assignment, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $assignment->get_progress_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'progress_name', $assignment, ['format' => format::FORMAT_PLAIN])
        );

        // resolve reason_assigned
        $this->assertEquals(
            $assignment->get_reason_assigned(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'reason_assigned', $assignment, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $assignment->get_reason_assigned(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'reason_assigned', $assignment, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $assignment->get_reason_assigned(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'reason_assigned', $assignment, ['format' => format::FORMAT_PLAIN])
        );

        // resolve assigner
        $assigner = $this->resolve_graphql_type(self::QUERY_TYPE, 'assigner', $assignment);
        $this->assertEquals($assignment->get_assigner()->id, $assigner->id);

        // resolve can_archive
        $this->assertEquals(
            $assignment->get_field('can_archive'), $this->resolve_graphql_type(self::QUERY_TYPE, 'can_archive', $assignment)
        );
    }

    private function create_data() {
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $competency = $generator->create_competency();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $user_assignment = $assignment_generator->create_cohort_assignment($competency->id, $user->id);
        return assignment_model::load_by_id($user_assignment->id);
    }
}