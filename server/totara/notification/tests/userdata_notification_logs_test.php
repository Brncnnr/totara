<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Ben Fesili <ben.fesili@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notification_delivery_log as notification_delivery_log_entity;
use totara_notification\entity\notification_event_log as notification_event_log_entity;
use totara_notification\entity\notification_log as notification_log_entity;
use totara_notification\model\notification_delivery_log;
use totara_notification\model\notification_event_log;
use totara_notification\model\notification_log;
use totara_notification\testing\generator;
use totara_notification\userdata\notification_logs;
use totara_notification_mock_recipient as mock_recipient;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user as target_user;

class totara_notification_userdata_notification_logs_test extends testcase {

    /**
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_count(): void {
        $user = $this->setup_data();
        $target_user = new target_user($user);
        $extended_context = context_system::instance();
        $count = notification_logs::execute_count($target_user, $extended_context);

        self::assertSame(9, $count);
    }

    /**
     * @return stdClass
     * @throws coding_exception
     */
    private function setup_data(): stdClass {
        $generator = generator::instance();
        $purgeable_user = $this->getDataGenerator()->create_user();
        $non_purgeable_user = $this->getDataGenerator()->create_user();
        $extended_context = extended_context::make_system();

        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
            ]
        );

        for ($i = 0; $i < 3; $i++) {
            $notification_event_log = notification_event_log::create(
                mock_resolver::class,
                $extended_context,
                $purgeable_user->id,
                ['test-private-event-data'],
                'test-schedule',
                '',
                'test',
                [],
                false
            );
            $indirect_notification_event_log = notification_event_log::create(
                mock_resolver::class,
                $extended_context,
                $non_purgeable_user->id,
                ['test-private-event-data'],
                'test-schedule',
                '',
                'test',
                [],
                false
            );

            // where both notification_event_log and notification_log are purgeable users
            $purgeable_both = notification_log::create(
                $notification_event_log->get_id(),
                $preference->get_id(),
                $purgeable_user->id,
                false
            );
            notification_delivery_log::create(
                $purgeable_both->get_id(),
                'email',
                time(),
                'noreply@example.com',
                false
            );
            // where notification_event_log.subject_user_id is purgeable users but not recipient
            $purgeable_event_only = notification_log::create(
                $notification_event_log->get_id(),
                $preference->get_id(),
                $non_purgeable_user->id,
                false
            );
            notification_delivery_log::create(
                $purgeable_event_only->get_id(),
                'email',
                time(),
                'noreply@example.com',
                false
            );
            // where notification_log.recipient_user_id is purgeable users but not notification_event_log
            $purgeable_recipient_only = notification_log::create(
                $indirect_notification_event_log->get_id(),
                $preference->get_id(),
                $purgeable_user->id,
                false
            );
            notification_delivery_log::create(
                $purgeable_recipient_only->get_id(),
                'email',
                time(),
                'noreply@example.com',
                false
            );
            // neither log nor event log related to puregable user
            $non_purgeable_log = notification_log::create(
                $indirect_notification_event_log->get_id(),
                $preference->get_id(),
                $non_purgeable_user->id,
                time(),
                false
            );
            notification_delivery_log::create(
                $non_purgeable_log->get_id(),
                'email',
                time(),
                'noreply@example.com',
                false
            );
        }

        return $purgeable_user;
    }

    /**
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export(): void {
        $user = $this->setup_data();
        $target_user = new target_user($user);
        $extended_context = context_system::instance();
        $export = notification_logs::execute_export($target_user, $extended_context);

        self::assertSame(9, count($export->data['notifications']));
    }

    /**
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_purge() {
        $purgeable_user = $this->setup_data();
        $non_purgable_user = $this->setup_data();

        $target_user = new target_user($purgeable_user);
        $extended_context = context_system::instance();

        $log_count = notification_logs::execute_count($target_user, $extended_context);
        $this->assertSame(9, $log_count);

        $purged = notification_logs::execute_purge($target_user, $extended_context);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $purged);

        $purged_notification_event_logs = notification_event_log_entity::repository()
            ->where('subject_user_id', $purgeable_user->id)
            ->get();

        $non_purged_notification_event_logs = notification_event_log_entity::repository()
            ->where('subject_user_id', $non_purgable_user->id)
            ->get();

        $this->assertSame(3, $purged_notification_event_logs->count());
        $this->assertSame(3, $non_purged_notification_event_logs->count());
        foreach ($purged_notification_event_logs as $notification_event_log) {
            // only event data should be removed
            $this->assertNull($notification_event_log->event_data);
            $this->assertSame('test-schedule', $notification_event_log->schedule_type);
            $this->assertSame('test', $notification_event_log->display_string_key);
        }
        // ensure non purged users logs were not touched
        foreach ($non_purged_notification_event_logs as $notification_event_log) {
            $this->assertNotNull($notification_event_log->event_data);
            $event_data = json_decode($notification_event_log->event_data, true);
            $event_data = reset($event_data);
            $this->assertSame('test-private-event-data', $event_data);
            $this->assertSame('test-schedule', $notification_event_log->schedule_type);
            $this->assertSame('test', $notification_event_log->display_string_key);
        }

        $purged_users_notification_logs = notification_log_entity::repository()
            ->where('recipient_user_id', $purgeable_user->id)
            ->get();

        $non_purged_users_notification_logs = notification_log_entity::repository()
            ->where('recipient_user_id', $non_purgable_user->id)
            ->get();

        $purged_delivery_logs = notification_delivery_log_entity::repository()
            ->where_in('notification_log_id', $purged_users_notification_logs->pluck('id'))
            ->get();

        $non_purged_delivery_logs = notification_delivery_log_entity::repository()
            ->where_in('notification_log_id', $non_purged_users_notification_logs->pluck('id'))
            ->get();

        foreach ($purged_delivery_logs as $delivery_log) {
            $this->assertNull($delivery_log->address);
        }

        foreach ($non_purged_delivery_logs as $delivery_log) {
            $this->assertSame('noreply@example.com', $delivery_log->address);
        }

        $this->assertSame(6, $purged_delivery_logs->count());
    }

}