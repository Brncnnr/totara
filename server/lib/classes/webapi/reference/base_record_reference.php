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
 * @package core
 */

namespace core\webapi\reference;

use Closure;
use core\exception\unresolved_record_reference;
use core\orm\query\builder;
use dml_exception;
use dml_missing_record_exception;
use dml_multiple_records_exception;
use stdClass;

/**
 * Base class used for identifying one record by provided columns.
 */
abstract class base_record_reference {

    /**
     * The DB columns used for finding the reference record
     * @var string[]
     */
    protected array $refine_columns = [];

    /**
     * The entity name used for error messages etc.
     * @var string
     */
    protected string $entity_name = '';

    /**
     * A set of filter functions that are called after we fetched the reference record
     * @var Closure[]
     */
    private array $post_filters = [];

    /**
     * The DB table where the reference record will be looked up
     * @return string
     */
    abstract protected function get_table_name(): string;

    /**
     * The entity name for error messages
     * @return string
     */
    abstract protected function get_entity_name(): string;

    public function __construct(?string $entity_name = null) {
        if (isset($entity_name)) {
            $this->entity_name = $entity_name;
        } else {
            $this->entity_name = $this->get_entity_name();
        }
    }

    /**
     * Converts the provided columns and their values to the conditions for DB lookup query
     * @param array $ref_columns The column names and their values as associative array
     * @return array
     */
    protected function convert_ref_columns_to_conditions(array $ref_columns = []): array {
        $conditions = [];
        foreach ($this->refine_columns as $field) {
            if (isset($ref_columns[$field])) {
                $conditions[$field] = $ref_columns[$field];
            }
        }

        return $conditions;
    }

    /**
     * Finds the record that matches the provided columns
     * @param array $ref_columns The column names and their values as associative array
     * @return stdClass
     * @throws dml_exception
     * @throws unresolved_record_reference
     */
    public function get_record(array $ref_columns = []): stdClass {
        $conditions = $this->convert_ref_columns_to_conditions($ref_columns);

        if (empty($ref_columns) || empty($conditions)) {
            throw new unresolved_record_reference($this->entity_name . ' reference columns are not being passed.');
        }

        try {
            $target_record = builder::get_db()->get_record(
                $this->get_table_name(),
                $conditions,
                '*',
                MUST_EXIST
            );
        } catch (dml_multiple_records_exception $exception) {
            throw new unresolved_record_reference($this->entity_name . ' reference must resolve one record only');
        } catch (dml_missing_record_exception $exception) {
            throw new unresolved_record_reference($this->entity_name . ' reference not found');
        }

        if (!empty($this->post_filters)) {
            foreach ($this->post_filters as $post_filter) {
                $target_record = $post_filter($target_record);
            }
            if (!is_object($target_record)) {
                throw new unresolved_record_reference($this->entity_name . ' reference not found');
            }
        }

        return $target_record;
    }

    /**
     * Adds a filter to run after we fetched the reference record
     * @param Closure $filter
     * @return void
     */
    protected function filter(Closure $filter): void {
        $this->post_filters[] = $filter;
    }
}
