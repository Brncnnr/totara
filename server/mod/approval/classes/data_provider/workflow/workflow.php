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

namespace mod_approval\data_provider\workflow;

use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\orm\query\sql\query;
use core\pagination\offset_cursor as cursor;
use mod_approval\data_provider\offset_cursor_paginator_trait;
use mod_approval\data_provider\provider;
use mod_approval\data_provider\workflow\filter\assignment_type;
use mod_approval\data_provider\workflow\filter\name;
use mod_approval\data_provider\workflow\filter\tenant;
use mod_approval\data_provider\workflow\filter\workflow_type;
use mod_approval\data_provider\workflow\filter\workflow_version_status;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\workflow\workflow as workflow_model;
use sqlsrv_native_moodle_database;

/**
 * Workflow data provider
 *
 * @package mod_approval\data_provider\workflow
 */
class workflow extends provider {

    use offset_cursor_paginator_trait;

    /**
     * Workflow table alias.
     *
     * @var string
     */
    private $workflow_table_alias = 'workflow';

    /**
     * Workflow version table alias.
     * @var string
     */
    private $workflow_version_table_alias = 'workflow_version';

    /**
     * @inheritDoc
     */
    protected function build_query(): repository {
        return workflow_entity::repository()
            ->with(['workflow_type', 'default_assignment'])
            ->as($this->workflow_table_alias)
            ->join(
                [workflow_version::TABLE, $this->workflow_version_table_alias],
                function (builder $builder) {
                    $field = $this->where_latest_workflow_version();
                    $builder->where_field("{$this->workflow_version_table_alias}.id", $field);
                }
            )
            ->where('active', true)
            ->select("$this->workflow_table_alias.*");
    }

    /**
     * Return the workflow_version join on active or draft or archived
     * for report builder
     * @see mod/approval/rb_sources/rb_source_approval_workflow.php (rb_source_approval_workflow::define_join_on)
     *
     * @return string
     */
    public function build_join_on(): string {
        return $this->where_latest_workflow_version()->sql();
    }

    /**
     * @return raw_field
     */
    private function where_latest_workflow_version(): raw_field {
        /**
         * ATTENTION: this query used in a report builder: see $this->build_join_on()
         * @see mod/approval/rb_sources/rb_source_approval_workflow.php
         * rb_source_approval_workflow::define_join_on
         */
        $out_alias = $this->workflow_table_alias;
        $in_alias = 'joining_latest_workflow_version';
        $builder = builder::table(workflow_version::TABLE, $in_alias)
            ->where_raw("{$in_alias}.workflow_id = {$out_alias}.id")
            ->order_by("{$in_alias}.id", 'DESC')
            ->limit(1)
            ->offset(0)
            ->select_raw("{$in_alias}.id");
        [$sql, $params] = query::from_builder($builder)->build();
        // Add limit & offset to the query.
        $sql .= ' ' . $this->get_latest_workflow_version_limit_sql();
        return raw_field::raw("({$sql})", $params);
    }

    /**
     * Limit part of sql used to pick the latest workflow status.
     *
     * @return string
     */
    private function get_latest_workflow_version_limit_sql(): string {
        return builder::get_db() instanceof sqlsrv_native_moodle_database
            ? "OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY"
            : "LIMIT 1";
    }

    /**
     * Map the application entities to their respective model class.
     *
     * @return collection|workflow_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(workflow_model::class);
    }

    /**
     * Filter by name or workflow id.
     *
     * @param repository $repository
     * @param string $name
     */
    protected function filter_query_by_name(repository $repository, string $name) {
        $repository->set_filter((new name($this->workflow_table_alias))->set_value($name));
    }

    /**
     * Filter by Active status.
     *
     * @param repository $repository
     * @param string $status_enum
     */
    protected function filter_query_by_status(repository $repository, string $status_enum) {
        $repository->set_filter((new workflow_version_status($this->workflow_version_table_alias))->set_value($status_enum));
    }

    /**
     * Filter by Assignment type.
     *
     * @param repository $repository
     * @param string $assignment_type_enum
     */
    protected function filter_query_by_assignment_type(repository $repository, string $assignment_type_enum) {
        $repository->set_filter((new assignment_type($this->workflow_table_alias))->set_value($assignment_type_enum));
    }

    /**
     * Filter by Workflow type id.
     *
     * @param repository $repository
     * @param int $workflow_type_id
     */
    protected function filter_query_by_workflow_type_id(repository $repository, int $workflow_type_id) {
        $repository->set_filter((new workflow_type($this->workflow_table_alias))->set_value($workflow_type_id));
    }

    /**
     * @param repository $repository
     * @param string $tenant_id
     */
    protected function filter_query_by_tenant_id(repository $repository, string $tenant_id) {
        $repository->set_filter((new tenant($this->workflow_table_alias))->set_value($tenant_id));
    }

    /**
     * Sort by name.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_name(repository $repository): void {
        $repository->order_by_raw("UPPER($this->workflow_table_alias.name) ASC")->order_by("$this->workflow_table_alias.id", 'DESC');
    }

    /**
     * Sort by updated date.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_updated(repository $repository): void {
        $repository->order_by("$this->workflow_table_alias.updated", 'DESC')->order_by("$this->workflow_table_alias.id", 'DESC');
    }

    /**
     * Sort by workflow id_number.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_id_number(repository $repository): void {
        $repository->order_by("$this->workflow_table_alias.id_number")->order_by("$this->workflow_table_alias.id");
    }

    /**
     * Sort by active status.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_status(repository $repository): void {
        $repository->order_by("$this->workflow_version_table_alias.status")->order_by("$this->workflow_table_alias.id");
    }

    /**
     * Get page of workflows.
     * Returns workflow interactors if user_id is provided.
     *
     * @param int $page
     * @param int $limit
     * @param int|null $user_id
     *
     * @return array
     */
    public function get_page(int $page, int $limit, int $user_id = null): array {
        $cursor = cursor::create([
            'page' => $page,
            'limit' => $limit,
        ]);

        $paginator = $this->get_paginator($cursor);
        $next_cursor = $paginator->get_next_cursor();
        $workflows = $paginator->get_items()->map_to(workflow_model::class);

        return [
            'items' => $workflows,
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null
                ? ''
                : $next_cursor->encode(),
        ];
    }
}