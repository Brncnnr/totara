<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package hierarchy_position
 */

namespace hierarchy_position\reference;

use core\webapi\reference\base_record_reference;
use hierarchy_position\entity\position;

/**
 * Position record reference. Used to find one record by provided parameters
 */
class hierarchy_position_record_reference extends base_record_reference {
    /**
     * @inheritDoc
     */
    protected array $refine_columns = ['id', 'idnumber'];

    /**
     * @inheritDoc
     */
    protected function get_table_name(): string {
        return position::TABLE;
    }

    /**
     * @inheritDoc
     */
    protected function get_entity_name(): string {
        return 'Position';
    }
}
