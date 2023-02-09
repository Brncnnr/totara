<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\watcher;

use coding_exception;
use container_workspace\discussion\discussion;
use container_workspace\loader\member\loader as member_loader;
use container_workspace\member\member;
use container_workspace\member\status;
use container_workspace\query\member\query as member_query;
use container_workspace\workspace;
use context;
use context_course;
use context_user;
use core\entity\user;
use core_container\factory;
use editor_weka\hook\find_context;
use editor_weka\hook\search_users_by_pattern;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_core\advanced_feature;
use totara_engage\loader\user_loader;
use totara_engage\query\user_query;
use totara_engage\query\user_tenant_query;

/**
 * Watcher for editor weka.
 */
final class editor_weka_watcher {
    /**
     * @param find_context $hook
     * @return void
     */
    public static function load_context(find_context $hook): void {
        global $DB;
        $component = $hook->get_component();

        if (workspace::get_type() !== $component) {
            return;
        }

        $area = $hook->get_area();
        if (discussion::AREA === $area) {
            $discussion_id = $hook->get_instance_id();

            if (null !== $discussion_id) {
                $workspace_id = $DB->get_field(
                    'workspace_discussion',
                    'course_id',
                    ['id' => $discussion_id]
                );

                $context = context_course::instance($workspace_id);
                $hook->set_context($context);
                return;
            }
        }

        if (workspace::DESCRIPTION_AREA === $area) {
            $workspace_id = $hook->get_instance_id();
            if (null !== $workspace_id) {
                $context = context_course::instance($workspace_id);
                $hook->set_context($context);
            }
        }
    }

    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    public static function on_search_users(search_users_by_pattern $hook): void {
        if (advanced_feature::is_disabled('container_workspace')) {
            // Workspace is disabled.
            return;
        }

        static::search_users_for_workspace_or_discussion($hook);
        static::search_users_for_comments($hook);
    }

    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    private static function search_users_for_workspace_or_discussion(search_users_by_pattern $hook): void {
        if ($hook->is_db_run()) {
            return;
        }

        $component = $hook->get_component();
        if (workspace::get_type() !== $component) {
            return;
        }

        $context_id = $hook->get_context_id();
        $context = context::instance_by_id($context_id);

        if (CONTEXT_COURSECAT != $context->contextlevel && CONTEXT_COURSE != $context->contextlevel) {
            debugging("Context level is not support by container_workspace", DEBUG_DEVELOPER);
            return;
        }

        $users = static::search_users($context, $hook->get_actor_id(), $hook->get_pattern());

        $hook->add_users($users);
        $hook->mark_db_run();
    }

    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    private static function search_users_for_comments(search_users_by_pattern $hook): void {
        if ($hook->is_db_run()) {
            return;
        }

        $component = $hook->get_component();
        if (comment::get_component_name() !== $component) {
            return;
        }

        comment_helper::validate_comment_area($hook->get_area());

        $context_id = $hook->get_context_id();
        $context = context::instance_by_id($context_id);

        // Only CONTEXT_COURSE is valid here.
        if (CONTEXT_COURSE != $context->contextlevel) {
            return;
        }

        $workspace = workspace::from_id($context->instanceid);

        if (!$workspace->is_typeof(workspace::get_type())) {
            // We do not want to fail different container, so skip it for now.
            return;
        }

        $users = static::search_users(
            $context,
            $hook->get_actor_id(),
            $hook->get_pattern()
        );

        $hook->add_users($users);
        $hook->mark_db_run();
    }

    /**
     * @param context $context
     * @param int $actor_id
     * @param string $pattern
     *
     * @return user[]
     */
    private static function search_users(context $context, int $actor_id, string $pattern): array {
        global $CFG, $USER;

        $user_context = context_user::instance($USER->id);

        if (CONTEXT_COURSE == $context->contextlevel) {
            $workspace_id = $context->instanceid;

            /** @var workspace $workspace */
            $workspace = factory::from_id($workspace_id);
            if (!$workspace->is_typeof(workspace::get_type())) {
                throw new \coding_exception("Cannot find workspace by id '{$workspace_id}'");
            }

            // Checking whether the user is a member of workspace first, so that we can allow the user
            // to search for something.
            $actor_member = member_loader::get_for_user($actor_id, $workspace_id);

            if (null === $actor_member || $actor_member->is_suspended()) {
                // Nope, user actor is not a member of the workspace.
                // Hence we are not allowing this user to search anything.
                return [];
            }

            // Within hidden workspace, we are only searching within the workspace members.
            // Same is true for system workspaces if the user is in a tenant
            if (($CFG->tenantsenabled && $user_context->tenantid && empty($context->tenantid))
                || $workspace->is_hidden()
            ) {
                $query = new member_query($workspace_id);
                $query->set_search_term($pattern);
                $query->set_member_status(status::get_active());

                if ($CFG->tenantsenabled && empty($context->tenantid) && !empty($CFG->tenantsisolated)) {
                    // Tenancy is enabled, the workspace is in system context,
                    // and isolation mode is on. Hence we don't want to include the tenant user
                    // in the list.
                    $query->set_include_tenant_users(false);
                }

                $paginator_result = member_loader::get_members($query);
                $members = $paginator_result->get_items()->all();

                return array_map(
                    function (member $member): user {
                        $user_record = $member->get_user_record();
                        return new user($user_record);
                    },
                    $members
                );
            }
        } else if (!empty($user_context->tenantid) && $user_context->tenantid !== $context->tenantid) {
            throw new coding_exception('User is not allowed to load users for the given context.');
        }

        // We are only including the system users when the course context is not within a tenant.
        $include_system_users = empty($context->tenantid);

        // For PUBLIC/PRIVATE workspace and the creation workspace, we will allow user actor
        // to search for all the users in the system. And of course, it is multi-tenancy aware.
        $user_query = user_query::create_with_exclude_guest_user(
            $context->id,
            new user_tenant_query($include_system_users)
        );

        $user_query->set_search_term($pattern);

        $paginator_result = user_loader::get_users($user_query);
        return $paginator_result->get_items()->all();
    }
}