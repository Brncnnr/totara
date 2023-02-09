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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_tenant
 */

namespace core;

use context;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to provider multi tenancy related methods for use in builder / repository instances
 *
 * @package totara_tenant
 */
class tenant_orm_helper {

    /**
     * Applies a multi tenancy restriction to the given builder / repository instance,
     * restricting the users to the ones who are participants of the same tenant as
     * the context given. If the context is empty or it does not belong to a context
     * users who belong to a tenant will be excluded.
     *
     * @param builder|repository $builder
     * @param string|field $user_id_column
     * @param context $context if omitted and isolation is on tenant members will be excluded
     * @return void
     */
    public static function restrict_users($builder, $user_id_column, context $context): void {
        global $CFG;

        if (!empty($CFG->tenantsenabled)) {
            // Making sure there's no collision with existing aliases
            $alias = uniqid('mtru');

            // Make sure we only load the users who are participants of the same
            // tenant the given context belongs to.
            if ($context->tenantid) {
                $exists_builder = builder::table('cohort_members')
                    ->as($alias)
                    ->join(['tenant', 't'], 'cohortid', 'cohortid')
                    ->where('t.id', $context->tenantid)
                    ->where_field('userid', $user_id_column);

                $builder->where_exists($exists_builder);
            } else if (!empty($CFG->tenantsisolated)) {
                // If multi tenancy is on with isolation and we are not in a context
                // which belongs to a tenant then exclude all users who are tenant members
                $builder->join(['user', $alias], $user_id_column, 'id')
                    ->where($alias.'.tenantid', null);
            }
        }
    }

}