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
 */

namespace mod_perform\notification;

use coding_exception;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\models\activity\participant_instance;
use mod_perform\notification\internals\sink;

/**
 * factory class
 */
abstract class factory {
    /** @var loader|null */
    protected static $loader = null;

    /** @var clock|null */
    protected static $clock = null;

    /** @var sink|null|false */
    protected static $sink = false;

    /**
     * Create a broker instance.
     *
     * @param string $class_key
     * @return broker
     * @throws coding_exception
     */
    public static function create_broker(string $class_key): broker {
        $class = self::create_loader()->get_class_of($class_key);
        $inst = new $class();
        return $inst;
    }

    /**
     * Create a trigger instance.
     *
     * @param notification_model $notification
     * @return trigger
     */
    public static function create_trigger(notification_model $notification): trigger {
        return new trigger($notification->class_key);
    }

    /**
     * Create a condition instance.
     *
     * @param notification_model $notification
     * @return condition
     * @throws coding_exception
     */
    public static function create_condition(notification_model $notification): condition {
        $class_key = $notification->class_key;
        $condition_class = self::create_loader()->get_condition_class_of($class_key);
        if ($condition_class === null) {
            throw new coding_exception('condition is not supported for '. $class_key);
        }
        return new $condition_class(self::create_clock(), $notification->get_triggers_in_seconds(), $notification->last_run_at);
    }

    /**
     * Create a dealer instance.
     *
     * @param subject_instance_entity $subject_instance
     * @return dealer
     * @deprecated Since Totara 17.0
     */
    public static function create_dealer_on_subject_instance(subject_instance_entity $subject_instance): dealer {
        debugging('Do not use create_dealer_on_subject_instance - perform now uses centralised notifications', DEBUG_DEVELOPER);
        $participant_instances = $subject_instance->participant_instances->map_to(participant_instance::class)->all();
        return self::create_dealer_on_participant_instances($participant_instances);
    }

    /**
     * Create a dealer instance for manual participant selection.
     *
     * @param subject_instance_entity[] $subject_instances
     * @return dealer
     * @deprecated Since Totara 17.0
     */
    public static function create_dealer_on_subject_instances_for_manual_participants(array $subject_instances): dealer {
        debugging('Do not use create_dealer_on_subject_instances_for_manual_participants - perform now uses centralised notifications', DEBUG_DEVELOPER);
        // Need a different class because participant instances are not available.
        return new dealer_participant_selection($subject_instances);
    }

    /**
     * Create a dealer instance.
     *
     * @param participant_instance[]|participant_instance_entity[] $participant_instances
     * @return dealer
     * @deprecated Since Totara 17.0
     */
    public static function create_dealer_on_participant_instances(array $participant_instances): dealer {
        debugging('Do not use create_dealer_on_participant_instances - perform now uses centralised notifications', DEBUG_DEVELOPER);
        $instances = array_map(function ($e, $i) {
            if ($e instanceof participant_instance) {
                return $e;
            }
            if ($e instanceof participant_instance_entity) {
                return participant_instance::load_by_entity($e);
            }
            throw new coding_exception('unknown element at ' . $i);
        }, $participant_instances, array_keys($participant_instances));
        /** @var participant_instance[] $instances */
        return new dealer($instances);
    }

    /**
     * Create a mailer instance.
     *
     * @param notification_model $notification
     * @return mailer|null The mailer instance or null if the notification cannot be sent
     * @deprecated Since Totara 17.0
     */
    public static function create_mailer_on_notification(notification_model $notification): ?mailer {
        debugging('Do not use create_mailer_on_notification - perform now uses centralised notifications', DEBUG_DEVELOPER);
        if ($notification->active) {
            $mailer = new mailer($notification);
            // Optimise out when no recipients are enabled.
            if ($mailer->has_recipients()) {
                return $mailer;
            }
        }
        return null;
    }

    /**
     * Create a composer instance.
     *
     * @param string $class_key
     * @return composer
     */
    public static function create_composer(string $class_key): composer {
        self::create_loader()->ensure_class_key_exists($class_key);
        return new composer($class_key);
    }

    /**
     * Return the loader instance.
     *
     * @return loader
     * @throws coding_exception
     */
    public static function create_loader(): loader {
        if (self::$loader === null) {
            self::$loader = loader::create();
        }
        return self::$loader;
    }

    /**
     * Reset loader cache
     */
    public static function reset_loader(): void {
        self::$loader = null;
    }

    /**
     * Return the master clock.
     *
     * @return clock
     */
    public static function create_clock(): clock {
        if (self::$clock === null) {
            self::$clock = new clock();
        }
        return self::$clock;
    }

    /**
     * Reset clock
     */
    public static function reset_clock(): void {
        self::$clock = null;
    }

    /**
     * Return the master clock for testing.
     *
     * @param integer $bias time offset in seconds; (NOTE: bias is cumulative)
     * @return clock
     */
    public static function create_clock_with_time_offset(int $bias): clock {
        $current_bias = get_config('mod_perform', 'notification_time_travel') ?: 0;
        $new_bias = $current_bias + $bias;
        set_config('notification_time_travel', $new_bias, 'mod_perform');
        // Always override the singleton instance.
        self::$clock = new clock();
        return self::$clock;
    }

    /**
     * Return the notification message sink for testing.
     *
     * @return sink|null the singleton instance or null if not available
     */
    public static function create_sink(): ?sink {
        if (self::$sink === false) {
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                self::$sink = new sink();
            } else {
                self::$sink = null; // @codeCoverageIgnore
            }
        }
        return self::$sink;
    }

    /**
     * Reset sink;
     */
    public static function reset_sink(): void {
        self::$sink = false;
    }
}
