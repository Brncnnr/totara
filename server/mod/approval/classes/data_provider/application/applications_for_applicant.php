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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application;

use core\collection;
use core\entity\user;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\pagination\offset_cursor as cursor;
use core\pagination\cursor_paginator;
use mod_approval\data_provider\application\filter\application_id;
use mod_approval\data_provider\application\filter\application_title;
use mod_approval\data_provider\application\filter\overall_progress;
use mod_approval\data_provider\offset_cursor_paginator_trait;
use mod_approval\data_provider\provider;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_repository;
use mod_approval\model\application\application as application_model;

/**
 * Class applications_for_applicant
 *
 * @package mod_approval\data_provider\application
 *
 * @method collection|application_model[] get
 */
class applications_for_applicant extends application_provider_base {

    use offset_cursor_paginator_trait;

    /**
     * @var int
     */
    protected $applicant_id;

    /**
     * @var array
     */
    protected $capability_maps = [];

    /**
     * @param int $applicant_id The id of the user for whom we are loading applications.
     */
    public function __construct(int $applicant_id) {
        $this->applicant_id = $applicant_id;
        $this->user_id = $applicant_id;
        $this->require_capability_maps();
    }

    /**
     * @param application_repository|repository $repository
     * @param int|array $application_ids Application ID(s)
     */
    protected function filter_query_by_application_id(repository $repository, $application_ids): void {
        if (!is_array($application_ids)) {
            $application_ids = [$application_ids];
        }

        $repository->set_filter(
            (new application_id('application'))->set_value($application_ids)
        );
    }

    /**
     * @param application_repository|repository $repository
     * @param string[] $states one or more values of approval_application.state
     */
    protected function filter_query_by_overall_progress(repository $repository, array $states): void {
        if (!is_array($states)) {
            $states = [$states];
        }

        $repository->set_filter(
            (new overall_progress('application'))->set_value($states)
        );
    }

    /**
     * @param application_repository|repository $repository
     * @param string $title
     */
    protected function filter_query_by_application_title(repository $repository, string $title): void {
        $repository->set_filter((new application_title())->set_value($title));
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_newest_first(repository $repository): void {
        $repository->order_by('created', 'DESC')->order_by('id', 'DESC');
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_oldest_first(repository $repository): void {
        $repository->order_by('created', 'ASC')->order_by('id', 'ASC');
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_submitted(repository $repository): void {
        // By submitted date newest first. Application in DRAFT state in the end
        $repository->order_by_raw('COALESCE(application.submitted, ' . PHP_INT_MIN . ') DESC, id DESC');
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_workflow_type_name(repository $repository): void {
        // By workflow_type, newest first.
        $repository->order_by('workflow_type.name', 'ASC')->order_by('id', 'DESC');
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_title(repository $repository): void {
        // By title, newest first.
        $repository->order_by_raw("UPPER({$repository->get_alias()}.title) ASC")->order_by('id', 'DESC');
    }

    /**
     * @param repository $repository
     */
    protected function sort_query_by_id_number(repository $repository): void {
        // By id_number, newest first.
        $repository->order_by_raw("UPPER({$repository->get_alias()}.id_number) ASC")->order_by('id', 'DESC');
    }

    /**
     * Build query for applications that belong to this applicant.
     *
     * @return application_repository
     */
    protected function build_query(): repository {
        $query = application_entity::repository()
            ->as('application')
            ->join(['approval_workflow_version', 'workflow_version'], 'application.workflow_version_id', '=', 'id')
            ->join(['approval_workflow', 'workflow'], 'workflow_version.workflow_id', '=', 'id')
            ->join(['approval_workflow_type', 'workflow_type'], 'workflow.workflow_type_id', '=', 'id')
            ->select(['*', 'workflow_type.name'])
            ->join([user::TABLE, 'applicant'], function (builder $builder) {
                $builder->where_field('id', '=', 'application.user_id')
                    ->where('id', '=', $this->applicant_id)
                    ->where('deleted', '=', 0);
            })
            ->join(['approval', 'assignment'], 'approval_id', 'id');

        // Apply capability maps to the repository query.
        $this->apply_capability_map_joins($query);

        // Apply capability maps (unless draft and owner is user)
        $query->where(function (builder $builder) {
            $builder->where("owner_id", $this->applicant_id);
            // Must have some capability if not owner.
            if ($this->has_any_capability()) {
                $builder->or_where(function (builder $builder) {
                    $this->apply_capability_map_conditions($builder);
                });
            }
        });
        return $query;
    }

    /**
     * Get all of the defined capability_map classes.
     *
     * @return array
     */
    public static function capability_map_classes(): array {
        return [
            'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_any',
            'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_applicant',
            'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_user',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_any',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_applicant',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_user'
        ];
    }

    /**
     * Returns next page of applications
     *
     * @param int $page_size
     * @param int $page_requested
     * @return \stdClass
     */
    public function get_page(int $page_size = cursor_paginator::DEFAULT_ITEMS_PER_PAGE, int $page_requested = 1): \stdClass {
        $cursor = cursor::create()->set_limit($page_size)->set_page($page_requested);
        $paginator = $this->get_paginator($cursor);
        $items = $paginator->get_items()->map_to(application_model::class);
        $next_cursor = $paginator->get_next_cursor();
        return (object) [
            'items' => $items,
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null ? '' : $next_cursor->encode(),
        ];
    }
}
