<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */
namespace mod_perform\dates\resolvers;

use mod_perform\dates\constants;

class fixed_range_resolver implements date_resolver {

    /**
     * @var int
     */
    protected $start;

    /**
     * @var int|null
     */
    protected $end;

    /**
     * @param int $start unix timestamp of start date
     * @param int|null $end unix timestamp of end date
     */
    public function __construct(int $start, ?int $end) {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @inheritDoc
     */
    public function get_start(...$args): ?int {
        return $this->start;
    }

    /**
     * @inheritDoc
     */
    public function get_end(...$args): ?int {
        return $this->end;
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return constants::DATE_RESOLVER_EMPTY_BASE;
    }

}
