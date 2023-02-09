<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\webapi;

/**
 * Base class for GraphQL scalars.
 */
abstract class scalar {
    /**
     * Serializes a server-side value to the client format.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function serialize($value) {
        return $value;
    }

    /**
     * Parses a value provided by client (usually via variables) to the internal
     * server-side format.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function parse_value($value) {
        return $value;
    }

    /**
     * Parses a literal value hardcoded in GraphQL query.
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     * @param array|null $variables
     * @return mixed
     */
    public static function parse_literal(\GraphQL\Language\AST\Node $valueNode, ?array $variables) {
        if ($valueNode instanceof \GraphQL\Language\AST\StringValueNode
            or $valueNode instanceof \GraphQL\Language\AST\IntValueNode
            or $valueNode instanceof \GraphQL\Language\AST\FloatValueNode
            or $valueNode instanceof \GraphQL\Language\AST\BooleanValueNode
        ) {
            return static::parse_value($valueNode->value);
        }
        // NOTE: override this method if scalar is represented by some other structure in the query text.
        throw new \Exception();
    }
}