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

use mod_perform\models\activity\details\subject_instance_notification;
use mod_perform\models\activity\notification;
use mod_perform\notification\broker;
use mod_perform\notification\brokers\due_date;
use mod_perform\notification\brokers\due_date_reminder;
use mod_perform\notification\brokers\instance_created_reminder;
use mod_perform\notification\brokers\overdue_reminder;
use mod_perform\notification\condition;
use mod_perform\notification\conditions\after_midnight;
use mod_perform\notification\conditions\days_before;
use mod_perform\notification\recipient;
use mod_perform\notification\trigger;
use mod_perform\notification\triggerable;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_broker_testcase extends mod_perform_notification_testcase {
    /** @var condition */
    private $condition_fail;

    /** @var condition */
    private $condition_pass;

    /** @var subject_instance_notification */
    private $sin_empty;

    /** @var subject_instance_notification */
    private $sin_due_not_set;

    /** @var subject_instance_notification */
    private $sin_due_set;

    /** @var subject_instance_notification */
    private $sin_creation_not_set;

    /** @var subject_instance_notification */
    private $sin_creation_set;

    public function setUp(): void {
        parent::setUp();
        $this->condition_fail = new mod_perform_mock_condition_fail();
        $this->condition_pass = new mod_perform_mock_condition_pass();
        $this->sin_empty = $this->mock_subject_instance_notification(['due_date' => 0]);
        $this->sin_due_not_set = $this->mock_subject_instance_notification(['due_date' => 0]);
        $this->sin_due_set = $this->mock_subject_instance_notification(['due_date' => 1]);
        $this->sin_creation_not_set = $this->mock_subject_instance_notification(['instance_created_at' => 0]);
        $this->sin_creation_set = $this->mock_subject_instance_notification(['instance_created_at' => 1]);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->condition_fail = $this->condition_pass = null;
        $this->sin_empty = $this->sin_due_not_set = $this->sin_due_set = null;
        $this->sin_creation_not_set = $this->sin_creation_set = null;
    }

    /**
     * Instantiate subject_instance_notification.
     *
     * @param array|stdClass $record
     * @return subject_instance_notification
     */
    private function mock_subject_instance_notification($record): subject_instance_notification {
        $class = new ReflectionClass(subject_instance_notification::class);
        $ctor = $class->getConstructor();
        $ctor->setAccessible(true);
        $object = $class->newInstanceWithoutConstructor();
        $ctor->invoke($object, (object)$record);
        return $object;
    }

    public function test_get_default_triggers() {
        $this->mock_loader([
            'mock_no_triggerable' => [
                'class' => mod_perform_mock_broker_no_triggerable::class,
                'name' => 'mock w/o triggerable',
                'condition' => days_before::class,
                'trigger_type' => trigger::TYPE_BEFORE,
                'recipients' => recipient::ALL,
                'trigger_label' => ['cancel'],
            ],
            'mock_triggerable' => [
                'class' => mod_perform_mock_broker_triggerable::class,
                'name' => 'mock mock',
                'condition' => after_midnight::class,
                'trigger_type' => trigger::TYPE_AFTER,
                'recipients' => recipient::ALL,
                'trigger_label' => ['ok'],
            ],
        ]);

        $activity = $this->create_activity();
        $notification1 = notification::load_by_activity_and_class_key($activity, 'mock_no_triggerable')->activate();
        $notification2 = notification::load_by_activity_and_class_key($activity, 'mock_triggerable')->activate();
        $this->assertEquals([3, 1, 4], $notification1->get_triggers());
        $this->assertEquals([259200, 86400, 345600], $notification1->get_triggers_in_seconds());
        $this->assertEquals([2, 7, 1], $notification2->get_triggers());
        $this->assertEquals([172800, 604800, 86400], $notification2->get_triggers_in_seconds());
    }

    /**
     * @covers \mod_perform\notification\brokers\instance_created_reminder
     */
    public function test_is_triggerable_now_of_instance_created_reminder() {
        $instance_created_reminder = new instance_created_reminder();
        $this->assertFalse($instance_created_reminder->is_triggerable_now($this->condition_fail, $this->sin_empty));
        $this->assertFalse($instance_created_reminder->is_triggerable_now($this->condition_fail, $this->sin_creation_not_set));
        $this->assertFalse($instance_created_reminder->is_triggerable_now($this->condition_fail, $this->sin_creation_set));
        $this->assertFalse($instance_created_reminder->is_triggerable_now($this->condition_pass, $this->sin_empty));
        $this->assertFalse($instance_created_reminder->is_triggerable_now($this->condition_pass, $this->sin_creation_not_set));
        $this->assertTrue($instance_created_reminder->is_triggerable_now($this->condition_pass, $this->sin_creation_set));
    }

    /**
     * @covers \mod_perform\notification\brokers\due_date
     */
    public function test_is_triggerable_now_of_due_date() {
        $due_date = new due_date();
        $this->assertFalse($due_date->is_triggerable_now($this->condition_fail, $this->sin_empty));
        $this->assertFalse($due_date->is_triggerable_now($this->condition_fail, $this->sin_due_not_set));
        $this->assertFalse($due_date->is_triggerable_now($this->condition_fail, $this->sin_due_set));
        $this->assertFalse($due_date->is_triggerable_now($this->condition_pass, $this->sin_empty));
        $this->assertFalse($due_date->is_triggerable_now($this->condition_pass, $this->sin_due_not_set));
        $this->assertTrue($due_date->is_triggerable_now($this->condition_pass, $this->sin_due_set));
    }

    /**
     * @covers \mod_perform\notification\brokers\due_date_reminder
     */
    public function test_is_triggerable_now_of_due_date_reminder() {
        $due_date_reminder = new due_date_reminder();
        $this->assertFalse($due_date_reminder->is_triggerable_now($this->condition_fail, $this->sin_empty));
        $this->assertFalse($due_date_reminder->is_triggerable_now($this->condition_fail, $this->sin_due_not_set));
        $this->assertFalse($due_date_reminder->is_triggerable_now($this->condition_fail, $this->sin_due_set));
        $this->assertFalse($due_date_reminder->is_triggerable_now($this->condition_pass, $this->sin_empty));
        $this->assertFalse($due_date_reminder->is_triggerable_now($this->condition_pass, $this->sin_due_not_set));
        $this->assertTrue($due_date_reminder->is_triggerable_now($this->condition_pass, $this->sin_due_set));
    }

    /**
     * @covers \mod_perform\notification\brokers\overdue_reminder
     */
    public function test_is_triggerable_now_of_overdue_reminder() {
        $overdue_reminder = new overdue_reminder();
        $this->assertFalse($overdue_reminder->is_triggerable_now($this->condition_fail, $this->sin_empty));
        $this->assertFalse($overdue_reminder->is_triggerable_now($this->condition_fail, $this->sin_due_not_set));
        $this->assertFalse($overdue_reminder->is_triggerable_now($this->condition_fail, $this->sin_due_set));
        $this->assertFalse($overdue_reminder->is_triggerable_now($this->condition_pass, $this->sin_empty));
        $this->assertFalse($overdue_reminder->is_triggerable_now($this->condition_pass, $this->sin_due_not_set));
        $this->assertTrue($overdue_reminder->is_triggerable_now($this->condition_pass, $this->sin_due_set));
    }
}

/**
 * @codeCoverageIgnore
 */
class mod_perform_mock_broker_no_triggerable implements broker {
    public function get_default_triggers(): array {
        return [3 * DAYSECS, 1 * DAYSECS, 4 * DAYSECS];
    }
}

/**
 * @codeCoverageIgnore
 */
class mod_perform_mock_broker_triggerable implements broker, triggerable {
    public function get_default_triggers(): array {
        return [2 * DAYSECS, 7 * DAYSECS, 1 * DAYSECS];
    }

    public function is_triggerable_now(condition $condition, subject_instance_notification $record): bool {
        return false;
    }
}
