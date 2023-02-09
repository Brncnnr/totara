<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\data_provider\user;

use container_approval\approval as container_approval;
use context;
use context_user;
use core\entity\user;
use core\entity\user_repository;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use core\pagination\base_paginator;
use core\pagination\cursor;
use core\tenant_orm_helper;
use mod_approval\data_provider\cursor_paginator_trait;
use mod_approval\data_provider\provider;
use stdClass;
use totara_core\access;

/**
 * Base class for selectable applicants.
 */
abstract class selectable_applicants_base extends provider {

    use cursor_paginator_trait;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var context
     */
    protected $reference_context;

    /**
     * @param int $user_id
     */
    public function __construct(int $user_id) {
        $this->user_id = $user_id;
        $this->set_reference_context();
    }

    /**
     * Set reference context used to limit users to tenant.
     */
    private function set_reference_context() {
        $reference_context = container_approval::get_default_category_context();
        $viewing_user_context = context_user::instance($this->user_id);

        if ($viewing_user_context->tenantid) {
            // We base the user list on the tenant the selector is in, means the selector only sees users
            // in the same tenant as themselves.
            $reference_context = $viewing_user_context;
        }

        $this->reference_context = $reference_context;
    }

    /**
     * When user does not have create_application_any capability, limit to users where they have create_application_user.
     *
     * @param repository $repository
     * @return repository
     */
    protected function limit_by_capability(repository $repository, context $reference_context): repository {
        /**
         * TODO: Look up the specific contexts where user has create_application_any, and try to limit the list of users
         *     to applicants who are eligible to create applications in those contexts.
         */

        // If the user has create_application_any (workflow manager) in any context, they see everyone in the tenant.
        $has_create_application_any_capability = has_capability_in_any_context('mod/approval:create_application_any');

        // If not, we can limit the list to users where this user has create_application_user
        if (!$has_create_application_any_capability) {
            $repository->join(['context', 'ctx'], 'id', 'instanceid')
                ->where('ctx.contextlevel', CONTEXT_USER)
                ->where(function (builder $builder) {
                    [$sql, $params] = access::get_has_capability_sql(
                        'mod/approval:create_application_user',
                        'ctx.id',
                        $this->user_id
                    );
                    $builder->where_raw($sql, $params);
                });
        }

        return $repository;
    }

    /**
     * @param user_repository|repository $repository
     * @param string $substring
     */
    protected function filter_query_by_fullname(repository $repository, string $substring): void {
        $repository->filter_by_full_name($substring);
    }

    /**
     * Get user query.
     *
     * @param context $capability_context
     * @return repository
     */
    protected function get_user_query(context $capability_context): repository {
        $userfields = $this->get_userfields();
        $user_query = user::repository()
            ->as('u')
            ->add_select_raw('distinct "u".id')
            ->add_select($userfields)
            ->filter_by_not_guest()
            ->filter_by_not_deleted()
            ->filter_by_not_suspended();

        $user_query = $this->limit_by_capability($user_query, $capability_context);

        // Always limit by tenant if needed.
        tenant_orm_helper::restrict_users(
            $user_query,
            new field('id', $user_query->get_builder()),
            $this->reference_context
        );

        foreach ($userfields as $field) {
            $user_query->order_by($field);
        }

        return $user_query->order_by('id');
    }

    /**
     * Returns not nullable userfields for sorting
     *
     * @return array
     */
    private function get_userfields(): array {
        global $CFG;
        $override = new stdClass();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamedisplay = $CFG->fullnamedisplay;
        if ($fullnamedisplay === 'language') {
            $fullnamedisplay = get_string('fullnamedisplay', '', $override);
        }
        $firstnamepos = strpos($fullnamedisplay, 'firstname');
        $lastnamepos = strpos($fullnamedisplay, 'lastname');
        if ($firstnamepos === false) {
            if ($lastnamepos === false) {
                return ['firstname', 'lastname']; // order by default
            } else {
                return ['lastname']; // lastname only
            }
        } else {
            if ($lastnamepos === false) {
                return ['firstname']; // firstname only
            } else if ($firstnamepos <= $lastnamepos) {
                return ['firstname', 'lastname']; // firstname is first
            } else {
                return ['lastname', 'firstname']; // lastname is first
            }
        }
    }

    /**
     * Returns page of users
     *
     * @param string|null $page_cursor
     * @param int $page_size
     * @return array
     */
    public function get_page(?string $page_cursor = null, int $page_size = base_paginator::DEFAULT_ITEMS_PER_PAGE): array {
        $cursor = $page_cursor !== null
            ? cursor::decode($page_cursor)
            : cursor::create()->set_limit($page_size);
        $paginated_results = $this->get_next($cursor, true)->get();
        $paginated_results['items'] = array_map(function ($user) {
            return new user($user->id);
        }, $paginated_results['items']);

        return $paginated_results;
    }
}