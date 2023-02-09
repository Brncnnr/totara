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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_contentmarketplace\model\content_marketplace as model;
use totara_contentmarketplace\learning_object\abstraction\metadata\model as learning_object_model;
use coding_exception;

/**
 * Extend this class to help on resolving the common attributes of content marketplace instance.
 */
abstract class content_marketplace_activity extends type_resolver {
    /**
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|model|learning_object_model
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            throw new coding_exception("Cannot resolve other object type rather than " . model::class);
        }

        switch ($field) {
            case "module":
                return $source;

            case "learning_object":
                return $source->get_learning_object();

            default:
                return self::resolve_extended_field($field, $source, $args, $ec);
        }
    }

    /**
     * Extend this function to support more fields at lower level of type resolver implementation.
     *
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    protected static function resolve_extended_field(
        string $field,
        model $source,
        array $args,
        execution_context $ec
    ) {
        throw new coding_exception("Unsupported field '{$field}'");
    }
}