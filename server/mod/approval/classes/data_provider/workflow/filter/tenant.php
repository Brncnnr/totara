<?php
/**
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\workflow\filter;

use core\orm\entity\filter\filter;
use core\orm\query\builder;

/**
 * Tenant filter.
 */
class tenant extends filter {

    /**
     * @var string
     */
    private $workflow_table_alias;

    /**
     * Workflow tenant filter constructor.
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
        global $CFG;

        if (!empty($this->value)) {
            $this->builder->join(['course_modules', 'cm'], function (builder $builder) {
                $builder->where_field('cm.course', "$this->workflow_table_alias.course_id");
            })->join(['context', 'ctx'], function (builder $builder) {
                $builder->where_field('instanceid', 'cm.id')
                    ->where('contextlevel', CONTEXT_MODULE);
            });

            if (!empty($CFG->tenantsisolated)) {
                // Tenant domain manager only can view workflows created by ownself.
                $this->builder->where('ctx.tenantid', $this->value);
            } else {
                // Isolation mode is disabled, tenant domain manager can view workflows created by site manager.
                $this->builder->where_raw("(ctx.tenantid =:tenant_id OR ctx.tenantid IS NULL)", ['tenant_id' => $this->value]);
            }
        }
    }
}