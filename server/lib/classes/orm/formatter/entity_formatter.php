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
 * @package core_orm
 */

namespace core\orm\formatter;

use context;
use core\orm\entity\entity;
use core\webapi\formatter\formatter;

abstract class entity_formatter extends formatter {

    /**
     * @var entity
     */
    protected $object;

    /**
     * @param entity $object
     * @param context $context
     */
    public function __construct($object, context $context) {
        if (!$object instanceof entity) {
            throw new \coding_exception('Entity formatter can only format entities');
        }
        return parent::__construct($object, $context);
    }

    protected function get_field(string $field) {
        return $this->object->$field;
    }

    protected function has_field(string $field): bool {
        $result = false;

        if (method_exists($this->object, 'relation_exists')) {
            $result = $this->object->relation_exists($field);
        }

        return $result || $this->object->has_attribute($field);
    }

}