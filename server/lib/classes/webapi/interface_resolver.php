<?php
/*
 * This file is part of Totara LMS
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
 * @package core
 */
namespace core\webapi;

use GraphQL\Type\Definition\ResolveInfo;

interface interface_resolver {
    /**
     * Usage of this function is to check which of your graphql type match with the param $objectValue and then
     * returns a string. The string can be a classname of that graphql type, or a actual graphql type defined in
     * the schema.graphqls.
     *
     * @param mixed $objectvalue
     * @param mixed $context
     * @param ResolveInfo   $info
     *
     * @return string
     */
    public static function resolve($objectvalue, $context, ResolveInfo $info): string;
}