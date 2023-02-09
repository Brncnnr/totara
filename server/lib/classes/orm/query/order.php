<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query;

/**
 * Class order
 *
 * Special case of a field to provide better experience when using order, mainly to accommodate setting order
 * direction on the object, without resorting to raw.
 *
 * @package core\orm\query
 */
final class order extends field {

    const DIRECTION_ASC = 'ASC';
    const DIRECTION_DESC = 'DESC';

    /**
     * @var string
     */
    protected $direction = self::DIRECTION_ASC;

    /**
     * order constructor.
     *
     * @param string|field $field What field (column to order by)
     * @param string $direction What order direction should be: desc or asc
     * @param builder|null $builder
     */
    public function __construct($field, string $direction = self::DIRECTION_ASC, ?builder $builder = null) {

        if ($field instanceof raw_field) {
            if (is_null($builder)) {
                $builder = $field->get_builder();
            }
            $field = $field->get_field_as_is();
        }

        parent::__construct($field, $builder);

        // Order fields can't be aliased with as
        if ($this->get_field_as()) {
            $this->field_as = null;
            debugging('Order fields can not be aliased with as');
        }

        $this->set_direction($direction);
    }

    /**
     * Set order direction
     *
     * @param string $direction ASC or DESC
     * @return $this
     */
    public function set_direction(string $direction) {
        $this->direction = (strtolower($direction) === 'asc') ? self::DIRECTION_ASC : self::DIRECTION_DESC;

        return $this;
    }

    /**
     * Get order direction
     *
     * @return string
     */
    public function get_direction(): string {
        return $this->direction;
    }

    /**
     * Get sql ready to be inserted for this field
     *
     * @return string
     */
    public function sql(): string {
        return parent::sql() . ' ' . $this->get_direction();
    }
}
