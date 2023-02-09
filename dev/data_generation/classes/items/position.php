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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items;

use degeneration\App;
use hierarchy_position\entity\position as position_entity;

/**
 * Class position
 *
 * @method position_entity get_data()
 *
 * @package degeneration\items
 */
class position extends hierarchy_item {

    /**
     * Get hierarchy item type
     *
     * @return string
     */
    public function get_type(): string {
        return 'position';
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function get_properties(): array {
        return [
            'fullname' => App::faker()->jobTitle,
            'description' => App::faker()->bs,
            'idnumber' => uniqid('pos_'),
            'visible' => true,
        ];
    }

    public function get_entity_class(): ?string {
        return position_entity::class;
    }
}