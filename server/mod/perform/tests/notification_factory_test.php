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

use mod_perform\models\activity\notification as notification_model;
use mod_perform\notification\factory;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @coversDefaultClass \mod_perform\notification\factory
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_factory_testcase extends mod_perform_notification_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->mock_loader(null);
    }

    /**
     * @covers ::create_broker
     */
    public function test_create_broker() {
        $this->assertInstanceOf(mod_perform_mock_broker_one::class, factory::create_broker('mock_one'));
        $this->assertInstanceOf(mod_perform_mock_broker_two::class, factory::create_broker('mock_two'));
        $this->assertInstanceOf(mod_perform_mock_broker_three::class, factory::create_broker('mock_three'));
    }

    /**
     * @covers ::create_trigger
     */
    public function test_create_trigger() {
        $activity = $this->create_activity();
        $notification1 = notification_model::load_by_activity_and_class_key($activity, 'mock_one');
        $notification2 = notification_model::load_by_activity_and_class_key($activity, 'mock_two');
        $notification3 = notification_model::load_by_activity_and_class_key($activity, 'mock_three');
        $this->assertFalse(factory::create_trigger($notification1)->are_triggers_available());
        $this->assertTrue(factory::create_trigger($notification2)->are_triggers_available());
        $this->assertTrue(factory::create_trigger($notification3)->are_triggers_available());
    }

    /**
     * @covers ::create_condition
     */
    public function test_create_condition() {
        $activity = $this->create_activity();
        $notification1 = notification_model::load_by_activity_and_class_key($activity, 'mock_one')->activate();
        $notification2 = notification_model::load_by_activity_and_class_key($activity, 'mock_two')->activate();
        $notification3 = notification_model::load_by_activity_and_class_key($activity, 'mock_three')->activate();
        try {
            factory::create_condition($notification1);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('condition is not supported for mock_one', $ex->getMessage());
        }
        $this->assertInstanceOf(mod_perform_mock_condition_fail::class, factory::create_condition($notification2));
        $this->assertInstanceOf(mod_perform_mock_condition_pass::class, factory::create_condition($notification3));
    }

    /**
     * @covers ::create_loader
     */
    public function test_create_loader() {
        $loader1 = factory::create_loader();
        $loader2 = factory::create_loader();
        $this->assertSame($loader1, $loader2);
    }

    /**
     * @covers ::create_clock
     */
    public function test_create_clock() {
        $clock1 = factory::create_clock();
        $clock2 = factory::create_clock();
        $this->assertSame($clock1, $clock2);
    }
}
