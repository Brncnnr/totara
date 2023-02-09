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
require_once $CFG->dirroot . '/totara/competency/tests/scale_query_resolver_test.php';

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_scale_testcase extends scale_query_resolver_test {

    protected const ID_ARG_EXCEPTION_MESSAGE = '/Please provide "id" OR "competency_id" OR "framework_id"/';

    /**
     * @inheritDoc
     */
    protected function get_query_name(): string {
        return 'totara_competency_scale';
    }

    /**
     * @dataProvider query_successful_provider
     * @param closure $get_args
     */
    public function test_query_successful(closure $get_args): void {
        $data = $this->create_data();

        $args = $get_args($data);

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertEquals($data->scale->id, $result->get_id());
    }

    public function query_successful_provider(): array {
        return [
            'by scale id' => [
                function (object $data) {
                    return ['id' => $data->scale->id];
                }
            ],
            'by competency id' => [
                function (object $data) {
                    return ['competency_id' => $data->comp1->id];
                }
            ],
            'by framework id' => [
                function (object $data) {
                    return ['framework_id' => $data->fw1->id];
                }
            ],
        ];
    }
}