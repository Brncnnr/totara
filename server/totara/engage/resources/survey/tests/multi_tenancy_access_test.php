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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access_manager;

class engage_survey_multi_tenancy_access_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_system_level_user_access_tenant_survey(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as second user to create a public survey.
        $this->setUser($user_two);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        // Log in as system level user and check if user two is able to see the survey or not.
        $this->setUser($user_one);
        self::assertTrue(access_manager::can_access($survey, $user_one->id));

        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($survey, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_tenant_user_access_system_survey(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        // Log in as system level user to create a survey.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant user and check if user two is able to access the systsem level resource.
        $this->setUser($user_two);
        self::assertTrue(access_manager::can_access($survey, $user_two->id));

        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($survey, $user_two->id));
    }

    /**
     * @return void
     */
    public function test_tenant_user_access_different_tenant_survey(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as tenant one user one to create a public survey and check if user two has
        // ability to access it or not.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        // Log in as user two and check if user two is able to see the survey.
        $this->setUser($user_two);
        self::assertFalse(access_manager::can_access($survey, $user_two->id));

        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($survey, $user_two->id));
    }

    /**
     * @return void
     */
    public function test_tenant_user_access_participant_survey(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        // Log in as system user to create a public survey and check if tenant user is able to access it or not.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        // Log in as tenant user and check if the user is able to access the survey.
        $this->setUser($user_two);
        self::assertTrue(access_manager::can_access($survey, $user_two->id));

        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($survey, $user_two->id));
    }

    /**
     * @return void
     */
    public function test_tenant_participant_access_member_survey(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        // Log in as tenant member to create a survey.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        // Log in as partipant - and check if you are able to see the survey.
        $this->setUser($user_two);
        self::assertTrue(access_manager::can_access($survey, $user_two->id));

        set_config('tenantsisolated', 1);
        self::assertTrue(access_manager::can_access($survey, $user_two->id));
    }
}