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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\entity\filter;

use Closure;
use core\orm\entity\filter\filter;

abstract class user_learning_progress_filter extends filter {

    public const COMPLETED = 'COMPLETED';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const NOT_STARTED = 'NOT_STARTED';
    public const NOT_TRACKED = 'NOT_TRACKED';

    /**
     * Get progress status value.
     *
     * @param $value
     *
     * @return Closure
     */
    protected function get_status($value): Closure {
        switch ($value) {
            case self::COMPLETED:
                return $this->completed();
            case self::IN_PROGRESS:
                return $this->in_progress();
            case self::NOT_STARTED:
                return $this->not_started();
            case self::NOT_TRACKED:
                return $this->not_tracked();
        }

        throw new \coding_exception('Invalid completion status');
    }

    /**
     * Get condition to filter items that are completed.
     *
     * @return Closure
     */
    abstract protected function completed(): Closure;

    /**
     * Get condition to filter items that are in progress.
     *
     * @return Closure
     */
    abstract protected function in_progress(): Closure;

    /**
     * Get condition to filter items that are not started.
     *
     * @return Closure
     */
    abstract protected function not_started(): Closure;

    /**
     * Get condition to filter items that are not tracked.
     *
     * @return Closure
     */
    abstract protected function not_tracked(): Closure;

}