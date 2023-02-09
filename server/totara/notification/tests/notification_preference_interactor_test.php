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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_scheduled_aware_event_resolver as mock_schedule_resolver;

/**
 * @group totara_notification
 */
class totara_notification_notification_preference_interactor_testcase extends testcase {
    /**
     * @var stdClass
     */
    private $user_one;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $this->user_one = $generator->create_user();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user_one = null;
    }

    /**
     * @return void
     */
    public function test_check_cannot_manage_without_capability_and_no_permission_granted_by_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();

        $interactor = new notification_preference_interactor(
            extended_context::make_system(),
            $this->user_one->id
        );

        self::assertFalse($interactor->can_manage_notification_preferences());
        self::assertFalse($interactor->can_manage_notification_preferences_of_resolver(mock_schedule_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_manage_with_capability_and_no_permission_granted_by_the_resolver(): void {
        global $DB;
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $role_id = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'user']
        );

        $extended_context = extended_context::make_system();
        assign_capability(
            'totara/notification:managenotifications',
            CAP_ALLOW,
            $role_id,
            $extended_context->get_context_id(),
            true
        );

        $interactor = new notification_preference_interactor(
            $extended_context,
            $this->user_one->id
        );

        self::assertTrue($interactor->can_manage_notification_preferences());
        self::assertTrue($interactor->can_manage_notification_preferences_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_can_manage_without_capability_but_has_permissions_at_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $ec = extended_context::make_system();
        mock_resolver::set_permissions($ec, $this->user_one->id, true);

        $interactor = new notification_preference_interactor(
            $ec,
            $this->user_one->id
        );

        self::assertFalse($interactor->can_manage_notification_preferences());
        self::assertTrue($interactor->can_manage_notification_preferences_of_resolver(mock_resolver::class));
    }

    /**
     * @return void
     */
    public function test_check_cannot_manage_without_capability_and_no_permissions_at_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $ec = extended_context::make_system();
        mock_resolver::set_permissions($ec, $this->user_one->id, false);

        $interactor = new notification_preference_interactor(
            $ec,
            $this->user_one->id
        );

        self::assertFalse($interactor->can_manage_notification_preferences());
        self::assertFalse($interactor->can_manage_notification_preferences_of_resolver(mock_resolver::class));
    }

}
