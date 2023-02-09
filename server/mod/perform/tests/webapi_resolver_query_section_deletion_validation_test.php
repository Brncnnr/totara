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
 * @package mod_perform
 */

use mod_perform\hook\pre_section_deleted;
use mod_perform\models\activity\section;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class webapi_resolver_query_section_deletion_validation_testcase extends advanced_testcase {
    const QUERY = "mod_perform_section_deletion_validation";

    use webapi_phpunit_helper;

    public function test_query_successful() {
        $section = $this->create_test_data();

        $hook_sink = $this->redirectHooks();
        $hook_sink->clear();
        $hooks = $hook_sink->get_hooks();

        $this->assertCount(0, $hooks);

        $args = ['input' => ['section_id' => $section->id]];

        $this->resolve_graphql_query(self::QUERY, $args);

        $hooks = $hook_sink->get_hooks();

        $hook_exists = false;
        foreach ($hooks as $hook) {
            if ($hook instanceof pre_section_deleted) {
                $hook_exists = true;
                break;
            }
        }
        $this->assertTrue($hook_exists);
    }

    public function test_failed_without_correct_advanced_feature() {
        $section = $this->create_test_data();

        advanced_feature::disable('performance_activities');

        $this->expectExceptionMessage('Feature performance_activities is not available');

        $args = ['input' => ['section_id' => $section->id]];
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_failed_without_manage_capability() {
        $section = $this->create_test_data();

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(moodle_exception::class);

        $args = ['input' => ['section_id' => $section->id]];
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    /**
     * Create activity and section
     * 
     * @return section
     * @throws coding_exception
     */
    private function create_test_data() {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        return $section;
    }
}