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

namespace core\orm\query;

/**
 * This class is a data holder to hold all shared properties of a query builder.
 * This enables to recreated a new instance of a builder with the same properties
 * or use the repository to change the properties of the builder.
 *
 * It enables a cleaner interface of the repository and enables to use a trait in both to share
 * the features.
 */
final class properties {

    /**
     * Return results as class
     */
    public const AS_OBJECT = 0;

    /**
     * Return results as array
     */
    public const AS_ARRAY = 1;

    /**
     * A class name or callable to map the selection in the database to an object
     *
     * @var null|string|callable
     */
    public $map_to = null;

    /**
     * Default return type from builder
     *
     * 0 - as stdClass
     * 1 - as array
     *
     * @var int
     */
    public $return_type = self::AS_OBJECT;

    /**
     * Query builder to select from subquery
     *
     * @var builder
     */
    public $from;

    /**
     * Array of items to select
     *
     * It will translate to a string that would be safe to inject into the query
     *
     * @var mixed[]
     */
    public $selects = [];

    /**
     * Array of queryable conditions to generate SQL where part
     *
     * @var queryable[]|condition[]
     */
    public $conditions = [];

    /**
     * Array of tables to join
     *
     * @var join[]
     */
    public $joins = [];

    /**
     * Array of queries joined with union
     *
     * @var array[]
     */
    public $unions = [];

    /**
     * Array of group by conditions
     *
     * @var string[]
     */
    public $group_by = [];

    /**
     * Instance of query builder to hold conditions to generate HAVING part of
     *
     * @var builder
     */
    public $having = null;

    /**
     * Flag whether it is the nested instance
     *
     * @var bool
     */
    public $nested = true;

    /**
     * Flag whether this query builder is used for query joined with the UNION, it imposes
     * some extra restrictions
     *
     * @var bool
     */
    public $united = false;

    /**
     * Limit from (offset) attribute
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Limit num attribute
     *
     * @var int
     */
    public $limit = 0;

    /**
     * Aggregation flag True - AND, False - OR
     *
     * @var bool
     */
    public $aggregation = true;

    /**
     * The column name or raw order string
     *
     * @var order[]
     */
    public $orders = [];

    /**
     * A flag whether to apply alias or not?
     *
     * @var bool
     */
    public $use_alias = true;

    /**
     * Base table alias
     *
     * @var string
     */
    public $alias = '';

    /**
     * The table name this builder is used for
     *
     * @var string
     */
    public $table = '';

}
