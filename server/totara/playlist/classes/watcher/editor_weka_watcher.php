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
 * @package totara_playlist
 */
namespace totara_playlist\watcher;

use coding_exception;
use context;
use context_user;
use core\entity\user;
use core_user;
use dml_exception;
use editor_weka\hook\find_context;
use editor_weka\hook\search_users_by_pattern;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_core\advanced_feature;
use totara_engage\access\access_manager;
use totara_engage\engage_core;
use totara_engage\loader\user_loader;
use totara_engage\query\user_query;
use totara_playlist\playlist;

/**
 * Watcher for editor weka
 */
class editor_weka_watcher {
    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    public static function on_search_users(search_users_by_pattern $hook): void {
        if (advanced_feature::is_disabled('engage_resources')) {
            return;
        }

        static::on_search_users_for_playlist($hook);
        static::on_search_users_for_comment($hook);
    }

    /**
     * @param find_context $hook
     * @return void
     */
    public static function on_find_context(find_context $hook): void {
        global $USER;
        $component = $hook->get_component();
        if (playlist::get_resource_type() !== $component) {
            return;
        }

        $instance_id = $hook->get_instance_id();
        if (null !== $instance_id) {
            $playlist = playlist::from_id($instance_id);
            $hook->set_context($playlist->get_context());

            return;
        }

        // Instance is not defined yet, this could be about creating new playlist.
        $context = \context_user::instance($USER->id);
        $hook->set_context($context);
    }

    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    private static function on_search_users_for_playlist(search_users_by_pattern $hook): void {
        if ($hook->is_db_run()) {
            return;
        }

        $component = $hook->get_component();
        if (playlist::get_resource_type() !== $component) {
            return;
        }

        // This one is only for the summary (description) field
        if (playlist::SUMMARY_AREA !== $hook->get_area()) {
            return;
        }

        $actor_id = $hook->get_actor_id();
        $context = context::instance_by_id($hook->get_context_id());
        if (CONTEXT_USER != $context->contextlevel || $context->instanceid != $actor_id) {
            // We only allow the user's own context
            return;
        }

        $users = self::search_for_users(
            $context,
            $actor_id,
            $hook->get_pattern()
        );

        $hook->add_users($users);
        $hook->mark_db_run();
    }

    /**
     * @param search_users_by_pattern $hook
     * @return void
     */
    private static function on_search_users_for_comment(search_users_by_pattern $hook): void {
        global $CFG;

        if ($hook->is_db_run()) {
            return;
        }

        $component = $hook->get_component();
        if (comment::get_component_name() !== $component) {
            return;
        }

        $comment_id = $hook->get_instance_id();
        if (empty($comment_id)) {
            // Skips if comment id is empty, let the engage_watcher deal with it.
            return;
        }

        $context_id = $hook->get_context_id();
        $context = context::instance_by_id($context_id);

        if (CONTEXT_USER != $context->contextlevel) {
            // Playlist only works with context user.
            return;
        }

        try {
            comment_helper::validate_comment_area($hook->get_area());
        } catch (coding_exception $e) {
            return;
        }

        try {
            $comment = comment::from_id($comment_id);
        } catch (dml_exception $e) {
            // If the comment is not found, skip
            return;
        }

        if (playlist::get_resource_type() !== $comment->get_component()) {
            return;
        }

        $playlist_id = $comment->get_instanceid();
        $playlist = playlist::from_id($playlist_id);
        if (!access_manager::can_access($playlist, $hook->get_actor_id())) {
            return;
        }

        // The context should match the user's one from this playlist
        if ($context->instanceid != $playlist->get_userid()) {
            return;
        }

        // We don't want to expose other tenant's users to the user so fall back to the ones he can see
        $actor = core_user::get_user($hook->get_actor_id());
        if (!empty($CFG->tenantsenabled) && !empty($actor->tenantid) && empty($context->tenantid)) {
            $context = context_user::instance($hook->get_actor_id());
        }

        // Now this comment is for this article - we will start find users.
        $users = static::search_for_users(
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
    private static function search_for_users(context $context, int $actor_id, string $pattern): array {
        if (!engage_core::allow_access_with_tenant_check($context, $actor_id)) {
            return [];
        }

        $query = user_query::create_with_exclude_guest_user($context->id);
        $query->set_search_term($pattern);

        $result = user_loader::get_users($query);
        return $result->get_items()->all();
    }
}