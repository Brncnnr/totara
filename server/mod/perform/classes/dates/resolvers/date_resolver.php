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

interface date_resolver {

    /**
     * Get the start date for n entry.
     *
     * @param array $args
     * @return int
     */
    public function get_start(...$args): ?int;

    /**
     * Get the end date for an entry.
     *
     * @param array $args
     * @return int
     */
    public function get_end(...$args): ?int;

    /**
     * Return what the resolver is based on (user, job, etc)
     *
     * @return string
     */
    public function get_resolver_base(): string;
}
