<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 */

namespace core\entity;

use context;
use core\orm\collection;
use core\orm\entity\filter\basket;
use core\orm\entity\filter\in;
use core\orm\entity\filter\user_name;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use core\tenant_orm_helper;
use core\user_orm_helper;
use core_user\profile\display_setting;
use user_picture;

class user_repository extends repository {

    /**
     * @return array
     */
    protected function get_default_filters(): array {
        return [
            'basket' => new basket(),
            'text' => new user_name(),
            'ids' => new in('id')
        ];
    }

    /**
     * Filter only users who are confirmed
     *
     * @return $this
     */
    public function filter_by_confirmed(): self {
        $this->where('confirmed', 1);

        return $this;
    }

    /**
     * Filter only users not marked as deleted
     *
     * @return $this
     */
    public function filter_by_not_deleted(): self {
        $this->where('deleted', 0);

        return $this;
    }

    /**
     * Filter only users not marked as suspended
     *
     * @return $this
     */
    public function filter_by_not_suspended(): self {
        $this->where('suspended', 0);

        return $this;
    }

    /**
     * Only real users
     *
     * @return $this
     */
    public function filter_by_not_guest(): self {
        global $CFG;
        $guest_id = $CFG->siteguest;
        $this->where('id', '!=', $guest_id);

        return $this;
    }

    /**
     * Remove the current logged in user from the query.
     *
     * @return $this
     */
    public function filter_by_not_current_user(): self {
        $current_user = user::logged_in();

        if (!$current_user) {
            throw new \coding_exception('There must be a user logged in otherwise you can not use ' . __FUNCTION__ . '()!');
        }

        $this->where('id', '!=', $current_user->id);

        return $this;
    }

    /**
     * Search for users who's full names include a given string.
     * Note: This excludes guest users and deleted users.
     *
     * @param string $search_for Part of a user's name we are wanting to search for.
     *
     * @return $this
     */
    public function filter_by_full_name(string $search_for): self {
        user_orm_helper::filter_by_fullname($this->get_builder(), $search_for);

        return $this;
    }

    /**
     * Filter the users by email address.
     *
     * @param string $email
     * @param bool $case_sensitive Note: Two emails are generally treated the same regardless of if their casing is the same.
     * @return $this
     */
    public function filter_by_email(string $email, bool $case_sensitive = false): self {
        $sql = builder::get_db()->sql_equal($this->get_alias_sql() . '.email', ':email', $case_sensitive);
        $this->where_raw($sql, ['email' => $email]);

        return $this;
    }

    /**
     * Select fields required for displaying name of the user.
     *
     * @return $this
     */
    public function select_full_name_fields(): self {
        $fields = totara_get_all_user_name_fields(false, $this->get_alias_sql(), null, null, false);

        $this
            ->add_select('id')
            ->add_select($fields)
            ->group_by(array_merge(['id'], $fields));

        return $this;
    }

    /**
     * Select fields required to display each user's profile picture.
     *
     * @return $this
     */
    public function select_user_picture_fields(): self {
        $fields = user_picture::fields($this->get_alias_sql());

        $this
            ->add_select_raw($fields)
            ->group_by_raw($fields);

        return $this;
    }

    /**
     * Selects the fields required to display a user profile summary card.
     *
     * @see {/server/user/profile_summary_card_edit.php} Page for setting what fields are displayed on a site-level
     *
     * @param bool $include_profile_image
     * @return $this
     */
    public function select_profile_summary_card_fields(bool $include_profile_image = true): self {
        if ($include_profile_image && display_setting::display_user_picture()) {
            $this->select_user_picture_fields();
        }

        foreach (display_setting::get_display_fields() as $field_key => $field_name) {
            if ($field_name === null) {
                continue;
            }

            if ($field_name === 'fullname') {
                // 'fullname' is computed so we can't directly select it.
                $this->select_full_name_fields();
                continue;
            }

            $this
                ->add_select($field_name)
                ->group_by($field_name);
        }

        // Always include the ID.
        $this->add_select('id')->group_by('id');

        return $this;
    }

    /**
     * Order by the full name of the users.
     *
     * @return $this
     */
    public function order_by_full_name(): self {
        user_orm_helper::order_by_fullname($this->get_builder());

        return $this;
    }

    /**
     * Search for a user by search pattern
     *
     * @param context $context pass the context the search should be in relation to, usually the user context of current user
     * @param string $search_string a string to search for, currently this supports searching the fullname only
     * @param int $limit an optional limit
     * @param bool $include_guest optionally include the guest users, false by default
     * @param bool $filter_by_suspended optionally include the suspended users, false by default
     * @param bool $filter_by_current_user optionally include the current user, false by default
     * @return collection
     */
    public static function search(
        context $context,
        string $search_string = '',
        int $limit = 0,
        bool $include_guest = false,
        bool $filter_by_suspended = false, // Too late to filter it by default in the stable release
        bool $filter_by_current_user = false
    ): collection {
        return user::repository()
            ->when(true, function (self $repository) use ($context) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    new field('id', $repository->get_builder()),
                    $context
                );
            })
            ->filter_by_not_deleted()
            ->filter_by_confirmed()
            ->when(!$include_guest, function (self $repository) {
                $repository->filter_by_not_guest();
            })
            ->when($filter_by_suspended, function (self $repository) {
                $repository->filter_by_not_suspended();
            })
            ->when($filter_by_current_user, function (self $repository) {
                $repository->filter_by_not_current_user();
            })
            ->when(strlen($search_string) > 0, function (self $repository) use ($search_string) {
                $repository->filter_by_full_name($search_string);
            })
            ->order_by_full_name()
            ->when($limit > 0, function (self $repository) use ($limit) {
                $repository->limit($limit);
            })
            ->get();
    }
}
