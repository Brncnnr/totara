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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use engage_survey\totara_reaction\resolver\survey_reaction_resolver;
use totara_engage\access\access;
use totara_engage\answer\answer_type;
use totara_reaction\reaction_helper;
use totara_reaction\resolver\resolver_factory;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

class engage_survey_create_reaction_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_reaction_via_graphql(): void {
        global $USER, $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/engage/resources/survey/classes/totara_reaction/resolver/survey_reaction_resolver.php");

        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        $this->setAdminUser();

        // Create users.
        $users = $surveygen->create_users(2);

        $resolver = new survey_reaction_resolver();
        resolver_factory::phpunit_set_resolver($resolver);

        // Create survey.
        $this->setUser($users[0]);
        $user_0_public_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);
        $user_0_private_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PRIVATE
        ]);

        $this->setUser($users[1]);

        $variables = [
            'component' => $user_0_public_survey->get_resourcetype(),
            'instanceid' => $user_0_public_survey->get_id(),
            'area' => 'media'
        ];

        $ec = execution_context::create('ajax', 'totara_reaction_create_like');
        $result = graphql::execute_operation($ec, $variables);

        $this->assertNotEmpty($result->data);
        $this->assertEmpty($result->errors);
        $this->assertArrayHasKey('reaction', $result->data);

        $this->assertTrue(
            $DB->record_exists(
                'reaction',
                [
                    'instanceid' => $user_0_public_survey->get_id(),
                    'component' => $user_0_public_survey->get_resourcetype(),
                    'area' => 'media',
                    'userid' => $USER->id
                ]
            )
        );

        $variables = [
            'component' => $user_0_private_survey->get_resourcetype(),
            'instanceid' => $user_0_private_survey->get_id(),
            'area' => 'media'
        ];

        $error = "Coding error detected, it must be fixed by a programmer: Cannot create the"
         . " reaction for instance '{$user_0_private_survey->get_id()}' within area 'media'";
        $ec = execution_context::create('ajax', 'totara_reaction_create_like');
        $result = graphql::execute_operation($ec, $variables);
        $this->assertNull($result->data);
        $this->assertSame($error, $result->errors[0]->getMessage());
    }

    /**
     * @return void
     */
    public function test_create_reaction(): void {
        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(2);

        // Create survey.
        $this->setUser($users[0]);
        $user_0_public_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);
        $user_0_private_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PRIVATE
        ]);

        // Confirm that user can like public survey.
        $reaction = reaction_helper::create_reaction(
            $user_0_public_survey->get_id(),
            $user_0_public_survey->get_resourcetype(),
            'media',
            $users[1]->id
        );
        $this->assertInstanceOf(\totara_reaction\reaction::class, $reaction);

        // Confirm that user is not allowed to like private survey.
        $this->expectException(
            'coding_exception',
            "Cannot create the reaction for instance '{$user_0_private_survey->get_id()}' within area 'media'"
        );
        $reaction = reaction_helper::create_reaction(
            $user_0_private_survey->get_id(),
            $user_0_private_survey->get_resourcetype(),
            'media',
            $users[1]->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_with_area(): void {
        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');


        // Create user.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        // Create survey.
        $this->setUser($user2);
        $user2_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $reaction = reaction_helper::create_reaction(
            $user2_survey->get_id(),
            $user2_survey->get_resourcetype(),
            'media',
            $user2->id
        );

        $this->assertInstanceOf(\totara_reaction\reaction::class, $reaction);

        $this->setUser($user1);
        $user1_survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $this->expectException(
            'coding_exception',
            "Coding error detected, it must be fixed by a programmer: Cannot create the reaction for instance '{$user1_survey->get_id()}' within area 'meeeedddia'"
        );

        reaction_helper::create_reaction(
            $user1_survey->get_id(),
            $user1_survey->get_resourcetype(),
            'meeeedddia',
            $user1->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_private_survey(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $this->setUser($user_one);
        $survey = $surveygen->create_survey();

        $instance_id = $survey->get_id();
        $area = $survey::REACTION_AREA;
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot create the reaction for instance '{$instance_id}' within area '{$area}'");
        reaction_helper::create_reaction(
            $instance_id,
            $survey::get_resource_type(),
            $area,
            $user_one->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_private_survey_via_graphql(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $this->setUser($user_one);
        $survey = $surveygen->create_survey();

        $instance_id = $survey->get_id();
        $area = $survey::REACTION_AREA;
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot create the reaction for instance '{$instance_id}' within area '{$area}'");
        $this->resolve_graphql_mutation(
            'totara_reaction_create',
            [
                'component' => $survey->get_resourcetype(),
                'instanceid' => $survey->get_id(),
                'area' => 'media'
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_restricted_and_public_survey(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $this->setUser($user_one);
        $survey = $surveygen->create_restricted_survey();

        $reaction = reaction_helper::create_reaction(
            $survey->get_id(),
            $survey::get_resource_type(),
            $survey::REACTION_AREA,
            $user_one->id
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $survey->get_id());
        self::assertEquals($reaction->get_component(), $survey::get_resource_type());
        self::assertEquals($reaction->get_area(), $survey::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);

        $survey = $surveygen->create_public_survey();
        $reaction = reaction_helper::create_reaction(
            $survey->get_id(),
            $survey::get_resource_type(),
            $survey::REACTION_AREA,
            $user_one->id
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $survey->get_id());
        self::assertEquals($reaction->get_component(), $survey::get_resource_type());
        self::assertEquals($reaction->get_area(), $survey::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_restricted_and_public_survey_via_graphql(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $this->setUser($user_one);
        $survey = $surveygen->create_restricted_survey();

        $reaction = $this->resolve_graphql_mutation(
            'totara_reaction_create',
            [
                'component' => $survey->get_resourcetype(),
                'instanceid' => $survey->get_id(),
                'area' => 'media'
            ]
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $survey->get_id());
        self::assertEquals($reaction->get_component(), $survey::get_resource_type());
        self::assertEquals($reaction->get_area(), $survey::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);

        $survey = $surveygen->create_public_survey();
        $reaction = $this->resolve_graphql_mutation(
            'totara_reaction_create',
            [
                'component' => $survey->get_resourcetype(),
                'instanceid' => $survey->get_id(),
                'area' => 'media'
            ]
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $survey->get_id());
        self::assertEquals($reaction->get_component(), $survey::get_resource_type());
        self::assertEquals($reaction->get_area(), $survey::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);
    }

}