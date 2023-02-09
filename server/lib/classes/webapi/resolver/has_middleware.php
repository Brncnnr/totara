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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver;

use core\webapi\middleware;

/**
 * @deprecated since Totara 17
 *
 * Use of this interface is no longer required as get_middleware() is now declared
 * in the base type, query and mutation resolver classes. Please update your resolvers
 * to extend the appropriate base class and remove 'implements has_middleware', e.g:
 *
 * Before:
 *
 * class my_resolver implements type_resolver, has_middleware {
 *
 * After:
 *
 * class my_resolver extends type_resolver {
 *
 */
interface has_middleware {

    /**
     * @return array|middleware[]
     */
    public static function get_middleware(): array;

}
