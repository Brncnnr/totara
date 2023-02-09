<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant;

use context;
use totara_tenant\local\util as local_util;

defined('MOODLE_INTERNAL') || die();

/**
 * External tenant API methods.
 */
final class util {

    /**
     * Returns true if both context are either parts of the same tenant or both in the system context.
     * or if any user context is given if the user is participant of the same tenant.
     *
     * Triggers a debugging message if both are not in a tenant and returns false.
     *
     * @param context $context_a
     * @param context $context_b
     * @return bool
     */
    public static function do_contexts_share_same_tenant(context $context_a, context $context_b): bool {
        $tenants_a = $tenants_b = [];
        if (!empty($context_a->tenantid)) {
            $tenants_a = [$context_a->tenantid];
        }
        if (!empty($context_b->tenantid)) {
            $tenants_b = [$context_b->tenantid];
        }

        // If context  is a user context and potentially a tenant participant
        if ($context_a->contextlevel == CONTEXT_USER && empty($context_a->tenantid)) {
            $tenants_a = array_values(local_util::get_user_participation($context_a->instanceid));
        }
        if ($context_b->contextlevel == CONTEXT_USER && empty($context_b->tenantid)) {
            $tenants_b = array_values(local_util::get_user_participation($context_b->instanceid));
        }

        if (!empty(array_intersect($tenants_a, $tenants_b))) {
            return true;
        }

        // Both context share the same tenant
        if ($context_a->tenantid === $context_b->tenantid) {
            return true;
        }

        return false;
    }

}
