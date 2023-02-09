<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTDvs
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
 * @package core_user
 */

namespace core\data_providers;

use context_user;
use core\entity\user;
use core\entity\user_filters;
use core\orm\entity\repository;
use core\orm\entity\filter\filter_factory;
use core\orm\query\field;
use core\tenant_orm_helper;
use totara_core\data_provider\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * "Model" for dealing with collections of users.
 */
class users extends provider {
    /**
     * @inheritDoc
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        return self::create_active_users_provider(null, $filter_factory);
    }

    /**
     * Creates an instance of the provider targeting active, non-admin and non-guest
     * users.
     *
     * @param int|null $user_id indicates the user who is going to use this
     *        data provider. Used to filter out users for multitenancy. Defaults
     *        to the currently logged on user if not provided.
     * @param filter_factory|null $filter_factory object to create filters that
     *        this provider will use. Defaults to core\entity\user_filters if
     *        not provided.
     * @param bool $exclude_inactive
     * @param bool $exclude_guest
     * @param bool $exclude_admin
     * @return static
     */
    public static function create_active_users_provider(
        ?int $user_id = null,
        ?filter_factory $filter_factory = null,
        bool $exclude_inactive = true,
        bool $exclude_guest = true,
        bool $exclude_admin = true
    ): self {
        $actual_user = $user_id ?: user::logged_in()->id;
        $actual_filter_factory = $filter_factory ?: new user_filters();

        $repository_factory = function () use ($actual_user, $exclude_inactive, $exclude_guest, $exclude_admin): repository {
            return self::create_repository($actual_user, $exclude_inactive, $exclude_guest, $exclude_admin);
        };

        return (new self($repository_factory, $actual_filter_factory))
            ->set_user_id($actual_user);
    }

    /**
     * Creates a factory method for creating a user repository.
     *
     * @param int $user_id indicates the user who is going to use this data provider.
     */
    private static function create_repository(
        int $user_id,
        bool $exclude_inactive,
        bool $exclude_guest = true,
        bool $exclude_admin = true
    ): repository {
        $repo = user::repository()
            ->when(true, function (repository $repository) use ($user_id) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    new field('id', $repository->get_builder()),
                    context_user::instance($user_id)
                );
            });

        if ($exclude_guest) {
            $repo->filter_by_not_guest();
        }

        if ($exclude_admin) {
            $repo->where('id', '!=', get_admin()->id);
        }

        if ($exclude_inactive) {
            $repo->filter_by_not_deleted()->filter_by_not_suspended();
        } else {
            $repo->filter_by_not_deleted();
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
            ['id', 'firstname', 'lastname', 'username', 'timemodified'],
            $filter_factory,
            $repository_factory
        );
    }
}