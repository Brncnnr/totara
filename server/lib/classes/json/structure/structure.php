<?php
/**
 * This file is part of Totara Core
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
 * @package core
 */
namespace core\json\structure;

use stdClass;

/**
 * A metadata class that provide the json schema.
 */
abstract class structure {
    /**
     * Keyword for any of the object/type.
     *
     * @var string
     */
    public const ANY_OF = 'anyOf';

    /**
     * Keyword for one of the object/type.
     *
     * @var string
     */
    public const ONE_OF = 'oneOf';

    /**
     * Keyword for all of the object/type.
     *
     * @var string
     */
    public const ALL_OF = 'allOf';

    /**
     * Keyword for the min items within a list.
     *
     * @var string
     */
    public const MIN_ITEMS = 'minItems';

    /**
     * Keyword for the max items within a list.
     *
     * @var string
     */
    public const MAX_ITEMS = 'maxItems';

    /**
     * Keyword for the additional properties.
     *
     * @var string
     */
    public const ADDITIONAL_PROPERTIES = 'additionalProperties';

    /**
     * Keyword for the additional items within the collection.
     * The value associate with this key is either object, primitive type or FALSE.
     *
     * @var string
     */
    public const ADDITIONAL_ITEMS = 'additionalItems';

    /**
     * structure constructor.
     */
    private function __construct() {
        // Prevents the child of class from instantiation, as we are
        // only interested in the static methods for definition.
    }

    /**
     * Returns the json structure that either is object or array
     *
     * Note: if the definition is string, then the encoding process will not be encoded again, which it
     * would assume that the value from this function is a json encoded ready.
     *
     * @return array|stdClass|string
     */
    abstract public static function get_definition();
}