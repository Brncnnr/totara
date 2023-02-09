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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment;
use totara_comment\exception\comment_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_comment_get_replies_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * Verify that replies cannot be accessed across different tenants
     */
    public function test_get_replies_across_tenants_via_graphql(): void {
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

        $reply1 = $comment_generator->create_reply($comment1->get_id(), null, null, $user1->id);
        $reply2 = $comment_generator->create_reply($comment2->get_id(), null, null, $user3->id);

        // We're going to override the resolver to return the correct tenant context
        // for each comment specified by the instanceid. This is a mock to avoid coupling to another
        // component like workspaces to figure out context.
        $callback = function (int $instanceid, string $area) use ($tenant1, $tenant2): int {
            $context = $instanceid == 1 ? $tenant1->context : $tenant2->context;
            return $context->id;
        };
        totara_comment_default_resolver::add_callback('get_context_id', $callback);

        // User 1 & 2 should see reply 1, and fail to see reply 2
        $this->assert_can_see_replies($comment1->get_id(), $user1, $reply1);
        $this->assert_can_see_replies($comment1->get_id(), $user2, $reply1);
        $this->assert_cannot_see_replies($comment2->get_id(), $user1);
        $this->assert_cannot_see_replies($comment2->get_id(), $user2);

        // User 3 & 4 should see reply 2 and fail to see reply 1
        $this->assert_can_see_replies($comment2->get_id(), $user3, $reply2);
        $this->assert_can_see_replies($comment2->get_id(), $user4, $reply2);
        $this->assert_cannot_see_replies($comment1->get_id(), $user3);
        $this->assert_cannot_see_replies($comment1->get_id(), $user4);
    }

    /**
     * Fetch the comments for the instance
     *
     * @param int $comment_id
     * @return mixed|null
     */
    private function get_replies_ajax(int $comment_id) {
        return $this->resolve_graphql_query(
            'totara_comment_replies',
            [
                'commentid' => $comment_id
            ]
        );
    }

    /**
     * Call the graphql query & check that we see the comment that's expected
     *
     * @param int $comment_id
     * @param stdClass $user
     * @param comment $expected_reply
     */
    private function assert_can_see_replies(int $comment_id, stdClass $user, comment $expected_reply): void {
        $this->setUser($user);
        $results = $this->get_replies_ajax($comment_id);
        $this->assertNotNull($results);
        $this->assertCount(1, $results);
        /** @var comment $reply */
        $reply = current($results);
        $this->assertSame($reply->get_id(), $expected_reply->get_id());
    }

    /**
     * Call the graphql query & check that we cannot see any comments for the specific instance.
     *
     * @param int $comment_id
     * @param stdClass $user
     */
    private function assert_cannot_see_replies(int $comment_id, stdClass $user): void {
        $this->setUser($user);
        $exception = null;
        try {
            $this->get_replies_ajax($comment_id);
        } catch (comment_exception $ex) {
            $exception = $ex;
        }
        $this->assertNotNull($exception);
        $this->assertInstanceOf(comment_exception::class, $exception);
        $this->assertStringContainsString('Comment access denied', $exception->getMessage());
    }
}