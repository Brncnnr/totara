<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\entity;

use context;

interface expandable {

    /**
     * @param context|null $context for multi tenancy compatibility you need to pass a context
     * @return array
     */
    public function expand(?context $context = null): array;

    /**
     * @param int[] $ids
     * @param context|null $context for multi tenancy compatibility you need to pass a context
     * @return array
     */
    public static function expand_multiple(array $ids, ?context $context = null): array;

}