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

use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_archive_user_assignment_result_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_archive_user_assignment_result';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only assignment models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'archived_assignment', new stdClass());
    }

    public function test_resolve_archived_assignment() {
        $assignment = $this->create_data();
        $result = $this->resolve_graphql_type(self::QUERY_TYPE,'archived_assignment', $assignment);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_resolve_unknown_field() {
        $assignment = $this->create_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Field not implemented");
        $this->resolve_graphql_type(self::QUERY_TYPE,'assignment', $assignment);
    }

    private function create_data() {
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $user = $this->getDataGenerator()->create_user();
        $fw = $generator->create_framework();
        $comp = $generator->create_competency(null, $fw);
        $assignment_gen = $generator->assignment_generator();
        $assignment = new assignment($assignment_gen->create_user_assignment($comp->id, $user->id));
        $assignment = assignment_model::load_by_entity($assignment);
        return $assignment;
    }
}