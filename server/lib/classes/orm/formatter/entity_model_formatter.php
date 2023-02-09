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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\formatter;

use context;
use core\collection;
use core\orm\entity\model;
use core\webapi\formatter\formatter;

/**
 * Class entity_model_formatter
 *
 * This class format the entity model class attributes
 *
 * @package core\orm\formatter
 */
abstract class entity_model_formatter extends formatter {

    /**
     * @var model
     */
    protected $object;

    /**
     * @param model $object
     * @param context $context
     */
    public function __construct($object, context $context) {
        if (!$object instanceof model) {
            throw new \coding_exception('Entity model formatter can only format entity models');
        }
        return parent::__construct($object, $context);
    }

    /**
     * @param string $field
     *
     * @return mixed
     */
    protected function get_field(string $field) {
        $value = $this->object->$field;

        // Un-box collections.
        if ($value instanceof collection) {
            return $value->all(true);
        }

        return $value;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function has_field(string $field): bool {
        return $this->object->has_attribute($field);
    }

}