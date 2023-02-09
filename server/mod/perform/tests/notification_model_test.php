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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\entity\activity\notification as notification_entity;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\notification_recipient;
use mod_perform\notification\trigger;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @covers \mod_perform\models\activity\notification
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_model_testcase extends mod_perform_notification_testcase {
    /**
     * @return array
     */
    public function data_create_success(): array {
        return [
            ['instance_created', 'Participant instance creation'],
        ];
    }

    /**
     * @param string $class_key
     * @param string $name_expected
     * @dataProvider data_create_success
     */
    public function test_create_success(string $class_key, string $name_expected) {
        $activity = $this->create_activity();
        $time = time();
        $notification = notification::load_by_activity_and_class_key($activity, $class_key);
        $this->assertEquals($activity->id, $notification->activity->get_id());
        $this->assertEquals($name_expected, $notification->name);
        $this->assertEquals(trigger::TYPE_ONCE, $notification->trigger_type);
        $this->assertTrue($notification->active);
        $this->assertEmpty($notification->triggers);

        $entity = new notification_entity($notification->id);
        $this->assertEquals($class_key, $entity->class_key);
        $this->assertEqualsWithDelta($time, $entity->created_at, 2);
        $this->assertEqualsWithDelta($time, $entity->updated_at, 2);
    }

    public function test_create_failure() {
        $activity = $this->create_activity();
        try {
            $notification = notification::create($activity, 'he_who_must_not_be_named');
            $this->fail('invalid_parameter_exception expected');
        } catch (\invalid_parameter_exception $ex) {
        }
    }

    public function test_recipients() {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $notification = notification::load_by_activity_and_class_key($activity, 'instance_created');
        $this->assertCount(0, $notification->recipients);
        $this->create_section_relationships($section, [constants::RELATIONSHIP_APPRAISER]);
        $this->assertCount(1, $notification->recipients);
        foreach ($notification->recipients as $recipient) {
            $this->assertFalse($recipient->active);
        }
        $this->create_section_relationships($section, [constants::RELATIONSHIP_SUBJECT]);
        $this->assertCount(2, $notification->recipients);
        foreach ($notification->recipients as $recipient) {
            $this->assertFalse($recipient->active);
        }
        $this->create_section_relationships($section, [constants::RELATIONSHIP_MANAGER]);
        $this->assertCount(3, $notification->recipients);
        foreach ($notification->recipients as $recipient) {
            $this->assertFalse($recipient->active);
        }
    }

    public function test_triggers() {
        $activity = $this->create_activity();
        $notification = notification::load_by_activity_and_class_key($activity, 'instance_created_reminder');
        $this->assertEquals(trigger::TYPE_AFTER, $notification->trigger_type);
        $this->assertEquals([1], $notification->triggers);
        $notification->set_triggers([3, 1, 4]);
        $this->assertEquals([1, 3, 4], $notification->triggers);

        $notification = notification::load_by_activity_and_class_key($activity, 'instance_created');
        $this->assertEquals(trigger::TYPE_ONCE, $notification->trigger_type);
        $this->assertEquals([], $notification->triggers);
        try {
            $notification->set_triggers([3, 1, 4]);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
        }
        $this->assertEquals([], $notification->triggers);
    }

    public function test_toggle(): void {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $this->create_section_relationships($section, [constants::RELATIONSHIP_APPRAISER]);

        $notification = notification::load_by_activity_and_class_key($activity, 'due_date');
        /** @var notification_recipient $recipient */
        $recipient = $notification->recipients->first();

        $this->assertFalse($notification->active);
        $this->assertFalse($recipient->active);

        $notification->toggle(true);
        $this->assertTrue($notification->refresh()->active);
        $this->assertFalse($recipient->refresh()->active);

        $notification->toggle(false);
        $this->assertFalse($notification->refresh()->active);
        $this->assertFalse($recipient->refresh()->active);
        $notification->activate();
        $this->assertTrue($notification->refresh()->active);
        $this->assertFalse($recipient->refresh()->active);

        $notification->deactivate();
        $this->assertFalse($notification->refresh()->active);
        $this->assertFalse($recipient->refresh()->active);
    }
}
