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
use mod_perform\notification\exceptions\class_key_not_available;

/**
 * The loader class.
 */
class loader {
    public const ALL = 0;
    public const HAS_TRIGGERS = 1;
    public const HAS_CONDITION = 2;

    /** @var array */
    private $notifications;

    /** @var string[] */
    private static $mandatory_fields = [
        'class',
        'name',
        'trigger_type',
        'recipients',
    ];

    /** @var string[] */
    private static $mandatory_depends = [
        'trigger_label' => ['trigger_type', trigger::TYPE_ONCE],
        'condition' => ['trigger_type', trigger::TYPE_ONCE],
    ];

    /**
     * Private constructor to enforce the factory pattern.
     *
     * @param array $notifications
     */
    private function __construct(array $notifications) {
        self::validate($notifications);
        $this->notifications = $notifications;
    }

    /**
     * Validate the notifications array.
     *
     * @param array $notifications
     */
    private static function validate(array $notifications): void {
        if (empty($notifications)) {
            throw new coding_exception('notification data is empty');
        }
        foreach ($notifications as $class_key => $notif) {
            foreach (self::$mandatory_fields as $mandatory_field) {
                if (!isset($notif[$mandatory_field])) {
                    throw new coding_exception("{$mandatory_field} is missing for {$class_key}");
                }
            }
            foreach (self::$mandatory_depends as $mandatory_field => $mandatory_dependency) {
                $value = $notif[$mandatory_dependency[0]];
                if ($value != $mandatory_dependency[1] && !isset($notif[$mandatory_field])) {
                    throw new coding_exception("{$mandatory_field} is missing for {$class_key}");
                }
            }
            if (!$notif['recipients']) {
                throw new coding_exception("no recipients are set for {$class_key}");
            }
        }
    }

    /**
     * Create a loader instance.
     * **Call factory::create_loader() instead of this function.**
     *
     * @param array|null $notifications array or null to load from notifications.php
     * @return self
     * @internal
     */
    public static function create(?array $notifications = null): self {
        global $CFG;
        if ($notifications === null) {
            require($CFG->dirroot . '/mod/perform/db/notifications.php');
        }
        return new self($notifications);
    }

    /**
     * Get all the broker class names.
     *
     * @return array of [class_key => fully_qualified_class_path]
     */
    public function get_classes(): array {
        return array_map(function ($notif) {
            return $notif['class'];
        }, $this->notifications);
    }

    /**
     * Get all the registered class keys.
     *
     * @param integer $filter ALL, HAS_CONDITION or HAS_TRIGGERS
     * @return string[] of class_key's
     */
    public function get_class_keys(int $filter = self::ALL): array {
        if ($filter == self::ALL) {
            $notifications = $this->notifications;
        } else {
            $notifications = array_filter($this->notifications, function ($notif) use ($filter) {
                if (($filter & self::HAS_TRIGGERS) === self::HAS_TRIGGERS && $notif['trigger_type'] != trigger::TYPE_ONCE) {
                    return true;
                }
                if (($filter & self::HAS_CONDITION) === self::HAS_CONDITION && !empty($notif['condition'])) {
                    return true;
                }
                return false;
            });
        }
        return array_keys($notifications);
    }

    /**
     * Get the fully-qualified class name of the broker.
     *
     * @param string $class_key
     * @return string
     * @throws coding_exception
     */
    public function get_class_of(string $class_key): string {
        $info = $this->get_information($class_key);
        return $info['class'];
    }

    /**
     * Get the localised name of the broker.
     *
     * @param string $class_key
     * @return string
     * @throws coding_exception
     */
    public function get_name_of(string $class_key): string {
        $info = $this->get_information($class_key);
        return get_string($info['name'][0], $info['name'][1] ?? '');
    }

    /**
     * Get the type of event triggers.
     *
     * @param string $class_key
     * @return integer one of trigger constants
     */
    public function get_trigger_type_of(string $class_key): int {
        $info = $this->get_information($class_key);
        return $info['trigger_type'] ?? trigger::TYPE_ONCE;
    }

    /**
     * Get the label text attached to event triggers.
     *
     * @param string $class_key
     * @return string|null localised label text or null if triggers are not supported
     */
    public function get_trigger_label_of(string $class_key): ?string {
        $info = $this->get_information($class_key);
        $trigger_type = $this->get_trigger_type_of($class_key);
        if ($trigger_type === trigger::TYPE_ONCE) {
            return null;
        }
        $label = get_string($info['trigger_label'][0], $info['trigger_label'][1] ?? '');
        if ($trigger_type === trigger::TYPE_BEFORE) {
            return get_string('trigger_before', 'mod_perform', ['name' => $label]);
        }
        if ($trigger_type === trigger::TYPE_AFTER) {
            return get_string('trigger_after', 'mod_perform', ['name' => $label]);
        }
        throw new coding_exception('unsupported trigger type');
    }

    /**
     * Get the fully-qualified class name of the condition.
     *
     * @param string $class_key
     * @return string|null
     * @throws coding_exception
     */
    public function get_condition_class_of(string $class_key): ?string {
        $info = $this->get_information($class_key);
        return $info['condition'] ?? null;
    }

    /**
     * Return whether a broker can provide trigger events.
     *
     * @param string $class_key
     * @return boolean
     * @throws coding_exception
     */
    public function support_triggers(string $class_key): bool {
        return $this->get_trigger_type_of($class_key) !== trigger::TYPE_ONCE;
    }

    /**
     * Return the possible recipient relationships.
     *
     * @param string $class_key
     * @return integer OR combined recipient constants
     * @throws coding_exception
     */
    public function get_possible_recipients_of(string $class_key): int {
        $info = $this->get_information($class_key);
        return $info['recipients'];
    }

    /**
     * @param string $class_key
     * @return boolean
     */
    public function are_all_possible_recipients(string $class_key): bool {
        $info = $this->get_information($class_key);
        return $info['all_possible_recipients'] ?? false;
    }

    /**
     * Should the notification be active when the activity is created?
     *
     * @param string $class_key
     * @return bool
     */
    public function is_active_by_default(string $class_key): bool {
        $info = $this->get_information($class_key);
        return $info['active_by_default'] ?? false;
    }

    /**
     * The recipients that should be enabled by default.
     *
     * @param string $class_key
     * @return int|null Returns null if there are no recipients
     */
    public function get_default_active_recipients_of(string $class_key): ?int {
        $info = $this->get_information($class_key);
        return $info['active_for_recipients'] ?? null;
    }

    /**
     * Throw an exception if the class key is not available.
     *
     * @param string $class_key
     * @throws class_key_not_available
     */
    public function ensure_class_key_exists(string $class_key): void {
        if (!isset($this->notifications[$class_key])) {
            throw new class_key_not_available($class_key);
        }
    }

    /**
     * @param string $class_key
     * @return array
     * @throws class_key_not_available
     */
    private function get_information(string $class_key): array {
        $this->ensure_class_key_exists($class_key);
        return $this->notifications[$class_key];
    }

    /**
     * Check if notification is reminder.
     *
     * @param string $class_key
     * @return boolean
     * @throws class_key_not_available
     */
    public function is_reminder(string $class_key): bool {
        $info = $this->get_information($class_key);
        return !empty($info['is_reminder']);
    }
}
