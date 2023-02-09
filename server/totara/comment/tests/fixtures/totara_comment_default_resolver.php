<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment;
use totara_comment\resolver;

final class totara_comment_default_resolver extends resolver {
    /**
     * @var array
     */
    private static $callbacks;

    /**
     * @param string $component
     * @return void
     */
    public function set_component(string $component): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("It is not a unit test to set the component", DEBUG_DEVELOPER);
            return;
        }

        $this->component = $component;
    }

    /**
     * @param string   $functionname
     * @param callable $callback
     *
     * @return void
     */
    public static function add_callback(string $functionname, callable $callback): void {
        if (null == static::$callbacks) {
            static::$callbacks = [];
        }

        if (array_key_exists($functionname, static::$callbacks)) {
            debugging("There is a callback set for function '{$functionname}'", DEBUG_DEVELOPER);
            return;
        }


        if (!method_exists(static::class, $functionname)) {
            $cls = static::class;
            debugging("The function '{$functionname}' is not existing for class '{$cls}'", DEBUG_DEVELOPER);

            return;
        }

        static::$callbacks[$functionname] = $callback;
    }

    /**
     * @return void
     */
    public static function reset_callbacks(): void {
        static::$callbacks = [];
    }

    /**
     * @param string $functionname
     * @param array  $parameters
     *
     * @return bool
     */
    private function execute_callback(string $functionname, array $parameters): bool {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("It is not a unit test", DEBUG_DEVELOPER);
            return false;
        }

        if (is_array(static::$callbacks) && array_key_exists($functionname, static::$callbacks)) {
            $closure = \Closure::fromCallable(static::$callbacks[$functionname]);
            $result = $closure->__invoke(...$parameters);

            if (!is_bool($result)) {
                debugging(
                    "Expecting the callback for function '{$functionname}' to return the boolean result",
                    DEBUG_DEVELOPER
                );

                return false;
            }

            return $result;
        }

        return true;
    }

    /**
     * @param int    $instanceid
     * @param string $area
     * @param int    $actorid
     *
     * @return bool
     */
    public function is_allow_to_create(int $instanceid, string $area, int $actorid): bool {
        return $this->execute_callback(__FUNCTION__, [$instanceid, $area, $actorid]);
    }

    /**
     * @param comment $comment
     * @param int     $actorid
     *
     * @return bool
     */
    public function is_allow_to_delete(comment $comment, int $actorid): bool {
        return $this->execute_callback(__FUNCTION__, [$comment, $actorid]);
    }

    /**
     * @param comment $comment
     * @param int $actorid
     * @return bool
     */
    public function is_allow_to_update(comment $comment, int $actorid): bool {
        return $this->execute_callback(__FUNCTION__, [$comment, $actorid]);
    }

    /**
     * @param int $instanceid
     * @param string $area
     *
     * @return int
     */
    public function get_context_id(int $instanceid, string $area): int {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception("Not a unit test environment");
        }

        $callback = static::$callbacks[__FUNCTION__] ?? null;
        if (is_callable($callback)) {
            $closure = \Closure::fromCallable($callback);
            return $closure->__invoke($instanceid, $area);
        }

        $context = context_system::instance();
        return $context->id;
    }

    /**
     * @inheritDoc
     */
    public function get_owner_id_from_instance(string $area, int $instance_id): ?int {
        $callback = static::$callbacks[__FUNCTION__] ?? null;
        if (is_callable($callback)) {
            $closure = \Closure::fromCallable($callback);
            return $closure->__invoke($instance_id, $area);
        }
        return 0;
    }
}