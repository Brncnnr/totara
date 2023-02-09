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

use core\entity\user;
use core\format;
use totara_competency\entity\assignment;
use totara_competency\models\profile\proficiency_value;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_profile_scale_value_progress_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_profile_scale_value_progress';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only proficiency_value models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        [$proficiency_value, $competency] = $this->create_data();

        // resolve id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $proficiency_value);
        $this->assertEquals($competency->scale->min_proficient_value->id, $result);

        // resolve scale_id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'scale_id', $proficiency_value);
        $this->assertEquals($competency->scale->id, $result);

        // resolve name
        $this->assertEquals(
            'second scale',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $proficiency_value, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>second scale</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $proficiency_value, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'second scale',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $proficiency_value, ['format' => format::FORMAT_PLAIN])
        );

        // resolve percentage
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'percentage', $proficiency_value);
        $this->assertEquals(50, $result);

        // resolve proficient
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'proficient', $proficiency_value);
        $this->assertTrue($result);
    }

    private function create_data() {
        $this->generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $user = new user($this->getDataGenerator()->create_user(), false);

        $scale = $this->generator->create_scale(
            '1', '1', [
                ['name' => 'first scale', 'proficient' => false, 'default' => true, 'sortorder' => 1],
                ['name' => '<p>second scale</p>', 'proficient' => true, 'default' => false, 'sortorder' => 2],
            ]
        );

        $fw = $this->generator->create_framework($scale);
        $competency = $this->generator->create_competency('comp1', $fw);
        $assignment_gen = $this->generator->assignment_generator();
        $assignment = new assignment($assignment_gen->create_user_assignment($competency->id, $user->id));

        $proficiency_value = proficiency_value::min_value($assignment);

        return [$proficiency_value, $competency];
    }
}