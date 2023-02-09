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
 * @package mod_approval
 */

namespace mod_approval\data_provider\workflow\filter;

use core\orm\entity\filter\filter;
use mod_approval\exception\model_exception;
use mod_approval\model\status;

/**
 * Workflow version status filter.
 */
class workflow_version_status extends filter {

    /**
     * @var string
     */
    private $workflow_version_table_alias;

    /**
     * Workflow version status filter constructor.
     *
     * @param string $workflow_version_table_alias
     */
    public function __construct(string $workflow_version_table_alias) {
        parent::__construct([]);
        $this->workflow_version_table_alias = $workflow_version_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply() {
        if (trim($this->value) === '') {
            return;
        }
        $status_enum = strtolower($this->value);

        if (!status::is_valid_enum($status_enum)) {
            throw new model_exception('Invalid status enum provided.');
        }
        $method = "filter_$status_enum";

        $this->{$method}();
    }

    /**
     * Filter draft workflows.
     *
     * @return void
     */
    private function filter_draft(): void {
        $this->builder->where("$this->workflow_version_table_alias.status", status::DRAFT);
    }

    /**
     * Filter active workflows.
     *
     * @return void
     */
    private function filter_active(): void {
        $this->builder->where("$this->workflow_version_table_alias.status", status::ACTIVE);
    }

    /**
     * Filter archived workflows.
     *
     * @return void
     */
    private function filter_archived(): void {
        $this->builder->where("$this->workflow_version_table_alias.status", status::ARCHIVED);
    }
}
