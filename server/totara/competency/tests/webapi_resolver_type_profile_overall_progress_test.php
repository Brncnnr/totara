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
use core\orm\collection;
use totara_competency\models\profile\filter;
use totara_competency\models\profile\item;
use totara_competency\models\profile\progress;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/totara_competency_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_profile_overall_progress_testcase extends totara_competency_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_profile_overall_progress';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only progress models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'users', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_sorting_testing_data(true);
        /** @var user $user */
        $user = $data['users']->first();

        $progress = progress::for($user->id);

        // resolve user
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'user', $progress);
        $this->assertInstanceOf(user::class, $user);
        $this->assertEquals($user->id, $result->id);

        // resolve items
        $items = $this->resolve_graphql_type(self::QUERY_TYPE, 'items', $progress);
        $this->assertInstanceOf(collection::class, $items);
        foreach ($items as $item) {
            $this->assertInstanceOf(item::class, $item);
        }

        // resolve latest_achievement
        $expect = $data['competencies']->first()->fullname;
        $this->assertEquals(
            $expect, $latest_achievement = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'latest_achievement', $progress, ['format' => format::FORMAT_HTML]
        )
        );
        $this->assertEquals(
            $expect, $latest_achievement = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'latest_achievement', $progress, ['format' => format::FORMAT_RAW]
        )
        );
        $this->assertEquals(
            $expect, $latest_achievement = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'latest_achievement', $progress, ['format' => format::FORMAT_PLAIN]
        )
        );
    }

    public function test_resolve_filters() {
        $data = $this->create_sorting_testing_data(true);
        /** @var user $user */
        $user = $data['users']->first();

        $progress = progress::for($user->id);

        $filters = $this->resolve_graphql_type(self::QUERY_TYPE, 'filters', $progress);

        foreach ($filters as $filter) {
            $this->assertInstanceOf(filter::class, $filter);
        }
    }
}