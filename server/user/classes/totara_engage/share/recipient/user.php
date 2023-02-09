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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace core_user\totara_engage\share\recipient;

use context_user;
use core_user;
use theme_config;
use totara_engage\access\access;
use totara_engage\engage_core;
use totara_engage\entity\share as share_entity;
use totara_engage\exception\share_exception;
use totara_engage\loader\user_loader;
use totara_engage\query\user_query;
use totara_engage\repository\share_repository;
use totara_engage\share\recipient\helper as recipient_helper;
use totara_engage\share\recipient\recipient;
use totara_engage\share\shareable;
use totara_job\job_assignment;

class user extends recipient {

    /**
     * Area identifying this recipient.
     */
    public const AREA = 'user';

    /**
     * @inheritDoc
     */
    public function validate(): void {
        global $DB;

        $user_record = $DB->get_record('user', ['id' => $this->instanceid]);
        if (!$user_record || ($user_record->deleted || $user_record->suspended || !$user_record->confirmed)) {
            throw new share_exception('error:invalid_recipient', 'totara_engage');
        }
    }

    /**
     * @param shareable $item
     * @return void
     */
    public function validate_against_share_item(shareable $item): void {
        $context = $item->get_context();
        if (!engage_core::allow_access_with_tenant_check($context, $this->instanceid)) {
            throw new share_exception('error:invalid_recipient', 'totara_engage');
        }
    }

    /**
     * @param int $actor_id
     * @return void
     */
    public function validate_against_actor(int $actor_id): void {
        if ($actor_id == $this->instanceid) {
            // Actor happens to be the same as the recipient - skip the rest.
            return;
        }

        if (!engage_core::can_interact_with_user_in_tenancy_check($actor_id, $this->instanceid)) {
            // The user actor cannot access to the recipient context.
            // Hence this user actor should not be able to share the content to the recipient.
            throw new share_exception('error:invalid_recipient', 'totara_engage');
        }
    }

    /**
     * @inheritDoc
     */
    public function get_label(): string {
        return get_string('users', 'moodle');
    }

    /**
     * @inheritDoc
     */
    public function get_summary(): string {
        $ja = job_assignment::get_first($this->instanceid, false);
        return $ja ? $ja->fullname : '';
    }

    /**
     * @inheritDoc
     */
    public function get_data(?theme_config $theme_config = null) {
        return \core_user::get_user($this->instanceid);
    }

    /**
     * @inheritDoc
     */
    public function get_minimum_access(): int {
        return access::RESTRICTED;
    }

    /**
     * @inheritDoc
     * @return user[]
     */
    public static function search(string $search, ?shareable $instance): array {
        global $USER;

        $actor_context = context_user::instance($USER->id);
        $context_id = $actor_context->id;

        if (null !== $instance) {
            // Start looking for the instance context.
            $context = $instance->get_context();
            $context_id = $context->id;

            if (empty($context->tenantid)) {
                // The context that we are trying to search for user is in the system context.
                // Hence we can fallback to the actor's context, if the user context of actor
                // is within a tenant.
                // This happens because we would want to filtering out all the other tenant's members when actor
                // is trying to search for a user within a context system.
                if (!empty($actor_context->tenantid)) {
                    $context_id = $actor_context->id;
                }
            }
        }

        $query = user_query::create_with_exclude_guest_user($context_id);
        $query->set_search_term($search);

        if (null !== $instance) {
            // Exclude the shareable item owner.
            $owner_id = $instance->get_userid();
            $query->exclude_user($owner_id);
        }

        $result_paginator = user_loader::get_users($query);
        $user_ids = $result_paginator->get_items()->pluck('id');

        $recipients = [];
        foreach ($user_ids as $user_id) {
            $recipients[] = new self($user_id);
        }

        return $recipients;
    }

    /**
     * @inheritDoc
     */
    public static function is_user_permitted(shareable $instance, int $user_id): bool {
        // If this user is a recipient of this share then the user should be permitted.
        /** @var share_repository $repo */
        $repo = share_entity::repository();
        return $repo->is_recipient(
            $instance->get_id(),
            $instance::get_resource_type(),
            $user_id,
            self::AREA,
            recipient_helper::get_component(static::class)
        );
    }

}