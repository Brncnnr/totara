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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_engage
 */

use totara_engage\webapi\middleware\require_valid_recipients;
use totara_engage\exception\share_exception;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * @coversDefaultClass \totara_engage\webapi\middleware\require_valid_recipients
 *
 * @group totara_engage
 */
class totara_engage_webapi_middleware_require_valid_recipients_testcase extends advanced_testcase {

    /**
     * Created items
     *
     * @var array
     */
    public $data = [];

    protected function setUp(): void {
        $this->create_data();

        parent::setUp();
    }

    protected function tearDown(): void {
        parent::tearDown();

        $this->data = null;
    }

    /**
     * Test with wrong key - nothing should happen.
     */
    public function test_require_valid_recipients_wrong_key(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['validuser']->id, 'component' => 'core_user', 'area' => 'user']];
        $single_key_payload = payload::create($single_key_args, $context);

        // Wrong key here:
        $require_valid = new require_valid_recipients('foo');
        $result = $require_valid->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');
    }

    /**
     * Test same user.
     * TODO: this should probably fail.
     */
    public function test_require_valid_recipients_same_user(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['testuser']->id, 'component' => 'core_user', 'area' => 'user']];
        $single_key_payload = payload::create($single_key_args, $context);

        $require_valid = new require_valid_recipients($shares_key);
        $result = $require_valid->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');
    }

    /**
     * Test valid user.
     */
    public function test_require_valid_recipients_valid_user(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['validuser']->id, 'component' => 'core_user', 'area' => 'user']];
        $single_key_payload = payload::create($single_key_args, $context);

        $require_valid = new require_valid_recipients($shares_key);
        $result = $require_valid->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');
    }

    /**
     * Test deleted user.
     */
    public function test_require_valid_recipients_deleted_user(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['deleteduser']->id, 'component' => 'core_user', 'area' => 'user']];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage('Invalid recipient');
        $require_valid = new require_valid_recipients($shares_key);
        $require_valid->handle($single_key_payload, $next);
    }

    /**
     * Test nonexistent user.
     */
    public function test_require_valid_recipients_nonexistent_user(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['notuser']->id, 'component' => 'core_user', 'area' => 'user']];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage('Invalid recipient');
        $require_valid = new require_valid_recipients($shares_key);
        $require_valid->handle($single_key_payload, $next);
    }

    /**
     * Test valid workspace.
     */
    public function test_require_valid_recipients_valid_workspace(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['validworkspace']->id, 'component' => 'container_workspace', 'area' => 'library']];
        $single_key_payload = payload::create($single_key_args, $context);

        $require_valid = new require_valid_recipients($shares_key);
        $result = $require_valid->handle($single_key_payload, $next);
        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');
    }

    /**
     * Test invalid workspace.
     */
    public function test_require_valid_recipients_invalid_workspace(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['invalidworkspace']->id, 'component' => 'container_workspace', 'area' => 'library']];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage('You don\'t have permission to share to this workspace');
        $require_valid = new require_valid_recipients($shares_key);
        $result = $require_valid->handle($single_key_payload, $next);
    }

    /**
     * Test nonexistent workspace.
     */
    public function test_require_valid_recipients_nonexistent_workspace(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['notworkspace']->id, 'component' => 'container_workspace', 'area' => 'library']];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Invalid workspace with ID -42');
        $require_valid = new require_valid_recipients($shares_key);
        $require_valid->handle($single_key_payload, $next);
    }

    /**
     * Test course posing as workspace.
     */
    public function test_require_valid_recipients_course(): void {
        $expected = 86400;
        $shares_key = 'abc';
        [$context, $next] = $this->create_test_data($expected);

        $single_key_args = [$shares_key => ['instanceid' => $this->data['course']->id, 'component' => 'container_workspace', 'area' => 'library']];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('invalid workspace instanceid');
        $require_valid = new require_valid_recipients($shares_key);
        $require_valid->handle($single_key_payload, $next);
    }

    /**
     * Generates data shared by all tests.
     */
    private function create_data() {
        $this->setAdminUser();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);
        $this->data['testuser'] = $user;

        // Create some other user-likes
        $this->data['validuser'] = $this->getDataGenerator()->create_user();
        $this->data['deleteduser'] = $this->getDataGenerator()->create_user();
        delete_user($this->data['deleteduser']);
        $this->data['notuser'] = new \stdClass();
        $this->data['notuser']->id = -42;

        // Create some workspace-likes
        $this->data['validworkspace'] = $this->create_workspace('test workspace 1', $this->data['testuser']->id);
        $this->data['invalidworkspace'] = $this->create_workspace('test workspace 2', $this->data['validuser']->id);
        $this->data['notworkspace'] = new \stdClass();
        $this->data['notworkspace']->id = -42;

        // Create a course, which is kind of like a workspace
        $this->data['course'] = $this->getDataGenerator()->create_course();
    }

    /**
     * Generates individual test data.
     *
     * @param mixed $expected_result value to return as the result of the next
     *        chained "processor" after the require_activity handler.
     *
     * @return array (recipients array, graphql execution
     *         context, next handler to execute) tuple.
     */
    private function create_test_data($expected_result = null): array {
        $this->setUser($this->data['testuser']);

        $next = function (payload $payload) use ($expected_result): result {
            return new result($expected_result);
        };

        $context = execution_context::create("dev");
        return [$context, $next];
    }

    private function create_workspace($name, $userid, $summary = null, $private = false, $hidden = false): \container_workspace\workspace {
        /** @var \container_workspace\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $generator->create_workspace($name, $summary ?? "{$name} summary", FORMAT_PLAIN, $userid, $private, $hidden);
    }
}
