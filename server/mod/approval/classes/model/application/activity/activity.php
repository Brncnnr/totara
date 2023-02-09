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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\activity;

use coding_exception;
use core\entity\user;
use core_component;
use core_user\profile\user_field_resolver;
use html_writer;
use invalid_parameter_exception;
use lang_string;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use stdClass;

/**
 * Base activity.
 */
abstract class activity {
    /** @var string[]|null */
    private static $class_map = null;

    /** @var lang_string|string */
    private $description = '';

    /** @var boolean */
    private $from_system = false;

    /** @var boolean */
    private $to_system = false;

    /**
     * System action.
     *
     * @param string $lang_key Language string key
     * @param stdClass|array|null $a The `$a` parameter
     */
    final protected function by_system(string $lang_key, $a = null): void {
        $this->from_system = true;
        $this->to_system = true;
        $this->description = new lang_string($lang_key, 'mod_approval', $a);
    }

    /**
     * User action.
     * The {$a->user} placeholder will be replaced with a user profile link with the user's full name.
     *
     * @param string $lang_key Language string key
     * @param user $user
     * @param stdClass|array|null $a The `$a` parameter
     */
    final protected function by_user(string $lang_key, user $user, $a = null): void {
        $this->by_or_to_user($lang_key, $user, false, $a);
    }

    /**
     * System action to user.
     * The {$a->user} placeholder will be replaced with a user profile link with the user's full name.
     *
     * @param string $lang_key Language string key
     * @param user $user
     * @param stdClass|array|null $a The `$a` parameter
     */
    final protected function to_user(string $lang_key, user $user, $a = null): void {
        $this->by_or_to_user($lang_key, $user, true, $a);
    }

    /**
     * @param string $lang_key
     * @param user $user
     * @param boolean $is_system
     * @param stdClass|array|null $a The `$a` parameter
     */
    private function by_or_to_user(string $lang_key, user $user, bool $is_system, $a = null): void {
        $resolver = user_field_resolver::from_record((object)$user->get_attributes_raw());
        $url = $resolver->get_field_value('profileurl');
        if (!empty($url)) {
            $name = $resolver->get_field_value('fullname');
            $link = html_writer::link($url, $name);
        } else {
            $link = $user->fullname;
        }
        $a = (array)($a ?? []);
        $a['user'] = $link;
        $this->from_system = $is_system;
        $this->to_system = !$is_system;
        $this->description = new lang_string($lang_key, 'mod_approval', $a);
    }

    /**
     * @return array of [type => class, ...]
     */
    private static function get_class_map(): array {
        if (self::$class_map === null) {
            $class_map = [];
            $classes = core_component::get_namespace_classes('model\\application\\activity', self::class, 'mod_approval');
            /** @var activity $class */
            foreach ($classes as $class) {
                $type = $class::get_type();
                if (isset($class_map[$type])) {
                    throw new coding_exception(
                        "Activity type {$type} is already taken by {$class_map[$type]}, being overridden by {$class}"
                    );
                }
                $class_map[$type] = $class;
            }
            self::$class_map = $class_map;
        }
        return self::$class_map;
    }

    /**
     * Get the class path from the activity type.
     *
     * @param integer $type The application_activity type
     * @return string
     * @internal Do not publish this function.
     */
    public static function from_type(int $type): string {
        $classes = self::get_class_map();
        if (isset($classes[$type])) {
            return $classes[$type];
        }
        throw new invalid_parameter_exception("Activity type {$type} is not defined");
    }

    /**
     * Create an activity instance.
     *
     * @param application_activity $activity
     * @return self
     */
    final public static function from_activity(application_activity $activity): self {
        $class = self::from_type($activity->activity_type);
        return new $class($activity);
    }

    /**
     * Gets label associated with application_activity type.
     *
     * @param integer $type The application_activity type
     * @return lang_string
     */
    final public static function label(int $type): lang_string {
        /** @var activity $class */
        $class = self::from_type($type);
        return new lang_string($class::get_label_key(), 'mod_approval');
    }

    /**
     * Gets label associated with this activity.
     *
     * @return lang_string
     */
    final public function get_label(): lang_string {
        return new lang_string(static::get_label_key(), 'mod_approval');
    }

    /**
     * Gets description about this activity.
     *
     * @return string
     */
    final public function get_description(): string {
        return $this->description;
    }

    /**
     * Is this action taken by the system?
     *
     * @return boolean
     */
    final public function from_system(): bool {
        return $this->from_system;
    }

    /**
     * Is this action meant for a user?
     *
     * @return boolean
     */
    final public function for_system(): bool {
        return $this->to_system;
    }

    /**
     * Gets application_activity type.
     *
     * @return integer
     */
    abstract public static function get_type(): int;

    /**
     * Gets the language string key of the label.
     *
     * @return string
     */
    abstract protected static function get_label_key(): string;

    /**
     * Validates the activity info.
     *
     * @param array $info
     * @return boolean
     */
    public static function is_valid_activity_info(array $info): bool {
        // Must be empty by default.
        return empty($info);
    }

    /**
     * Override to fire off an event when this activity is recorded.
     *
     * @param application $application
     * @param int|null $actor_id ID of the user causing the activity, or null for system trigger (e.g. cron task)
     * @param array $activity_info
     */
    public static function trigger_event(application $application, ?int $actor_id, array $activity_info): void {
    }
}
