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
 * @package totara_engage
 */
namespace totara_engage\watcher;

use coding_exception;
use context;
use context_user;
use core_user;
use editor_weka\hook\search_users_by_pattern;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_core\advanced_feature;
use totara_engage\engage_core;
use totara_engage\loader\user_loader;
use totara_engage\query\user_query;

class editor_weka_watcher {
    /**
     * This is to search users when editor weka is within comment only.
     *
     * @param search_users_by_pattern $hook
     * @return void
     */
    public static function on_search_users(search_users_by_pattern $hook): void {
        global $CFG;

        if ($hook->is_db_run()) {
            return;
        }

        if (advanced_feature::is_disabled('engage_resources')) {
            return;
        }

        $component = $hook->get_component();
        if (comment::get_component_name() !== $component) {
            return;
        }

        // This is only for new comments, we cannot have any id yet
        $comment_id = $hook->get_instance_id();
        if (!empty($comment_id)) {
            return;
        }

        $context_id = $hook->get_context_id();
        $actor_id = $hook->get_actor_id();

        $context = context::instance_by_id($context_id);

        if (CONTEXT_USER != $context->contextlevel) {
            // We will skip this search.
            return;
        }

        try {
            comment_helper::validate_comment_area($hook->get_area());
        } catch (coding_exception $e) {
            return;
        }

        if (!engage_core::allow_access_with_tenant_check($context, $actor_id)) {
            // Note that we are not marking DB run, it is because it could be
            // meant for different component using comment.
            return;
        }

        // We don't want to expose other tenant's users to the user so fall back to the ones he can see
        $actor = core_user::get_user($hook->get_actor_id());
        if (!empty($CFG->tenantsenabled) && !empty($actor->tenantid) && empty($context->tenantid)) {
            $context = context_user::instance($hook->get_actor_id());
        }

        $query = user_query::create_with_exclude_guest_user($context->id);
        $query->set_search_term($hook->get_pattern());

        $paginator_result = user_loader::get_users($query);
        $users = $paginator_result->get_items()->all();

        $hook->add_users($users);
        $hook->mark_db_run();
    }
}