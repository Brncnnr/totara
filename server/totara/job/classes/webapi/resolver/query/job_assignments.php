<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totara.com>
 * @package totara_job
 */

namespace totara_job\webapi\resolver\query;

use coding_exception;
use core\entity\user;
use core\orm\pagination\cursor_paginator;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use core_user\external\user_helper;
use totara_job\data_provider\job_assignments as job_assignments_data_provider;
use totara_job\webapi\resolver\helper;
use context_system;
use context_tenant;

/**
 * Query to fetch job assignments for the External API.
 */
class job_assignments extends query_resolver {
    use helper;

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/job/lib.php');

        // Extract query from input args.
        $query = $args['query'] ?? [];

        // Ensure pagination structure is present
        $pagination = $query['pagination'] ?? ['limit' => cursor_paginator::DEFAULT_ITEMS_PER_PAGE, 'cursor' => ''];
        if (empty($pagination['limit'])) {
            $pagination['limit'] = cursor_paginator::DEFAULT_ITEMS_PER_PAGE;
        }
        if (!isset($pagination['cursor'])) {
            $pagination['cursor'] = '';
        }

        // Ensure sorting structure is present. Currently we only support parsing the first 'sort_input' item passed in.
        $sort = $query['sort'][0] ?? [];
        if (!empty($sort) && empty($sort['column'])) {
            throw new coding_exception("Required parameter 'sort.column' not being passed");
        }

        // Check capability.
        $current_user = user::logged_in();
        $api_user_obj = new \stdClass();
        $api_user_obj->id = $current_user->id;

        // Tenant user check.
        $tenant_id = $current_user->tenantid;
        if ($tenant_id) {
            user_helper::validate_tenant_by_id($tenant_id);
            $context = context_tenant::instance($tenant_id);
        }
        else { // System user check.
            $context = context_system::instance();
        }
        if (!has_any_capability(['moodle/user:viewdetails', 'moodle/user:viewalldetails'],  $context, $api_user_obj)) {
            throw new \moodle_exception('nopermissions', '', '', 'view job assignments');
        }

        // Create a new provider
        $provider = new job_assignments_data_provider($tenant_id);

        // No filters yet.
        $filters = [];

        // Enforce the tenant_id filter if current user is tenant member.
        if ($tenant_id) {
            $filters['tenant_id'] = $tenant_id;
        }
        return $provider
            ->add_filters($filters)
            ->sort_by($sort)
            ->get_page($pagination['cursor'], $pagination['limit']);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user()
        ];
    }
}
