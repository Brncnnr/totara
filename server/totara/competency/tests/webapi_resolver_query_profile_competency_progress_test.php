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

global $CFG;
require_once $CFG->dirroot . '/totara/competency/tests/profile_query_resolver_test.php';

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_profile_competency_progress_testcase extends profile_query_resolver_test {
    /**
     * @inheritDoc
     */
    protected function get_query_name(): string {
        return 'totara_competency_profile_competency_progress';
    }

    public function test_view_own_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->user);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];
        
        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertEquals($data->assignment->id, $result[0]->assignments->first()->id);
        $this->assertEquals($data->comp->id, $result[0]->competency->id);
    }

    public function test_view_other_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->manager);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertEquals($data->assignment->id, $result[0]->assignments->first()->id);
        $this->assertEquals($data->comp->id, $result[0]->competency->id);
    }
}