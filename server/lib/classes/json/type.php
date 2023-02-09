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
namespace core\json;

/**
 * A constant class that declares all primitives type support for a json editor.
 */
class type {
    /**
     * Type string for a field within json.
     *
     * @var string
     */
    public const STRING = 'string';

    /**
     * Type integer for a field within json.
     *
     * @var string
     */
    public const INT = 'integer';

    /**
     * Includes type integer and float for a field within json.
     * Use this type for a number that can be a float type.
     *
     * @var string
     */
    public const NUMBER = 'number';

    /**
     * Type boolean for a field within json.
     *
     * @var string
     */
    public const BOOL = 'boolean';

    /**
     * Type object for a field within json.
     *
     * @var string
     */
    public const OBJECT = 'object';

    /**
     * Type null for a field within json.
     *
     * @var string
     */
    public const NULL = 'null';

    /**
     * Type array for a field within json.
     *
     * @var string
     */
    public const ARRAY = 'array';
}