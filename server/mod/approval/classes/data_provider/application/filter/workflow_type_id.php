<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\filter;

use coding_exception;
use core\orm\entity\filter\filter;

/**
 * Filter by application id
 */
class workflow_type_id extends filter {

    /**
     * @var string
     */
    protected $workflow_type_table_alias;

    /**
     * @param string $workflow_type_table_alias
     */
    public function __construct(string $workflow_type_table_alias = 'workflow_type') {
        parent::__construct([]);
        $this->workflow_type_table_alias = $workflow_type_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply(): void {
        if (!is_int($this->value) || $this->value < 1) {
            throw new coding_exception('workflow_type filter must have an id for value');
        }

        $this->builder->where("{$this->workflow_type_table_alias}.id", '=', $this->value);
    }
}