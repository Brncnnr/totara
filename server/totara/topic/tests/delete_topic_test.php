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
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\topic;
use totara_topic\exception\topic_exception;
use totara_topic\resolver\resolver_factory;
use totara_topic\topic_helper;
use totara_topic\hook\get_deleted_topic_usages;

class totara_topic_delete_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_topic(): void {
        global $DB;
        $this->setAdminUser();

        $topic = topic::create('Security');
        $id = $topic->get_id();

        $this->assertTrue($DB->record_exists('tag', ['id' => $id]));

        $topic->delete();
        $this->assertFalse($DB->record_exists('tag', ['id' => $id]));
    }

    /**
     * @return void
     */
    public function test_delete_topic_without_capability(): void {
        $this->setAdminUser();

        $topic = topic::create('xoopy');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(topic_exception::class);
        $this->expectExceptionMessage(get_string('error:nocaptodelete', 'totara_topic'));

        $topic->delete();
    }

    /**
     * @return void
     */
    public function test_delete_topic_with_capability(): void {
        global $DB;
        $this->setAdminUser();

        $ctx = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $roles = get_roles_with_capability('totara/topic:delete', CAP_ALLOW, $ctx);

        $role = reset($roles);
        role_assign($role->id, $user->id, $ctx->id);

        $topic = topic::create('Hellowlx');
        $id = $topic->get_id();

        $this->assertTrue($DB->record_exists('tag', ['id' => $id]));
        $this->setUser($user);

        $topic->delete();

        $this->assertFalse($DB->record_exists('tag', ['id' => $id]));
    }

    /**
     * @return void
     */
    public function test_delete_topic_with_usage(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/topic/tests/fixtures/topic_resolver.php");

        // Clearing all the adhoc tasks first.
        $this->executeAdhocTasks();
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_topic');
        $gen->add_default_area();

        $topic = $gen->create_topic();

        $resolver = new topic_resolver();
        $resolver->set_component("totara_topic");

        resolver_factory::phpunit_set_default_resolver($resolver);

        for ($i = 0; $i < 5; $i++) {
            topic_helper::add_topic_usage(
                $topic->get_id(),
                'totara_topic',
                'topic',
                ($i + 1)
            );
        }

        $topic->delete();

        $sink = $this->redirectHooks();
        $this->executeAdhocTasks();

        // Debugging messages are called, because of the miss-match from list of items.
        $debug_messages = $this->getDebuggingMessages();
        $this->assertCount(1, $debug_messages);

        $debug_message = reset($debug_messages);
        $this->assertSame(
            "There are missing record(s) for the item's id",
            $debug_message->message
        );

        $this->resetDebugging();


        $hooks = $sink->get_hooks();

        // There should be only one hook calls for now, as there is
        // only component topic.
        $this->assertCount(1, $hooks);

        /** @var get_deleted_topic_usages $hook */
        $hook = reset($hooks);

        $this->assertInstanceOf(get_deleted_topic_usages::class, $hook);
        $this->assertSame('totara_topic', $hook->get_component());
        $this->assertSame('topic', $hook->get_item_type());

        // Cast to integers if the result is not giving us integers. In order to do assertion properly.
        $instance_ids = $hook->get_instance_ids();
        $instance_ids = array_map(
            function ($instance_id): int {
                return (int) $instance_id;
            },
            $instance_ids
        );

        // Sort array of instances, otherwise the result will be randomly.
        sort($instance_ids);
        $this->assertEquals([1, 2, 3, 4, 5], $instance_ids);
    }
}