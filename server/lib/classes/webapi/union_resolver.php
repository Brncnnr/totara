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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi;

use GraphQL\Type\Definition\ResolveInfo;

interface union_resolver {

    /**
     * Union type resolver. This should return a class name of a type resolver this type should resolve to.
     *
     * @param mixed $objectvalue
     * @param mixed $context
     * @param ResolveInfo $info
     * @return string the class name of the type resolver for the concrete type
     */
    public static function resolve_type($objectvalue, $context, ResolveInfo $info): string;

}
