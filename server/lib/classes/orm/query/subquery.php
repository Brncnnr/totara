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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query;

use coding_exception;
use core\orm\query\sql\query;

/**
 * This class is designed to pass subquery to the select function of the builder with an alias.
 *
 * $builder->select((new subquery($subquery_builder))->as('a_field'));
 *
 * // OR if you want to build a subquery on the go, you may use closure based syntax
 *
 * $builder->select((new subquery(function (builder $subquery) {
 *      $subquery->from('another_table')
 *               ->select('max(something)')
 *               ->where('bla', 'bla');
 * })->as('max_something'));
 *
 * @package core
 * @group orm
 */
final class subquery extends raw_field {

    /**
     * @var builder
     */
    protected $subquery;

    /**
     * subquery constructor.
     * @param \Closure|builder $subquery Builder object containing a subquery or a closure that will receive a new builder instance as first argument
     * @param builder|null $builder A link to the parent builder, if not passed it's set to the builder it's added to.
     */
    public function __construct($subquery, ?builder $builder = null) {
        if (is_callable($subquery)) {
            $sqb = new builder(null, false);

            $as = null;

            $subquery($sqb, $as);

            if (!$sqb instanceof builder) {
                throw new coding_exception('Callback must not reset builder');
            }

            if (!is_null($as)) {
                $this->as($as);
            }
        } else if ($subquery instanceof builder) {
            $sqb = $subquery;
        } else {
            throw new coding_exception('{$subquery} must be a builder or a callable');
        }

        $this->subquery = $sqb;
        $this->is_raw = true;

        if ($builder) {
            $this->set_builder($builder);
        }
    }

    /**
     * Get builder holding a subquery
     *
     * @return builder
     */
    public function get_subquery(): builder {
        return $this->subquery;
    }

    /**
     * Set a link to the parent builder instance
     *
     * @param builder $builder A ling to
     * @return $this
     */
    public function set_builder(builder $builder) {
        parent::set_builder($builder);

        $this->subquery->set_parent($builder);

        return $this;
    }

    /**
     * Set field alias
     *
     * @param string $as
     * @return $this
     */
    public function as(string $as) {
        // Sanity check, which should probably be extracted to a static function on the base::class
        if (!empty($as) && !preg_match(builder_base::AS_REGEX, $as)) {
            throw new coding_exception('Table aliases can only be alpha numeric with underscores');
        }

        $this->field_as = $as;

        return $this;
    }

    /**
     * Using aggregation functions is not supported
     *
     * @return null
     */
    public function get_field_agg(): ?string {
        return null;
    }

    /**
     * Prefixing subquery with an alias is not supported
     *
     * @return null
     */
    public function get_field_alias(): ?string {
        return null;
    }

    /**
     * Prefixing subquery altogether is not supported
     *
     * @return string
     */
    public function get_prefix(): string {
        return '';
    }

    /**
     * Getting column name of subquery is not possible
     *
     * @return string
     */
    public function get_field_column(): string {
        return '';
    }

    /**
     * Getting 'raw' sql this way is not supported
     *
     * @return string
     */
    public function get_field_as_is(): string {
        return '';
    }

    /**
     * As for regex checks this is considered to be valid
     *
     * @return null
     */
    public function validate(): bool {
        return true;
    }

    /**
     * Due to internal implementation getting params or sql separately without implementing caching is not viable
     *
     * @return array
     */
    public function get_params(): array {
        return [];
    }

    /**
     * Due to internal implementation getting params or sql separately without implementing caching is not viable
     *
     * @return string
     */
    public function sql(): string {
        return '';
    }

    /**
     * Get compiled subquery in a form of a bag [$sql, $params]
     *
     * @return array [$sql, $params]
     */
    public function build(): array {
        [$sql, $params] = query::from_builder($this->subquery)->build();

        if (!empty($sql = trim($sql))) {
            $sql = "({$sql})";

            if (!empty($as = $this->get_field_as())) {
                $sql = "{$sql} as {$as}";
            }
        }

        return [$sql, $params];
    }

}
