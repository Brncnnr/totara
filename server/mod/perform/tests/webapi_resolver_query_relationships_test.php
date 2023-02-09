<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use mod_perform\models\activity\activity;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\relationships
 * @group totara_core_relationship
 * @group perform
 */
class mod_perform_webapi_resolver_query_relationships_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'mod_perform_relationships';

    /**
     * @var activity
     */
    private $activity;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $this->activity = $generator->create_activity_in_container();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->activity = null;
    }

    public function test_resolve_query(): void {
        /** @var relationship[]|collection $results */
        $results = $this->resolve_graphql_query(self::QUERY, ['activity_id' => $this->activity->id]);
        $results = $results->all();
        $this->assertCount(9, $results);

        // make sure the order of relationships is correct
        $this->assertEquals(get_string('relationship_name_subject', 'totara_core'), $results[0]->get_name());
        $this->assertEquals(get_string('relationship_name_manager', 'totara_core'), $results[1]->get_name());
        $this->assertEquals(get_string('relationship_name_managers_manager', 'totara_core'), $results[2]->get_name());
        $this->assertEquals(get_string('relationship_name_appraiser', 'totara_core'), $results[3]->get_name());
        $this->assertEquals(get_string('relationship_name_perform_peer', 'mod_perform'), $results[4]->get_name());
        $this->assertEquals(get_string('relationship_name_perform_mentor', 'mod_perform'), $results[5]->get_name());
        $this->assertEquals(get_string('relationship_name_perform_reviewer', 'mod_perform'), $results[6]->get_name());
        $this->assertEquals(get_string('relationship_name_direct_report', 'totara_core'), $results[7]->get_name());
        $this->assertEquals(get_string('relationship_name_perform_external', 'mod_perform'), $results[8]->get_name());
    }

    public function test_require_manage_performance_activities_capability(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_query(self::QUERY, ['activity_id' => $this->activity->id]);
    }

    public function test_require_login(): void {
        self::setUser(null);

        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, ['activity_id' => $this->activity->id]);
    }

}
