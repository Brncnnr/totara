<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use core_phpunit\testcase;
use totara_comment\comment;
use totara_comment\exception\comment_exception;
use totara_comment\loader\comment_loader;
use totara_comment\pagination\cursor;
use totara_comment\resolver_factory;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_comment_get_comments_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @param int $total
     * @return void
     */
    private function create_comments(int $instanceid, string $component, string $area, int $total = 20): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        $this->setUser($user);

        for ($i = 0; $i < $total; $i++) {
            comment::create(
                $instanceid,
                uniqid('random_'),
                $area,
                $component,
                FORMAT_MOODLE,
                $user->id
            );
        }
    }

    /**
     * @return void
     */
    public function test_get_comments(): void {
        $total = 20;
        $this->create_comments(42, 'totara_comment', 'xx_xx', $total);

        $cursor = new cursor();
        $cursor->set_limit(comment::ITEMS_PER_PAGE);

        // Comments on page 1.
        $comments = comment_loader::get_paginator(42, 'totara_comment', 'xx_xx', $cursor)->get_items()->all();
        $this->assertCount(comment::ITEMS_PER_PAGE, $comments);

        /** @var comment $comment */
        foreach ($comments as $comment) {
            $this->assertEquals('totara_comment', $comment->get_component());
            $this->assertEquals('xx_xx', $comment->get_area());
            $this->assertEquals(42, $comment->get_instanceid());
        }

        // Comments on page 2.
        $cursor->set_page(2);
        $comments = comment_loader::get_paginator(42, 'totara_comment', 'xx_xx', $cursor)->get_items()->all();
        $this->assertCount(($total - comment::ITEMS_PER_PAGE), $comments);
    }

    /**
     * @return void
     */
    public function test_get_comments_paginator(): void {
        $total = 50;

        $this->create_comments(15, 'totara_comment', 'xx_xx', $total);

        $cursor = new cursor();
        $paginator = comment_loader::get_paginator(15, 'totara_comment', 'xx_xx', $cursor);
        $comments = $paginator->get_items()->all();

        /** @var comment $comment */
        foreach ($comments as $comment) {
            $this->assertEquals(0, $comment->get_total_replies());
        }

        $this->assertEquals(50, $paginator->get_total());
        $this->assertEquals(comment::ITEMS_PER_PAGE, $paginator->get_current_cursor()->get_limit());
    }

    /**
     * @return void
     */
    public function test_get_comments_via_graphql(): void {
        $this->create_comments(42, 'totara_comment', 'xx_xx');

        /** @var totara_comment_default_resolver $resolver */
        $resolver = resolver_factory::create_resolver('totara_comment');
        $resolver->add_callback(
            'get_context_id',
            function (): int {
                global $USER;
                $context = context_user::instance($USER->id);

                return $context->id;
            }
        );

        // Log in as different user to the one who commented.
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        $variables = [
            'instanceid' => 42,
            'component' => 'totara_comment',
            'area' => "xx_xx"
        ];

        $ec = execution_context::create('ajax', 'totara_comment_get_comments');
        $result = graphql::execute_operation($ec, $variables);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('comments', $result->data);

        $comments = $result->data['comments'];
        $this->assertCount(comment::ITEMS_PER_PAGE, $comments);

        foreach ($comments as $comment) {
            $this->assertArrayHasKey('id', $comment);
            $this->assertArrayHasKey('user', $comment);
            $this->assertArrayHasKey('content', $comment);
            $this->assertArrayHasKey('timedescription', $comment);

            // Validate interactor.
            $this->assertArrayHasKey('interactor', $comment);
            $interactor = $comment['interactor'];
            $this->assertTrue($interactor['can_delete']);
            $this->assertTrue($interactor['can_report']);
            $this->assertTrue($interactor['can_update']);
            $this->assertTrue($interactor['can_reply']);
            $this->assertTrue($interactor['can_react']);
        }

        // Log in as guest.
        $this->setGuestUser();
        $this->grant_guest_library_view_permission();

        $ec = execution_context::create('ajax', 'totara_comment_get_comments');
        $result = graphql::execute_operation($ec, $variables);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('comments', $result->data);

        $comments = $result->data['comments'];
        $this->assertCount(comment::ITEMS_PER_PAGE, $comments);

        foreach ($comments as $comment) {
            $this->assertArrayHasKey('id', $comment);
            $this->assertArrayHasKey('user', $comment);
            $this->assertArrayHasKey('content', $comment);
            $this->assertArrayHasKey('timedescription', $comment);

            // Validate interactor.
            $this->assertArrayHasKey('interactor', $comment);
            $interactor = $comment['interactor'];
            $this->assertFalse($interactor['can_delete']);
            $this->assertTrue($interactor['can_report']);
            $this->assertFalse($interactor['can_update']);
            $this->assertFalse($interactor['can_reply']);
            $this->assertFalse($interactor['can_react']);
        }
    }

    /**
     * Verify that comments cannot be accessed across different tenants
     */
    public function test_get_comments_across_tenants_via_graphql(): void {
        /** @var \totara_tenant\testing\generator $tenancy_generator */
        $tenancy_generator = \totara_tenant\testing\generator::instance();
        $tenancy_generator->enable_tenants();

        $tenant1 = $tenancy_generator->create_tenant();
        $tenant2 = $tenancy_generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $tenancy_generator->migrate_user_to_tenant($user1->id, $tenant1->id);
        $tenancy_generator->migrate_user_to_tenant($user2->id, $tenant1->id);
        $tenancy_generator->migrate_user_to_tenant($user3->id, $tenant2->id);
        $tenancy_generator->migrate_user_to_tenant($user4->id, $tenant2->id);

        // User 1 & 2 are in tenant 1, User 3 & 4 are in tenant 2.
        // User 1 & 3 will create comments, while 2 & 4 will view
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $this->getDataGenerator()->get_plugin_generator('totara_comment');

        $comment1 = $comment_generator->create_comment(1, 'totara_comment', 'xx_xx', null, null, $user1->id);
        $comment2 = $comment_generator->create_comment(2, 'totara_comment', 'xx_xx', null, null, $user3->id);

        // We're going to override the resolver to return the correct tenant context
        // for each comment specified by the instanceid. This is a mock to avoid coupling to another
        // component like workspaces to figure out context.
        $callback = function (int $instanceid, string $area) use ($tenant1, $tenant2): int {
            $context = $instanceid == 1 ? $tenant1->context : $tenant2->context;
            return $context->id;
        };
        totara_comment_default_resolver::add_callback('get_context_id', $callback);

        // User 1 & 2 should see comment 1 (via instance1), and fail to see comment 2
        $this->assert_can_see_comment(1, $user1, $comment1);
        $this->assert_can_see_comment(1, $user2, $comment1);
        $this->assert_cannot_see_comment(2, $user1);
        $this->assert_cannot_see_comment(2, $user2);

        // User 3 & 4 should see comment 2 (via instance2) and fail to see comment 1
        $this->assert_can_see_comment(2, $user3, $comment2);
        $this->assert_can_see_comment(2, $user4, $comment2);
        $this->assert_cannot_see_comment(1, $user3);
        $this->assert_cannot_see_comment(1, $user4);
    }

    /**
     * Fetch the comments for the instance
     *
     * @param int $instance_id
     * @return mixed|null
     */
    private function get_comments_ajax(int $instance_id) {
        return $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'component' => 'totara_comment',
                'area' => 'xx_xx',
                'instanceid' => $instance_id
            ]
        );
    }

    /**
     * Call the graphql query & check that we see the comment that's expected
     *
     * @param int $instance_id
     * @param stdClass $user
     * @param comment $expected_comment
     */
    private function assert_can_see_comment(int $instance_id, stdClass $user, comment $expected_comment): void {
        $this->setUser($user);
        $results = $this->get_comments_ajax($instance_id);
        $this->assertNotNull($results);
        $this->assertCount(1, $results);
        /** @var comment $comment */
        $comment = current($results);
        $this->assertSame($comment->get_id(), $expected_comment->get_id());
    }

    /**
     * Call the graphql query & check that we cannot see any comments for the specific instance.
     *
     * @param int $instance_id
     * @param stdClass $user
     */
    private function assert_cannot_see_comment(int $instance_id, stdClass $user): void {
        $this->setUser($user);
        $exception = null;
        try {
            $this->get_comments_ajax($instance_id);
        } catch (comment_exception $ex) {
            $exception = $ex;
        }
        $this->assertNotNull($exception);
        $this->assertInstanceOf(comment_exception::class, $exception);
        $this->assertStringContainsString('Comment access denied', $exception->getMessage());
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