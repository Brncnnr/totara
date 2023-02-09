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

/**
 * Class group_by
 *
 * Converts given fields to a group by sql statements.
 *
 * Warning! Passing params is not supported
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class group_by extends sql {

    /**
     * Generate SQL for the group by
     *
     * @return array [SQL, [Params]]
     */
    public function build(): array {
        if (empty($this->properties->group_by)) {
            return ['', []];
        }

        return ['GROUP BY ' . implode(', ', $this->properties->group_by), []];
    }

}