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
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\models\activity_log\assignment as activity_log_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_activity_log_row_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_activity_log_row';

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve timestamp
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timestamp', $data, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->get_date(), $result);

        // resolve description
        $this->assertEquals(
            $data->get_description(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $data->get_description(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->get_description(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_PLAIN])
        );

        // resolve assignment
        $result = $this->resolve_graphql_type(self::QUERY_TYPE,'assignment', $data);
        $this->assertEquals($data->get_assignment()->get_id(), $result->id);

        // resolve assignment_action
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'assignment_action', $data);
        $this->assertEquals($data->get_assignment_action(), $result);

        // resolve type
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $data);
        $this->assertEquals('assignment', $result);
    }

    private function create_data() {
        $assignment = new assignment();
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = time();
        $assignment_log->action = competency_assignment_user_log::ACTION_TRACKING_START;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $activity_log = activity_log_assignment::load_by_entity($assignment_log);

        return $activity_log;
    }
}