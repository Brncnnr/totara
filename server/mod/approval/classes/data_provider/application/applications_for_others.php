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
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\pagination\offset_cursor as cursor;
use core\pagination\cursor_paginator;
use invalid_parameter_exception;
use mod_approval\data_provider\application\filter\applicant_name;
use mod_approval\data_provider\application\filter\application_id;
use mod_approval\data_provider\application\filter\overall_progress;
use mod_approval\data_provider\application\filter\workflow_type_id;
use mod_approval\data_provider\application\filter\your_progress;
use mod_approval\data_provider\offset_cursor_paginator_trait;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_repository;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\model\application\application as application_model;

/**
 * Class applications_for_others
 *
 * @package mod_approval\data_provider\application
 *
 * @method collection|application_model[] get
 */
class applications_for_others extends application_provider_base {

    use offset_cursor_paginator_trait;

    /**
     * Get all the defined capability_map classes that this provider uses.
     *
     * @return array
     */
    public static function capability_map_classes(): array {
        return [
            'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_any',
            'mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_user',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_any',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_application_user',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_any',
            'mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_user'
        ];
    }

    /**
     * Filter the repository by one or more application IDs.
     *
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
     * Filter the repository by workflow_type name.
     *
     * @param application_repository|repository $repository
     * @param string $name
     */
    protected function filter_query_by_workflow_type_name(repository $repository, string $name): void {
        $workflow_type = workflow_type::repository()->where('name', '=', $name)->one();
        if (is_null($workflow_type)) {
            throw new invalid_parameter_exception('No workflow_type by that name');
        }
        $repository->set_filter(
            (new workflow_type_id('workflow_type'))->set_value($workflow_type->id)
        );
    }

    /**
     * Filter the repository by overall_progress value.
     *
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
     * Filter the repository by your_progress value.
     *
     * @param application_repository|repository $repository
     * @param string $state
     */
    protected function filter_query_by_your_progress(repository $repository, string $state): void {
        $repository->set_filter(
            (new your_progress($this->user_id, 'application'))->set_value($state)
        );
    }

    /**
     * Filter the repository by applicant name.
     *
     * @param application_repository|repository $repository
     * @param string $partial_name
     */
    protected function filter_query_by_applicant_name(repository $repository, string $partial_name): void {
        $repository->set_filter(
            (new applicant_name('applicant'))->set_value($partial_name)
        );
    }

    /**
     * Sort the results by created date, newest first.
     *
     * @param repository $repository
     */
    protected function sort_query_by_newest_first(repository $repository): void {
        $repository->order_by('created', 'DESC')->order_by('id', 'DESC');
    }

    /**
     * Sort the results by created date, oldest first.
     *
     * @param repository $repository
     */
    protected function sort_query_by_oldest_first(repository $repository): void {
        $repository->order_by('created', 'ASC')->order_by('id', 'ASC');
    }

    /**
     * Sort the results by application submitted date, latest first but draft applications last.
     *
     * @param repository $repository
     */
    protected function sort_query_by_submitted(repository $repository): void {
        // By submitted date newest first. Application in DRAFT state in the end
        $repository->order_by_raw('COALESCE(application.submitted, ' . PHP_INT_MIN . ') DESC, id DESC');
    }

    /**
     * Sort the results by workflow_type name.
     *
     * @param repository $repository
     */
    protected function sort_query_by_workflow_type_name(repository $repository): void {
        // By workflow_type, newest first.
        $repository->order_by('workflow_type.name', 'ASC')->order_by('id', 'DESC');
    }

    /**
     * Sort the results by application title.
     *
     * @param repository $repository
     */
    protected function sort_query_by_title(repository $repository): void {
        // By title, newest first.
        $repository->order_by_raw("UPPER({$repository->get_alias()}.title) ASC")->order_by('id', 'DESC');
    }

    /**
     * Sort the results by application ID Number.
     *
     * @param repository $repository
     */
    protected function sort_query_by_id_number(repository $repository): void {
        // By id_number, newest first.
        $repository->order_by_raw("UPPER({$repository->get_alias()}.id_number) ASC")->order_by('id', 'DESC');
    }

    /**
     * Sort the results by applicant name.
     *
     * @param repository $repository
     */
    protected function sort_query_by_applicant_name(repository $repository): void {
        // By applicant name, newest first.
        $name_concat = builder::get_db()->sql_concat_join("' '", totara_get_all_user_name_fields_join('applicant', null, true));
        $repository->order_by(new raw_field($name_concat), 'ASC')->order_by('id', 'DESC');
    }

    /**
     * Build query for applications that are visible to, but do not belong to, this user.
     *
     * @return application_repository
     */
    protected function build_query(): repository {
        $query = application_entity::repository()
            ->as('application')
            ->select(['application.*', 'workflow_type.name'])
            ->join(['approval_workflow_version', 'workflow_version'], 'application.workflow_version_id', '=', 'id')
            ->join(['approval_workflow', 'workflow'], 'workflow_version.workflow_id', '=', 'id')
            ->join(['approval_workflow_type', 'workflow_type'], 'workflow.workflow_type_id', '=', 'id')
            ->join([\core\entity\user::TABLE, 'applicant'], function (builder $builder) {
                $builder->where_field('id', '=', 'application.user_id')
                    ->where('id', '!=', $this->user_id)
                    ->where('deleted', '=', 0);
            })
            ->join(['approval', 'assignment'], 'approval_id', 'id');

        // Apply capability maps to the repository query.
        $this->apply_capability_map_joins($query);

        // Apply capability maps (unless owner is user)
        $query->where(function (builder $builder) {
            $builder->where("owner_id", $this->user_id);
            // Must have some capability if not owner.
            if ($this->has_any_capability()) {
                $builder->or_where(function (builder $builder) {
                    $this->apply_capability_map_conditions($builder);
                });
            }
        });

        /**
         * This is handy for troubleshooting.
         *
         * $builder = $query->get_builder();
         * [$sql, $params] = query::from_builder($builder)->build();
         * exit(print_r([$sql, $params]));
         */

        return $query;
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