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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\testing;

use core\orm\query\builder;
use totara_notification\entity\notification_delivery_log as notification_delivery_log_entity;
use totara_notification\entity\notification_event_log as notification_event_log_entity;
use totara_notification\entity\notification_log as notification_log_entity;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

trait notification_log_test_trait {
    /**
     * Verify the notification log entries
     * @param array $expected
     */
    private static function verify_notification_logs(array $expected) {
        $event_logs = builder::table(notification_event_log_entity::TABLE)->get();
        static::assertEquals(count($expected), count($event_logs));

        foreach ($expected as $expected_event_log) {
            $event_log = builder::table(notification_event_log_entity::TABLE)
                ->select(['id', 'resolver_class_name', 'event_data'])
                ->when(isset($expected_event_log['resolver_class_name']), function (builder $builder) use ($expected_event_log) {
                    $builder->where('resolver_class_name', $expected_event_log['resolver_class_name']);
                })
                ->when(isset($expected_event_log['context_id']), function (builder $builder) use ($expected_event_log) {
                    $builder->where('context_id', $expected_event_log['context_id']);
                })
                ->when(isset($expected_event_log['time_created']), function (builder $builder) use ($expected_event_log) {
                    $builder->where('time_created', $expected_event_log['time_created']);
                })
                ->when(isset($expected_event_log['event_data']), function (builder $builder) use ($expected_event_log) {
                    $builder->where('event_data', json_encode($expected_event_log['event_data']));
                })
                ->one();
            static::assertNotEmpty($event_log);
            $event_log_id = $event_log->id;

            if (empty($expected_event_log['logs'])) {
                $nlogs = notification_log_entity::repository()
                    ->where('notification_event_log_id', $event_log_id)
                    ->count();
                static::assertEquals(0, $nlogs);
            } else {
                foreach ($expected_event_log['logs'] as $expected_log) {
                    $nrecipients = notification_log_entity::repository()
                        ->where('notification_event_log_id', $event_log_id)
                        ->where('preference_id', $expected_log['preference_id'])
                        ->count();
                    static::assertEquals($expected_log['recipients'], $nrecipients);

                    $nchannels = builder::table(notification_delivery_log_entity::TABLE)
                        ->as('dlog')
                        ->join([notification_log_entity::TABLE, 'log'], 'dlog.notification_log_id', 'log.id')
                        ->where('log.notification_event_log_id', $event_log_id)
                        ->where('log.preference_id', $expected_log['preference_id'])
                        ->when(isset($expected_event_log['address']), function (builder $builder) use ($expected_event_log) {
                            $builder->where('dlog.address', $expected_event_log['address']);
                        })
                        ->count();
                    static::assertEquals($expected_log['channels'], $nchannels);
                }
            }

            if (isset($expected_event_log['event_name'])) {
                $resolver_class_name = $event_log->resolver_class_name;
                $event_data = json_decode($event_log->event_data, true);
                self::verify_notification_log_display_string($resolver_class_name, $event_data, $expected_event_log['event_name']);
            }
        }
    }

    /**
     * @param string|notifiable_event_resolver $resolver_class_name
     * @param array $event_data
     * @param string $expected_string
     * @return void
     */
    private static function verify_notification_log_display_string(string $resolver_class_name, array $event_data, string $expected_string) {
        $resolver = resolver_helper::instantiate_resolver_from_class($resolver_class_name, $event_data);

        $string_params = $resolver->get_notification_log_display_string_key_and_params($resolver_class_name, $event_data);
        $string_key = $string_params['key'];
        unset($string_params['key']);
        $actual_string = $resolver_class_name::format_event_log_display_string($string_key, $string_params);

        self::assertEquals($expected_string, $actual_string);
    }

    private static function clear_notification_logs() {
        notification_delivery_log_entity::repository()->delete();
        notification_delivery_log_entity::repository()->delete();
        notification_event_log_entity::repository()->delete();
    }

}
