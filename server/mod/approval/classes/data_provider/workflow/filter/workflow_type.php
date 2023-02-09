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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\data_provider\workflow\filter;

use core\orm\entity\filter\filter;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
/**
 * Workflow type filter.
 *
 * @package mod_approval\data_provider\workflow\filter
 */
class workflow_type extends filter {

    /**
     * @var string
     */
    private $workflow_table_alias;

    /**
     * Workflow type filter constructor.
     *
     * @param string $workflow_table_alias
     */
    public function __construct(string $workflow_table_alias) {
        parent::__construct([]);
        $this->workflow_table_alias = $workflow_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply() {
        $workflow_type_id = (int)$this->value;

        $this->builder
            ->join([workflow_type_entity::TABLE, 'wft'], 'wft.id', "$this->workflow_table_alias.workflow_type_id")
            ->where('wft.id', $workflow_type_id);
    }
}