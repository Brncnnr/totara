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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\assignment;

use core\collection;
use core\orm\entity\repository;
use core\pagination\offset_cursor as cursor;
use core\pagination\cursor_paginator;
use mod_approval\data_provider\assignment\filter\name;
use mod_approval\data_provider\offset_cursor_paginator_trait;
use mod_approval\data_provider\provider;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\helper\assignments_for_workflow_stage;
use mod_approval\model\status;

/**
 * Class override_assignments_for_workflow
 *
 * @package mod_approval\data_provider\assignment
 *
 * @method collection|assignment_model[] get
 */
class override_assignments_for_workflow_stage extends provider {

    use offset_cursor_paginator_trait;

    /**
     * Assignment table alias.
     *
     * @var string
     */
    private $assignment_table_alias = 'assignment';

    /**
     * @var int
     */
    protected $workflow_stage_id;

    /**
     * @param int $workflow_stage_id The id of the workflow stage for which we are loading override assignment.
     */
    public function __construct(int $workflow_stage_id) {
        $this->workflow_stage_id = $workflow_stage_id;
    }

    /**
     * Sort by name.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_name_asc(repository $repository): void {
        $repository
            ->order_by_raw("UPPER({$this->assignment_table_alias}.name) ASC")
            ->order_by_raw($this->assignment_table_alias . '.id ASC');
    }

    /**
     * Sort by name.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_name_desc(repository $repository): void {
        $repository
            ->order_by_raw("UPPER({$this->assignment_table_alias}.name) DESC")
            ->order_by_raw($this->assignment_table_alias . '.id DESC');
    }

    /**
     * Filter by name or workflow id.
     *
     * @param repository $repository
     * @param string $name
     */
    protected function filter_query_by_name(repository $repository, string $name) {
        $repository->set_filter((new name($this->assignment_table_alias))->set_value($name));
    }

    /**
     * Build query for override assignments that belong to this workflow.
     *
     * @return repository
     */
    protected function build_query(): repository {
        global $CFG;
        require_once($CFG->dirroot . "/totara/coursecatalog/lib.php");

        return assignment_entity::repository()
            ->as($this->assignment_table_alias)
            ->select(['*'])
            ->join([workflow::TABLE, 'workflow'], $this->assignment_table_alias . '.course', '=', 'course_id')
            ->join([workflow_version::TABLE, 'version'], 'workflow.id', '=', 'workflow_id')
            ->join([workflow_stage::TABLE, 'stage'], 'version.id', '=', 'workflow_version_id')
            ->where('stage.id', $this->workflow_stage_id)
            ->where('status', '!=', status::ARCHIVED)
            ->where('is_default', false);
    }

    /**
     * Map the override assignment entities to their respective model class.
     *
     * @return collection|assignment_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(assignment_model::class);
    }

    /**
     * Returns next page of override assignments.
     *
     * @param int $page_size
     * @param int $page_requested
     * @return \stdClass
     */
    public function get_page(int $page_size = cursor_paginator::DEFAULT_ITEMS_PER_PAGE, int $page_requested = 1): \stdClass {
        $cursor = cursor::create()->set_limit($page_size)->set_page($page_requested);
        $paginator = $this->get_paginator($cursor);
        $items = $paginator->get_items()->map_to(assignment_model::class);
        $next_cursor = $paginator->get_next_cursor();

        return (object)[
            'items' => assignments_for_workflow_stage::get($items, $this->workflow_stage_id),
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null ? '' : $next_cursor->encode(),
        ];
    }
}
