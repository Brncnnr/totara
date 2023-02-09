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
 */

namespace core\orm\query\sql;

use core\orm\query\builder_base;

/**
 * The _sql classes extending this one all have one responsibility:
 * build parts of a query.
 *
 * For example the where class is responsible for building the SQL part
 * of the WHERE clause in a query.
 *
 * They can all be tested individually and are used by the query builder.
 * Having them in separate classes enables a leaner builder and with less public methods.
 *
 * The SQL parts are not meant to be publicly used in the builder.
 *
 * @internal This is not meant to be used as external API
 */
abstract class sql extends builder_base {

    /**
     * Create sql from a given builder class
     *
     * @param builder_base $builder
     * @return sql
     */
    public static function from_builder(builder_base $builder): self {
        return new static($builder->properties, false);
    }

    /**
     * Generate SQL for the query itself. Woo-hoo.
     *
     * @return array [SQL, [Params], $limit_from, $limit_to]
     */
    abstract public function build(): array;

}