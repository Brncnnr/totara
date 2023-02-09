<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\assignment\assignment_type;

use core\entity\cohort as cohort_entity;
use core\orm\entity\entity;

/**
 * Cohort assignment type.
 */
class cohort extends base {

    public function __construct(int $id) {
        $this->entity = new cohort_entity($id);
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('model_assignment_type_cohort', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 3;
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'COHORT';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 30;
    }

    /**
     * @inheritDoc
     */
    public static function get_table(): string {
        return cohort_entity::TABLE;
    }

    /**
     * @inheritDoc
     */
    public static function instance(int $id): base {
        return new self($id);
    }

    /**
     * @inheritDoc
     */
    public function get_entity(): entity {
        return $this->entity;
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return empty($this->entity->name)
            ? get_string('untitled_cohort', 'mod_approval')
            : $this->entity->name;
    }

    /**
     * @inheritDoc
     */
    public function get_id_number(): string {
        return empty($this->entity->idnumber)
            ? "COHORT_{$this->entity->id}"
            : $this->entity->idnumber;
    }
}