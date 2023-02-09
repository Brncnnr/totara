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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\router;

use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\dispatchable;

/**
 * A route class.
 */
class route {
    /**
     * Do not display typing indicator.
     */
    const QUIET = 1;

    /**
     * Available in a team channel.
     */
    const TEAM = 2;

    /**
     * Indicate that the dispatcher is a messaging extension connector instead of a bot response.
     * Set `EXTENSION` together with `TEAM` in reality.
     */
    const EXTENSION = 4;

    /** @var dispatchable */
    private $dispatcher;

    /** @var integer */
    private $flags = 0;

    /**
     * @param dispatchable $dispatcher
     * @param integer $flags
     */
    public function __construct(dispatchable $dispatcher, int $flags = 0) {
        $this->dispatcher = $dispatcher;
        $this->flags = $flags;
    }

    /**
     * @return dispatchable
     */
    public function get_dispatcher(): dispatchable {
        return $this->dispatcher;
    }

    /**
     * @return integer
     */
    public function get_flags(): int {
        return $this->flags;
    }

    /**
     * @param bot $bot
     * @param activity $activity
     */
    public function dispatch(bot $bot, activity $activity): void {
        $this->dispatcher->dispatch($bot, $activity);
    }

    /**
     * @param integer $flag
     * @return boolean
     */
    public function has(int $flag): bool {
        return ($this->flags & $flag) === $flag;
    }
}
