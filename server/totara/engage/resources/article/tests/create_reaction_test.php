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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use engage_article\totara_reaction\resolver\article_reaction_resolver;
use totara_engage\access\access;
use totara_reaction\reaction_helper;
use totara_reaction\resolver\resolver_factory;
use totara_webapi\graphql;

class engage_article_create_reaction_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_create_reaction_via_graphql(): void {
        global $USER, $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/engage/resources/article/classes/totara_reaction/resolver/article_reaction_resolver.php");

        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        $this->setAdminUser();

        // Create users.
        $users = $articlegen->create_users(2);

        $resolver = new article_reaction_resolver();
        resolver_factory::phpunit_set_resolver($resolver);

        // Create article.
        $this->setUser($users[0]);
        $user_0_public_article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);
        $user_0_private_article = $articlegen->create_article([
            'access' => access::PRIVATE
        ]);

        $this->setUser($users[1]);

        $variables = [
            'component' => $user_0_public_article->get_resourcetype(),
            'instanceid' => $user_0_public_article->get_id(),
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
                    'instanceid' => $user_0_public_article->get_id(),
                    'component' => $user_0_public_article->get_resourcetype(),
                    'area' => 'media',
                    'userid' => $USER->id
                ]
            )
        );

        $variables = [
            'component' => $user_0_private_article->get_resourcetype(),
            'instanceid' => $user_0_private_article->get_id(),
            'area' => 'media'
        ];

        $error = "Coding error detected, it must be fixed by a programmer: Cannot create the"
         . " reaction for instance '{$user_0_private_article->get_id()}' within area 'media'";
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

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(2);

        // Create article.
        $this->setUser($users[0]);
        $user_0_public_article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);
        $user_0_private_article = $articlegen->create_article([
            'access' => access::PRIVATE
        ]);

        // Confirm that user can like public article.
        $reaction = reaction_helper::create_reaction(
            $user_0_public_article->get_id(),
            $user_0_public_article->get_resourcetype(),
            'media',
            $users[1]->id
        );
        $this->assertInstanceOf(\totara_reaction\reaction::class, $reaction);

        // Confirm that user is not allowed to like private article.
        $this->expectException(
            'coding_exception',
            "Cannot create the reaction for instance '{$user_0_private_article->get_id()}' within area 'media'"
        );
        $reaction = reaction_helper::create_reaction(
            $user_0_private_article->get_id(),
            $user_0_private_article->get_resourcetype(),
            'media',
            $users[1]->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_with_area(): void {
        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create user.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        $this->setUser($user2);
        $user2_article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        $reaction = reaction_helper::create_reaction(
            $user2_article->get_id(),
            $user2_article->get_resourcetype(),
            'media',
            $user2->id
        );

        $this->assertInstanceOf(\totara_reaction\reaction::class, $reaction);

        // Create article.
        $this->setUser($user1);
        $user1_article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        $this->expectException(
            'coding_exception',
            "Coding error detected, it must be fixed by a programmer: Cannot create the reaction for instance '{$user1_article->get_id()}' within area 'meeeedddia'"
        );

        reaction_helper::create_reaction(
            $user1_article->get_id(),
            $user1_article->get_resourcetype(),
            'meeeedddia',
            $user1->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_private_resource(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article(['userid' => $user_one->id]);

        $instance_id = $article->get_id();
        $area = $article::REACTION_AREA;
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot create the reaction for instance '{$instance_id}' within area '{$area}'");
        reaction_helper::create_reaction(
            $instance_id,
            $article::get_resource_type(),
            $area,
            $user_one->id
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_for_restricted_and_public_resource(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article(['userid' => $user_one->id, 'access' => access::RESTRICTED]);

        $reaction = reaction_helper::create_reaction(
            $article->get_id(),
            $article::get_resource_type(),
            $article::REACTION_AREA,
            $user_one->id
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $article->get_id());
        self::assertEquals($reaction->get_component(), $article::get_resource_type());
        self::assertEquals($reaction->get_area(), $article::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);

        $article = $articlegen->create_article(['userid' => $user_one->id, 'access' => access::PUBLIC]);
        $reaction = reaction_helper::create_reaction(
            $article->get_id(),
            $article::get_resource_type(),
            $article::REACTION_AREA,
            $user_one->id
        );

        self::assertNotEmpty($reaction);
        self::assertEquals($reaction->get_instanceid(), $article->get_id());
        self::assertEquals($reaction->get_component(), $article::get_resource_type());
        self::assertEquals($reaction->get_area(), $article::REACTION_AREA);
        self::assertEquals($reaction->get_userid(), $user_one->id);
    }
}