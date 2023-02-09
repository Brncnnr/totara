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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_article
 */

defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use core_phpunit\testcase;
use engage_article\totara_engage\interactor\article_interactor;
use GraphQL\Executor\ExecutionResult;
use totara_engage\access\access;
use totara_engage\interactor\interactor_factory;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;
use engage_article\testing\generator as article_generator;

class engage_article_resource_interactor_testcase extends testcase {
    use webapi_phpunit_helper;

    public function test_interactor(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        // Create article.
        /** @var article_generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        $public_article = $article_gen->create_public_article();

        // Interact as authenticated user.
        $user = $gen->create_user();
        $this->setUser($user);
        $interactor = article_interactor::create_from_accessible($public_article, $user->id);
        $this->assert_authenticated_user_interactor($interactor->to_array());
        // User can share public article
        $this->assertTrue($interactor->can_share());

        // Prevent user from sharing
        $role_user = $DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('engage/article:share', CAP_PREVENT, $role_user, $public_article->get_context_id());

        $this->assertFalse($interactor->can_share());

        // Interact as guest user.
        $this->setGuestUser();
        $interactor = article_interactor::create_from_accessible($public_article);
        $this->assert_guest_interactor($interactor->to_array());
    }

    /**
     * @return void
     */
    public function test_interactor_via_graphql(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        // Create article.
        /** @var article_generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        $public_article = $article_gen->create_public_article();

        // Interact as authenticated user.
        $user = $gen->create_user();
        $this->setUser($user);

        $ec = execution_context::create('ajax', 'totara_engage_get_interactor');
        $result = graphql::execute_operation(
            $ec,
            [
                'resource_id' => $public_article->get_id(),
            ]
        );

        $this->assert_graphql_result_success($result);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('interactor', $result->data);
        $interactor = $result->data['interactor'];
        $this->assert_authenticated_user_interactor($interactor);

        // Interact as guest user.
        $this->setGuestUser();
        $this->grant_guest_library_view_permission();

        $ec = execution_context::create('ajax', 'totara_engage_get_interactor');
        $result = graphql::execute_operation(
            $ec,
            [
                'resource_id' => $public_article->get_id(),
            ]
        );

        $this->assert_graphql_result_success($result);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('interactor', $result->data);
        $interactor = $result->data['interactor'];
        $this->assert_guest_interactor($interactor);
    }

    /**
     * @return void
     */
    public function test_interactor_factory(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        // Create article.
        /** @var article_generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        $public_article = $article_gen->create_public_article();

        // Get interactor.
        $interactor1 = interactor_factory::create('engage_article', $public_article->to_array());
        $interactor2 = interactor_factory::create_from_accessible($public_article);

        $this->assertEquals($interactor1, $interactor2);

        // Test validation on access field.
        try {
            $interactor = interactor_factory::create('engage_article', []);
            $this->fail('Expected coding_exception for no access field in resource data');
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: Resource access is required',
                $e->getMessage()
            );
        }

        // Test validation on userid field.
        try {
            $interactor = interactor_factory::create('engage_article', [
                'access' => access::PRIVATE,
            ]);
            $this->fail('Expected coding_exception for no userid field in resource data');
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: ID of user who owns the resource is required',
                $e->getMessage()
            );
        }
    }

    /**
     * @param array $interactor
     */
    private function assert_authenticated_user_interactor(array $interactor): void {
        $this->assertTrue($interactor['can_bookmark']);
        $this->assertTrue($interactor['can_comment']);
        $this->assertTrue($interactor['can_react']);
        $this->assertTrue($interactor['can_share']);
    }

    /**
     * @param array $interactor
     */
    private function assert_guest_interactor(array $interactor): void {
        $this->assertFalse($interactor['can_bookmark']);
        $this->assertFalse($interactor['can_comment']);
        $this->assertFalse($interactor['can_react']);
        $this->assertFalse($interactor['can_share']);
    }

    /**
     * @param ExecutionResult $result
     */
    private function assert_graphql_result_success(ExecutionResult $result): void {
        $errors = $result->errors;
        $this->assertEmpty($result->errors, empty($result->errors) ? '' : $errors[0]->getMessage());
    }

    /**
     * Allow guest to view engage library.
     */
    private function grant_guest_library_view_permission(): void {
        global $DB;
        $guest_role = $DB->get_record('role', array('shortname' => 'guest'));
        $context = context_user::instance(guest_user()->id);
        assign_capability('totara/engage:viewlibrary', CAP_ALLOW, $guest_role->id, $context);
    }

}