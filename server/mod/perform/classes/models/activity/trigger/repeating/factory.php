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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\trigger\repeating;

use coding_exception;
use mod_perform\entity\activity\track;

/**
 * Creates an instance of a trigger subclass.
 */
final class factory {
    // Known trigger subclasses.
    private static $triggers = [];

    // Singleton instance of this factory.
    private static $instance = null;

    /**
     * Returns the singleton instance of this factory.
     *
     * Required because there is no other way to do static initialization of
     * self::$triggers.
     *
     * @return self the factory.
     */
    public static function get_instance(): self {
        if (!self::$instance) {
            $triggers = [
                after_closure::class,
                after_completion_or_closure::class,
                after_completion::class,
                after_creation_and_closure::class,
                after_creation_and_completion_or_closure::class,
                after_creation_and_completion::class,
                after_creation::class
            ];

            foreach ($triggers as $subclass) {
                // The out of the box classes have default constructors; modify
                // this part for custom triggers that require constructors with
                // parameters.
                self::$triggers[$subclass] = new $subclass();
            }

            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Default constructor.
     */
    private function __construct() {
        // EMPTY BLOCK.
    }

    /**
     * Creates an instance of a trigger via its identifier.
     *
     * @param string $identifier indicates the subclass to create.
     *
     * @return trigger the trigger subclass.
     *
     * @throws coding_exception if the identifier is unknown.
     */
    public function create_trigger(
        string $identifier
    ): trigger {
        $trigger = self::$triggers[$identifier] ?? null;
        if ($trigger) {
            return $trigger;
        }

        throw new coding_exception("unknown trigger identifier: '$identifier'");
    }

    /**
     * Creates an instance of a trigger via its name and interval values.
     *
     * @param string $interval trigger interval.
     * @param string $name trigger name.
     *
     * @return trigger the trigger subclass.
     *
     * @throws coding_exception if there is no subclass with the interval/name
     *         combination.
     */
    public function create_trigger_from_interval_and_name(
        string $interval,
        string $name
    ): trigger {
        foreach (self::$triggers as $trigger) {
            if ($trigger->get_name() === $name
                && $trigger->get_interval() === $interval
            ) {
                return $trigger;
            }
        }

        throw new coding_exception("no trigger with name/interval: '$name/$interval'");
    }

    /**
     * Temporary method to be used during the migration from the deprecated
     * track::SCHEDULE_REPEATING_TYPE enums to new trigger subclasses.
     *
     * @param int $type enum to convert.
     *
     * @return trigger the trigger subclass.
     *
     * @throws coding_exception if the type is unknown.
     */
    public function create_trigger_from_repeating_type(int $type): trigger {
        switch ($type) {
            case track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION:
                return new after_creation();

            case track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE:
                return new after_creation_and_completion();

            case track::SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION:
                return new after_completion();

            default:
                throw new coding_exception("cannot convert repeating type: '$type'");
        }
    }

    /**
     * Temporary method to be used during the migration from the deprecated
     * track::SCHEDULE_REPEATING_TYPE enums to new trigger subclasses.
     *
     * @param trigger the trigger subclass.
     *
     * @return int the track::SCHEDULE_REPEATING_TYPE value.
     */
    public function create_repeating_type_from_trigger(trigger $trigger): int {
        switch (get_class($trigger)) {
            case after_creation::class:
                return track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION;

            case after_creation_and_completion::class:
                return track::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE;

            case after_completion::class:
                return track::SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION;

            default:
                return track::SCHEDULE_REPEATING_TYPE_UNSET;
        }
    }
}
