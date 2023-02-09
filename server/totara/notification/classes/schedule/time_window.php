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
namespace totara_notification\schedule;

use coding_exception;

class time_window {
    /**
     * @var int
     */
    private $min_time;

    /**
     * @var int
     */
    private $max_time;

    /**
     * time_window constructor.
     * @param int      $min_time
     * @param int      $max_time
     */
    public function __construct(int $min_time, int $max_time) {
        $this->min_time = $min_time;
        $this->max_time = $max_time;
    }

    /**
     * @return int
     */
    public function get_min_time(): int {
        return $this->min_time;
    }

    /**
     * @return int
     */
    public function get_max_time(): int {
        return $this->max_time;
    }

    /**
     * @return bool
     */
    public function is_valid(): bool {
        return $this->min_time <= $this->max_time;
    }

    /**
     * @return void
     */
    public function validate(): void {
        if (!$this->is_valid()) {
            throw new coding_exception("Invalid time frame that the min value is greater than max value");
        }
    }
}
