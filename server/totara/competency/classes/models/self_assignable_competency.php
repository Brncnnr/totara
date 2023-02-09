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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use coding_exception;
use core\orm\collection;
use totara_hierarchy\entity\competency;

class self_assignable_competency {

    /**
     * @var competency
     */
    protected $entity;

    /**
     * @var array
     */
    protected $user_assignments = [];

    private function __construct(competency $entity) {
        $this->entity = $entity;
    }

    public static function load_by_id(int $id): self {
        $entity = new competency($id);
        return new static($entity);
    }

    public static function load_by_entity(competency $entity): self {
        if (!$entity->exists()) {
            throw new coding_exception('Can load only existing entities');
        }
        return new static($entity);
    }

    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @param array|collection $assignments
     * @return $this
     * @throws coding_exception
     */
    public function set_user_assignments($assignments) {
        if ($assignments instanceof collection) {
            $assignments = $assignments->all();
        }
        if (!is_array($assignments)) {
            throw new coding_exception('Expected an array or a collection of assignments');
        }

        $this->user_assignments = $assignments;

        return $this;
    }

    public function get_user_assignments(): array {
        return $this->user_assignments;
    }

    /**
     * Returns the value of the given field, throws exception if fields doesn't exist
     *
     * @param string $field
     * @return mixed
     */
    public function get_field(string $field) {
        switch ($field) {
            case 'user_assignments':
                return $this->get_user_assignments();
            default:
                if ($this->entity->has_attribute($field)) {
                    return $this->entity->$field;
                }
                break;
        }

        throw new coding_exception('Unknown competency field '.$field);
    }

    public function has_field(string $field): bool {
        $extra_fields = ['user_assignments'];
        return in_array($field, $extra_fields)
            || $this->entity->has_attribute($field);
    }

    public function to_array(): array {
        return $this->entity->to_array();
    }

}