<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\data_providers;

use coding_exception;
use context_course;
use container_workspace\member\status;
use core\entity\user;
use core\entity\user_filters;
use core\orm\entity\repository;
use core\orm\entity\filter\filter_factory;
use core\orm\query\builder;
use core\tenant_orm_helper;
use totara_core\data_provider\provider;

/**
 * "Model" for dealing with collections of users who are not members of a given
 * workspace.
 */
class non_members extends provider {
    /**
     * @inheritDoc
     *
     * DO NOT USE THIS; the workspace id is required for this provider to work;
     * but this method is unfortunately required via the provider interface.
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        throw new coding_exception('Use create_for_workspace() method instead');
    }

    /**
     * Creates an instance of the provider targetting a specific workspace.
     *
     * @param int $workspace_id target workspace id. Also used to filter out
     *        users for multitenancy.
     * @param filter_factory|null $filter_factory object to create filters that
     *        this provider will use. Defaults to core\entity\user_filters if
     *        not provided.
     *
     * @return self the provider instance.
     */
    public static function create_for_workspace(
        int $workspace_id,
        ?filter_factory $filter_factory = null
    ): self {
        $actual_filter_factory = $filter_factory
            ? $filter_factory
            : new user_filters();

        $repository_factory = function () use ($workspace_id): repository {
            return self::create_repository($workspace_id);
        };

        return new self($repository_factory, $actual_filter_factory);
    }

    /**
     * Creates a factory method for creating a user repository.
     *
     * @param int $workspace_id target workspace id.
     * @param bool $exclude_admin if true, excludes admin users.
     *
     * @return self the repository instance.
     */
    private static function create_repository(
        int $workspace_id,
        bool $exclude_admin = false
    ): repository {
        $exists_query = builder::table('user_enrolments', 'ue')
            ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
            ->where_field('ue.userid', '"user".id')
            ->where('e.courseid', $workspace_id)
            ->where('status', status::get_active());

        $repo = user::repository()
            ->filter_by_not_deleted()
            ->filter_by_not_guest()
            ->filter_by_not_suspended()
            ->where_not_exists($exists_query)
            ->when(true, function (repository $repository) use ($workspace_id) {
                $alias = $repository->get_builder()->get_alias_sql();

                // Apply tenant query.
                $context = context_course::instance($workspace_id);
                tenant_orm_helper::restrict_users(
                    $repository->get_builder(),
                    "{$alias}.id",
                    $context
                );
            });

        if ($exclude_admin) {
            $repo->where('id', '!=', get_admin()->id);
        }

        return $repo;
    }

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'user';
    }

    /**
     * @inheritDoc
     */
    public static function get_summary_format_select() {
        // Not really relevant for user objects but have to return something.
        return 'id';
    }

    /**
     * Default constructor.
     *
     * DO NOT USE THIS DIRECTLY; use a virtual constructor method instead. Unfortunately,
     * cannot make this private because the constructor is public in the base class and
     * PHP7.x (unlike PHP8) cannot handle that.
     *
     * @param callable $repository_factory ()->repository method to create the user repository.
     * @param filter_factory $filter_factory
     */
    public function __construct(callable $repository_factory, filter_factory $filter_factory) {
        parent::__construct(
            $repository_factory(),
            ['id', 'firstname', 'lastname', 'username'],
            $filter_factory,
            $repository_factory
        );
    }
}