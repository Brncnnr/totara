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

use core\format;
use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\item;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_profile_progress_item_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_profile_progress_item';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only progress models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'overall_progress', new stdClass());
    }

    public function test_resolve_successful() {
        [$item, $assignment] = $this->create_data();

        // resolve overall_progress
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'overall_progress', $item);
        $this->assertEquals($item->overall_progress, $result);

        // resolve name
        $this->assertEquals(
            'item one', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $item, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>item one</p>', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $item, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'item one', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $item, ['format' => format::FORMAT_PLAIN])
        );

        // resolve items
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'items', $item);
        $this->assertEquals($assignment->get_id(), $result->first()->id);
    }

    private function create_data() {
        $user = $this->getDataGenerator()->create_user();
        $fw = $this->generator()->create_framework();
        $comp = $this->generator()->create_competency(null, $fw);
        $assignment_gen = $this->generator()->assignment_generator();
        $assignment = new assignment($assignment_gen->create_user_assignment($comp->id, $user->id));
        $user_assignment = assignment_model::load_by_id($assignment->id);

        $item = new item('key one', '<p>item one</p>');
        $item->append_assignment($user_assignment);

        return [$item, $user_assignment];
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}