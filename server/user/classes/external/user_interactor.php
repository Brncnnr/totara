<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totara.com>
 * @package core_user
 */

namespace core_user\external;

use context_coursecat;
use context_system;
use context_tenant;
use context_user;
use core\entity\user;
use core\record\tenant;

/**
 * A helper class that is constructed with creator user's id, which helps
 * to fetch all the available actions that the user can perform.
 *
 * The main purpose is to expose whether a user can manage other users.
 */
class user_interactor {
    /**
     * The user id of the user who is interacting.
     *
     * @var int
     */
    private $actor_user_id;

    /**
     *  The tenant id of the user who is interacting.
     *
     * @var int
     */
    private $actor_tenant_id;

    /**
     * The target user context to check against.
     *
     * @var context_user
     */
    private $target_user_context;

    /**
     * Constructor
     *
     * @param int $actor_user_id
     */
    public function __construct(
        int $actor_user_id,
        int $target_user_id = null
    ) {
        $user = new user($actor_user_id);
        $this->actor_user_id = $user->id;
        if ($user->tenantid) {
            user_helper::validate_tenant_by_id($user->tenantid);
        }
        $this->actor_tenant_id = $user->tenantid;
        if ($target_user_id) {
            $target_user = new user($target_user_id);
            $this->target_user_context = context_user::instance($target_user->id);
        }
    }

    /**
     * Check if actor can create a system user
     *
     * @return bool
     */
    public function can_create_system_user(): bool {
        global $CFG;
        // If for some reason actor is tenanted, but tenants aren't enabled, fail.
        if (!$CFG->tenantsenabled && $this->actor_tenant_id) {
            return false;
        }
        // Actor is not tenant member, check system capability.
        return $this->has_system_user_create_capability();
    }

    /**
     * Check if actor can create a user member in given tenancy
     *
     * @param int $tenant_id
     * @return bool
     */
    public function can_create_tenant_user(int $tenant_id): bool {
        global $CFG;
        // Check tenancy enabled.
        if (!$CFG->tenantsenabled) {
            return false;
        }
        // If actor is tenanted, check tenant capability. Otherwise, check system capability.
        if ($this->actor_tenant_id) {
            return $this->has_tenant_user_create_capability($tenant_id);
        } else {
            return $this->has_system_user_create_capability();
        }
    }

    /**
     * Check if actor has 'totara/tenant:usercreate' capability in tenant context
     *
     * @param int $tenant_id
     * @return bool
     */
    public function has_tenant_user_create_capability(int $tenant_id): bool {
        return has_capability('totara/tenant:usercreate', context_tenant::instance($tenant_id), $this->actor_user_id);
    }

    /**
     * Check if actor has 'moodle/user:create' capability in the system context
     *
     * @return bool
     */
    public function has_system_user_create_capability(): bool {
        return has_capability('moodle/user:create', context_system::instance(), $this->actor_user_id);
    }

    /**
     * Check if actor can update particular user
     *
     * @return bool
     */
    public function can_update_user(): bool {
        if ($this->target_user_context == null) {
            throw new \coding_exception('You must to create interactor object with a target user');
        }

        // We need to explicitely check this as not all capabilities are checked in the user context
        if ($this->target_user_context->is_user_access_prevented($this->actor_user_id)) {
            return false;
        }
        return $this->has_user_update_capability() ||
            $this->has_user_editprofile_capability();
    }

    /**
     * Check if actor has 'moodle/user:update' capability in the system context
     *
     * @return bool
     */
    public function has_user_update_capability(): bool {
        return has_capability('moodle/user:update', context_system::instance(), $this->actor_user_id);
    }

    /**
     * Check if actor has 'moodle/user:editprofile' capability in a user context
     *
     * @return bool
     */
    public function has_user_editprofile_capability(): bool {
        return has_capability('moodle/user:editprofile', $this->target_user_context, $this->actor_user_id);
    }

    /**
     * Check if actor has 'moodle/user:managelogin' capability in a user context
     *
     * @return bool
     */
    public function has_user_managelogin_capability(): bool {
        return has_capability('moodle/user:managelogin', $this->target_user_context, $this->actor_user_id);
    }

    /**
     * Check if actor has 'totara/tenant:manageparticipants' capability in the system context
     *
     * @return bool
     */
    public function has_tenancy_update_capability(): bool {
        return has_capability('totara/tenant:manageparticipants', context_system::instance(), $this->actor_user_id);
    }

    /**
     * Check if actor can delete particular user
     *
     * @return bool
     */
    public function can_delete_user(): bool {
        if ($this->target_user_context == null) {
            throw new \coding_exception('You must to create interactor object with a target user');
        }

        // We need to explicitely check this as not all capabilities are checked in the user context
        if ($this->target_user_context->is_user_access_prevented($this->actor_user_id)) {
            return false;
        }
        return $this->has_user_delete_capability();
    }

    /**
     * Check if actor has 'moodle/user:delete' capability in a user context
     *
     * @return bool
     */
    public function has_user_delete_capability(): bool {
        return has_capability('moodle/user:delete', $this->target_user_context, $this->actor_user_id);
    }

    /**
     * Check if actor can view users.
     * If you need to check view capabilities for particular user, please use access_controller.php
     *
     * @return bool
     */
    public function can_view(): bool {
        global $CFG;

        // If for some reason actor is tenanted, but tenants aren't enabled, fail.
        if (!$CFG->tenantsenabled && $this->actor_tenant_id) {
            return false;
        }
        if ($this->actor_tenant_id) {
            return $this->has_user_viewalldetails_capability_in_tenant_context($this->actor_tenant_id) ||
                $this->has_user_viewparticipants_capability_in_tenant_context($this->actor_tenant_id);
        } else {
            return $this->has_user_viewalldetails_capability_in_system_context();
        }
    }

    /**
     * Check if actor has 'moodle/user:viewalldetails' capability in a tenant context
     *
     * @param int $tenant_id
     * @return bool
     */
    public function has_user_viewalldetails_capability_in_tenant_context(int $tenant_id): bool {
        return has_capability('moodle/user:viewalldetails', context_tenant::instance($tenant_id), $this->actor_user_id);
    }

    /**
     * Check if actor has 'totara/tenant:viewparticipants' capability in a coursecategory context
     *
     * @param int $tenant_id
     * @return bool
     */
    public function has_user_viewparticipants_capability_in_tenant_context(int $tenant_id): bool {
        $tenant = tenant::fetch($tenant_id);
        return has_capability('totara/tenant:viewparticipants', context_coursecat::instance($tenant->categoryid), $this->actor_user_id);
    }

    /**
     * Check if actor has 'moodle/user:delete' capability in the system context
     *
     * @return bool
     */
    public function has_user_viewalldetails_capability_in_system_context(): bool {
        return has_capability('moodle/user:viewalldetails', context_system::instance(), $this->actor_user_id);
    }
}
