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

use hierarchy_position\entity\position as position_entity;

/**
 * Position assignment type.
 */
class position extends base {

    public function __construct(int $id) {
        $this->entity = new position_entity($id);
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('model_assignment_type_position', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'POSITION';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 20;
    }

    /**
     * @inheritDoc
     */
    public static function get_table(): string {
        return position_entity::TABLE;
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
    public function get_name(): string {
        return empty($this->entity->fullname)
            ? get_string('untitled_position', 'mod_approval')
            : $this->entity->fullname;
    }

    /**
     * @inheritDoc
     */
    public function get_id_number(): string {
        return empty($this->entity->shortname)
            ? "POSITION_{$this->entity->id}"
            : $this->entity->shortname;
    }
}