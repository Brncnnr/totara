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

use totara_topic\provider\topic_provider;
use totara_topic\topic;
use totara_topic\exception\topic_exception;

class totara_topic_add_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_topic(): void {
        $this->setAdminUser();

        $topic = topic::create("Hello world");
        $this->assertEquals("Hello world", $topic->get_raw_name());
    }

    /**
     * @return void
     */
    public function test_add_topic_without_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(topic_exception::class);
        $this->expectExceptionMessage(get_string('error:nocaptoadd', 'totara_topic'));

        topic::create('Bolobala');
    }

    /**
     * @return void
     */
    public function test_add_topic_with_capability(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = context_system::instance();
        $roles = get_roles_with_capability('totara/topic:add', CAP_ALLOW, $context);
        $role = reset($roles);

        role_assign($role->id, $user->id, $context->id);

        $topic = topic::create("Bolobala");
        $id = $topic->get_id();

        $this->assertTrue($DB->record_exists('tag', ['id' => $id]));
    }

    /**
     * Test bulk topics creation.
     *
     * @return void
     */
    public function test_create_bulk_topics(): void {
        // Create user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Give user capability to create topics.
        $context = context_system::instance();
        $roles = get_roles_with_capability('totara/topic:add', CAP_ALLOW, $context);
        $role = reset($roles);
        role_assign($role->id, $user->id, $context->id);

        // 1 - User input all duplicated: should return the duplications and create none of the topics.
        $add_topics = [
            'one',
            'two',
            'three',
            'one',
            'two',
            'three',
        ];
        $duplicate_topics = topic::create_bulk($add_topics);
        $this->assertEquals(count($add_topics) / 2, count($duplicate_topics));

        $found_topics = [];
        foreach ($add_topics as $topic) {
            $find_me = topic_provider::find_by_name($topic);
            if ($find_me != null) {
                $found_topics[] = $find_me;
            }
        }
        $this->assertEquals(0, count($found_topics));

        // 2 - User input contains no duplicates: should return no duplicates and create the topics.
        $add_topics = [
            'one',
            'two',
            'three',
        ];
        $duplicate_topics = topic::create_bulk($add_topics);
        $this->assertEquals(0, count($duplicate_topics));

        $found_topics = [];
        foreach ($add_topics as $topic) {
            $find_me = topic_provider::find_by_name($topic);
            if ($find_me != null) {
                $found_topics[] = $find_me;
            }
        }
        $this->assertEquals(count($add_topics), count($found_topics));

        // 3 - User input contains duplicates of topics in already in system: should return duplicates.
        $dup_topics = [
            'one',
            'two',
            'three',
        ];
        $duplicate_topics = topic::create_bulk($dup_topics);
        $this->assertEquals(count($add_topics), count($duplicate_topics));

        $found_topics = [];
        foreach ($add_topics as $topic) {
            $find_me = topic_provider::find_by_name($topic);
            if ($find_me != null) {
                $found_topics[] = $find_me;
            }
        }
        $this->assertEquals(count($add_topics), count($found_topics));

        // 4 - User input contains blanks: should return no duplicates and create additional topics.
        $topics_with_blanks = [
            '',
            'four',
            '',
            'five',
        ];
        $duplicate_topics = topic::create_bulk($topics_with_blanks);
        $this->assertEquals(0, count($duplicate_topics));

        $all = array_merge($add_topics, $topics_with_blanks);
        $input = [];
        $found_topics = [];
        foreach ($all as $topic) {
            if ($topic !== '') {
                $input[] = $topic;
                $find_me = topic_provider::find_by_name($topic);
                if ($find_me != null) {
                    $found_topics[] = $find_me;
                }
            }
        }
        $this->assertEquals(count($input), count($found_topics));
    }


    /**
     * @return void
     */
    public function test_can_be_added(): void {
        $this->setAdminUser();

        $topic = topic::create("Hello world");
        self::assertTrue($topic->can_be_added('engage_article', 'engage_resource'));

        set_config('usetags', 0);
        self::assertFalse($topic->can_be_added('engage_article', 'engage_resource'));
    }
}