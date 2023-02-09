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

namespace mod_approval\model\application\action;

use coding_exception;
use core_component;
use invalid_parameter_exception;
use lang_string;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\workflow\interaction\transition\transition_base;

/**
 * Base class for user interactions with applications.
 */
abstract class action {
    /** @var string[]|null */
    private static $class_map = null;

    /**
     * Is application_action code valid?
     *
     * @param int $code The application_action code
     * @return bool
     */
    final public static function is_valid(int $code): bool {
        $classes = self::get_class_map();
        return isset($classes[$code]);
    }

    /**
     * @return array of [code => class, ...]
     */
    private static function get_class_map(): array {
        if (self::$class_map === null) {
            $class_map = [];
            $classes = core_component::get_namespace_classes('model\\application\\action', self::class, 'mod_approval');
            /** @var action $class */
            foreach ($classes as $class) {
                $code = $class::get_code();
                if (isset($class_map[$code])) {
                    throw new coding_exception(
                        "Action code {$code} is already taken by {$class_map[$code]}, being overridden by {$class}"
                    );
                }
                $class_map[$code] = $class;
            }
            self::$class_map = $class_map;
        }
        return self::$class_map;
    }

    /**
     * Get the class path from the action code.
     *
     * @param integer $code The application_action code
     * @return action
     */
    public static function from_code(int $code): action {
        $classes = self::get_class_map();
        if (isset($classes[$code])) {
            $class = $classes[$code];
            return new $class();
        }
        throw new invalid_parameter_exception('Invalid application_action code');
    }

    /**
     * Get the class path from the enum string.
     *
     * @param string $enum The application_action enum string
     * @return action
     */
    public static function from_enum(string $enum): action {
        $classes = self::get_class_map();
        foreach ($classes as $class) {
            if ($class::get_enum() === $enum) {
                return new $class();
            }
        }
        throw new invalid_parameter_exception('Invalid action: ' . $enum);
    }

    /**
     * Gets action code.
     *
     * @return integer
     */
    abstract public static function get_code(): int;

    /**
     * Gets action enum string for mod_approval_application_action_type.
     *
     * @return string
     */
    abstract public static function get_enum(): string;

    /**
     * Gets label associated with this action.
     *
     * @return lang_string
     */
    abstract public static function get_label(): lang_string;

    /**
     * Sees if the actor can take an action on the application.
     *
     * @param application_interactor $interactor
     * @return boolean
     */
    abstract public static function is_actionable(application_interactor $interactor): bool;

    /**
     * Execute the action.
     *
     * Each subclass should implement this function and make whatever changes are needed when this is called.
     *
     * @param application $application
     * @param int $actor_id
     */
    abstract public static function execute(application $application, int $actor_id): void;

    /**
     * Returns the hardcoded default transition to use when this action occurs.
     *
     * @return transition_base
     */
    abstract public static function get_default_transition(): transition_base;

}
