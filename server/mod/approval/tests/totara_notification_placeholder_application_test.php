<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\controllers\application\view as view_controller;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\totara_notification\placeholder\application as application_placeholder_group;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_placeholder_application_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_get_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, application_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'title',
                'id_number',
                'current_approval_level',
                'current_stage_name',
                'type',
                'title_linked',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        // Create an application.
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $core_generator = $this->getDataGenerator();
        $user_record = $core_generator->create_user();
        $user_entity = new user($user_record);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $placeholder_group = application_placeholder_group::from_model($application);

        // Check each placeholder.
        self::assertEquals($application->title, $placeholder_group->do_get('title'));
        self::assertEquals($application->id_number, $placeholder_group->do_get('id_number'));
        self::assertEquals($application->current_approval_level->name, $placeholder_group->do_get('current_approval_level'));
        self::assertEquals($application->current_stage->name, $placeholder_group->do_get('current_stage_name'));
        self::assertEquals($application->workflow_type->name, $placeholder_group->do_get('type'));
        self::assertStringContainsString(
            view_controller::get_base_url() . "?application_id=$application->id",
            $placeholder_group->do_get('title_linked')
        );
        self::assertStringContainsString(
            'Testing',
            $placeholder_group->do_get('title_linked')
        );

        // Check empty current approval level.
        $application = $this->create_application($workflow, $assignment, $user_entity);
        $placeholder_group = application_placeholder_group::from_model($application);
        self::assertEquals('', $placeholder_group->do_get('current_approval_level'));
    }

    public function test_not_available(): void {
        $placeholder_group = new application_placeholder_group(null);
        self::assertEquals('', $placeholder_group->get('title'));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The application model is empty');
        $placeholder_group->do_get('title');
    }

    public function test_instances_are_cached(): void {
        global $DB;

        list($workflow, , $assignment) = $this->create_workflow_and_assignment();

        $core_generator = $this->getDataGenerator();

        $user_record = $core_generator->create_user();
        $user_entity = new user($user_record);
        $application1 = $this->create_application($workflow, $assignment, $user_entity);

        $user_record = $core_generator->create_user();
        $user_entity = new user($user_record);
        $application2 = $this->create_application($workflow, $assignment, $user_entity);

        $query_count = $DB->perf_get_reads();
        application_placeholder_group::from_id($application1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        application_placeholder_group::from_id($application1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        application_placeholder_group::from_id($application2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        application_placeholder_group::from_id($application1->id);
        application_placeholder_group::from_id($application2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}
