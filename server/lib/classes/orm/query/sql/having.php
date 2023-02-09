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
 * @package core
 * @group orm
 */

namespace core\orm\query\sql;

use core\orm\query\builder;

/**
 * Convert builder instance containing conditions for having into a proper having statement with params
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class having extends sql {

    /**
     * Generate SQL for the having part
     *
     * @return array [SQL, [Params]]
     */
    public function build(): array {
        
        if (!$this->properties->having instanceof builder || !$this->properties->having->has_conditions()) {
            return ['', []];
        }

        [$sql, $params] = where::from_builder($this->properties->having)->build(false);

        return ["HAVING ${sql}", $params];
    }

}