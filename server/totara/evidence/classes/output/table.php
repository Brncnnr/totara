<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\output;

use Closure;
use coding_exception;
use core\orm\entity\entity;
use core\output\template;

class table extends template {

    /**
     * Columns that correlate to database fields
     *
     * @var array
     */
    protected $columns;

    /**
     * Action buttons, ie edit, delete
     *
     * @var callable
     */
    protected $actions;

    /**
     * Message to be shown when there are no rows
     *
     * @var string
     */
    protected $no_data_message;

    /**
     * The HTML ID attribute of the table
     *
     * @var string
     */
    protected $html_id = "table-evidence";

    /**
     * Create cells that correspond to the column (attribute/lambda) and entity (item)
     *
     * @param entity $entity
     * @return array
     */
    protected function create_cells(entity $entity): array {
        // Constraint - no point in having a table without any columns
        if (!is_array($this->columns) || count($this->columns) === 0) {
            throw new coding_exception("At least one column must be specified for the table");
        }

        $row = [];
        foreach ($this->columns as $column) {
            if (is_string($column['value'])) {
                $row[] = format_string($entity->get_attribute($column['value']));
            } else {
                $row[] = ($column['value'])($entity);
            }
        }
        return $row;
    }

    /**
     * Create a row with cells and optional specified actions
     *
     * @param entity[] $entities Array of entity values
     * @return array
     */
    protected function create_rows(array $entities): array {
        $rows = [];
        foreach ($entities as $entity) {
            $row['row_id'] = $entity->id;
            $row['cell'] = $this->create_cells($entity);

            if (isset($this->actions)) {
                $row['action'] = ($this->actions)($entity);
            }

            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Creates the table and related data for usage in template
     *
     * @return array
     */
    public function get_template_data(): array {
        $table = [
            'id'              => $this->html_id,
            'header'          => array_column($this->columns, 'label'),
            'has_rows'        => !empty($this->data),
            'has_actions'     => isset($this->actions),
            'no_data_message' => $this->no_data_message
        ];

        $table['column_count'] = count($table['header']) + (int) $table['has_actions'];

        if ($table['has_rows']) {
            $table['row'] = $this->create_rows($this->data);
        }

        return $table;
    }

    /**
     * Sets columns that correlate to database fields
     *
     * @param array ...$columns
     * @return table
     */
    public function set_columns(array ...$columns): table {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set action buttons, eg edit, delete
     *
     * @param Closure $actions Lambda function that creates actions array for a given entity
     * @return table
     */
    public function set_actions(Closure $actions): table {
        $this->actions = $actions;
        return $this;
    }

    /**
     * Set message to be shown when there are no rows
     *
     * @param string $message
     * @return table
     */
    public function set_no_data_message(string $message): table {
        $this->no_data_message = $message;
        return $this;
    }

    /**
     * Set the HTML ID attribute of the table
     *
     * @param string $id HTML attribute
     * @return table
     */
    public function set_id(string $id): table {
        $this->html_id = $id;
        return $this;
    }
}
