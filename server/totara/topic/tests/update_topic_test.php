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

class totara_topic_update_topic_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_topic(): void {
        global $DB;
        $this->setAdminUser();

        $topic = topic::create('xxooxxoo');
        $this->assertTrue($DB->record_exists('tag', ['id' => $topic->get_id()]));

        $topic->update("Bolobala");
        $this->assertEquals('Bolobala', $DB->get_field('tag', 'rawname', ['id' => $topic->get_id()]));
    }

    /**
     * @return void
     */
    public function test_udpate_topic_without_capability(): void {
        global $DB;
        $this->setAdminUser();

        $topic = topic::create('Bolobala');
        $id = $topic->get_id();

        $this->assertTrue($DB->record_exists('tag', ['id' => $id]));

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(topic_exception::class);
        $this->expectExceptionMessage(get_string('error:nocaptoupdate', 'totara_topic'));

        $topic->update("Bolobala part 2");
    }

    /**
     * @return void
     */
    public function test_update_topic_with_capability(): void {
        global $DB;
        $this->setAdminUser();

        $context = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $roles = get_roles_with_capability('totara/topic:update', CAP_ALLOW, $context);

        $role = reset($roles);
        role_assign($role->id, $user->id, $context->id);

        $topic = topic::create('Bolobala');
        $id = $topic->get_id();

        $this->assertTrue($DB->record_exists('tag', ['id' => $id]));

        $topic->update('Bolobala balabolo');
        $this->assertEquals('Bolobala balabolo', $DB->get_field('tag', 'rawname', ['id' => $id]));
    }

    /**
     * @return void
     */
    public function test_update_topic_with_case_change(): void {
        global $DB;
        $this->setAdminUser();

        $topic = topic::create('My Topic');
        $this->assertTrue($DB->record_exists('tag', ['id' => $topic->get_id()]));

        $topic->update("My topic");
        $this->assertSame('My topic', $DB->get_field('tag', 'rawname', ['id' => $topic->get_id()]));

        $topic2 = topic::create('Another Topic');
        $this->assertTrue($DB->record_exists('tag', ['id' => $topic2->get_id()]));

        $this->expectException(topic_exception::class);
        $this->expectExceptionMessage("The topic already exists in the system");
        $topic2->update("My Topic");
    }
}