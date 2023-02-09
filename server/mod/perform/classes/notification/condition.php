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

abstract class condition {
    /** ascending order */
    protected const ASC = 0;

    /** descending order */
    protected const DESC = 1;

    /** @var clock */
    private $clock;

    /** @var integer[] */
    private $triggers;

    /** @var integer */
    private $last_run_time;

    /**
     * Constructor. *Do not instantiate this class directly. Use the factory class.*
     *
     * @param clock $clock
     * @param array $triggers trigger values in seconds
     * @param integer $last_run_time
     * @internal
     */
    public function __construct(clock $clock, array $triggers, int $last_run_time) {
        $this->clock = $clock;
        $this->triggers = $triggers;
        $this->last_run_time = $last_run_time;
    }

    /**
     * Get the current time.
     * Do *NOT* use the time() function!!
     *
     * @return integer
     */
    protected function get_time(): int {
        return $this->clock->get_time();
    }

    /**
     * Get sorted trigger values in seconds.
     *
     * @param integer $order
     * @return array
     */
    protected function get_sorted_triggers(int $order): array {
        $triggers = $this->triggers;
        if ($order === self::ASC) {
            sort($triggers);
        } else if ($order === self::DESC) {
            rsort($triggers);
        }
        return $triggers;
    }

    /**
     * Return true if notification is triggered after the specific time.
     *
     * @param integer $time
     * @return boolean
     */
    protected function is_over(int $time): bool {
        return $this->last_run_time > $time;
    }

    /**
     * Test the condition.
     *
     * @param integer $base_time
     * @return boolean
     */
    abstract public function pass(int $base_time): bool;
}
