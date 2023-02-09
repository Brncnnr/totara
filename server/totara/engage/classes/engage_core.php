<?php
/**
 * This file is part of Totara LMS
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
 * @author  Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage;

use totara_core\advanced_feature;
use context;
use context_user;
use coding_exception;

/**
 * Library class for Totara Engage.
 *
 * Contains static methods only, that are to be treated as public API.
 */
final class engage_core {
    /**
     * @return bool
     */
    public static function allow_view_user_profile(): bool {
        $features = [
            'engage_resources',
            // 'ml_recommender', Not required for recommenders. Left here for posterity only as this is an Engage feature.
            // 'totara_msteams', Not required for MS teams. Left here for posterity only as this is an Engage feature.
        ];
        foreach ($features as $feature) {
            if (advanced_feature::is_enabled($feature)) {
                return true;
            }
        }
        return false;
    }

    /**
     * An extended multi tenancy check for actor user.
     * The conditions are:
     *
     * + If the actor cannot access to the target context, then function will return FALSE.
     * + With isolation mode is on, if the actor is a system user and not participate to the same tenant as
     *   target context, then function will return FALSE
     *
     * @param context $target_context
     * @param int $actor_id
     *
     * @return bool
     */
    public static function allow_access_with_tenant_check(context $target_context, int $actor_id): bool {
        global $CFG;

        if (empty($CFG->tenantsenabled)) {
            return true;
        }

        // Check with the system first.
        if ($target_context->is_user_access_prevented($actor_id)) {
            // Tenant says no.
            return false;
        }

        if (!empty($CFG->tenantsisolated)) {
            // Custom check in engage, as we only need to check if the actor is a participant of the
            // same tenant with owner. This should only happen when tenants isolation mode is on.
            $target_tenant_id = $target_context->tenantid;
            if (null !== $target_tenant_id) {
                return static::is_user_part_of_tenant($target_tenant_id, $actor_id);
            }
        }

        return true;
    }

    /**
     * Checking whether the user is a part of tenant or not. Either a member or participant.
     *
     * @param int $tenant_id
     * @param int $user_id
     *
     * @return bool
     */
    public static function is_user_part_of_tenant(int $tenant_id, int $user_id): bool {
        global $CFG, $DB;

        if (empty($tenant_id)) {
            throw new coding_exception("Target tenant's id is empty");
        } else if (!$CFG->tenantsenabled) {
            throw new coding_exception("Tenancy feature is not enabled");
        }

        $user_context = context_user::instance($user_id);
        if ($tenant_id == $user_context->tenantid) {
            return true;
        }

        $sql = '
            SELECT 1 FROM "ttr_cohort_members" cm
            INNER JOIN "ttr_tenant" t ON t.cohortid = cm.cohortid
            WHERE cm.userid = :user_id
            AND t.id = :tenant_id
        ';

        return $DB->record_exists_sql(
            $sql,
            [
                'user_id' => $user_id,
                'tenant_id' => $tenant_id
            ]
        );
    }

    /**
     * A helper to check whether the user one is able to see user two or not.
     *
     * @param int $actor_id
     * @param int $target_user_id
     *
     * @return bool
     */
    public static function can_interact_with_user_in_tenancy_check(int $actor_id, int $target_user_id): bool {
        global $CFG;

        if (!$CFG->tenantsenabled) {
            // By default, every user is able to see each other anyway.
            return true;
        }

        $target_context = context_user::instance($target_user_id);
        $actor_context = context_user::instance($actor_id);

        if (null === $actor_context->tenantid && null !== $target_context->tenantid) {
            // Actor is in the system and target user is in tenant.
            // Hence we should allow the actor to see target without isolation mode.
            if (empty($CFG->tenantsisolated)) {
                // Isolation mode is off - yes, system user is able to see tenant user.
                return true;
            }

            return static::is_user_part_of_tenant($target_context->tenantid, $actor_id);
        } else if (null !== $actor_context->tenantid && null === $target_context->tenantid) {
            // Actor is in tenant.
            if (empty($CFG->tenantsisolated)) {
                // Isolation mode is off - yes, tenant user is able to see system user.
                return true;
            }

            // Isolation is on, hence we have to check if the target user is within the same tenant as this actor
            return self::is_user_part_of_tenant($actor_context->tenantid, $target_user_id);
        }

        return $actor_context->tenantid == $target_context->tenantid;
    }
}