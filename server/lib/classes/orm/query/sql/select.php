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

use Closure;
use coding_exception;
use core\orm\collection;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\raw_field;
use core\orm\query\subquery;

/**
 * Class select
 *
 * Creates sql statement for the select part of the query converting php field::class objects stored on the builder.
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class select extends sql {

    /**
     * Generate SQL for the query itself. Woo-hoo.
     *
     * @return array [SQL, [Params]]
     */
    public function build(): array {
        // Let's first map the select statements to be the same
        // Select all by default
        $selects = collection::new($this->properties->selects ?: [new field('*', new builder($this->properties))])
            ->map(Closure::fromCallable([$this, 'map_select']))
            ->to_array();

        $sql = implode(', ', array_column($selects, 0));
        $params = array_merge_recursive(...array_column($selects, 1));
        
        return [$sql, $params];
    }

    /**
     * Unify things that might be converted to select
     *
     * @param raw_field|builder $select
     * @return array [$sql, $params]
     */
    protected function map_select($select) {
        switch (true) {
            case $select instanceof subquery:
                return $select->build();

            case $select instanceof raw_field:
                return [$select->sql(), $select->get_params()];

            case $select instanceof builder:
                [$sql, $params] =  array_slice(query::from_builder($select)->build(), 0, 2);

                return ["({$sql})", $params];

            default:
                throw new coding_exception('Invalid select type slipped through somehow...');
        }
    }

}