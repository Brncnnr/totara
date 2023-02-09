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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\user;

use context_user;
use container_approval\approval as container_approval;
use core\entity\user;
use core\entity\user_repository;
use core\orm\entity\repository;
use core\orm\query\field;
use core\pagination\base_paginator;
use core\pagination\cursor;
use core\tenant_orm_helper;
use mod_approval\data_provider\provider;
use mod_approval\data_provider\cursor_paginator_trait;
use stdClass;

/**
 * Class selectable_users
 *
 * @package mod_approval\data_provider\user
 */
class selectable_users extends provider {

    use cursor_paginator_trait;

    /** @var user $user */
    public function __construct(user $user) {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    protected function build_query(): repository {
        global $CFG;
        $reference_context = container_approval::get_default_category_context();
        $viewing_user_context = context_user::instance($this->user->id);
        if ($viewing_user_context->tenantid) {
            // We base the user list on the tenant the selector is in, means the selector only sees users
            // in the same tenant as themselves.
            $reference_context = $viewing_user_context;
        }
        $userfields = $this->get_userfields();
        $user_query = user::repository()
            ->as('user')
            ->add_select('id')
            ->add_select($userfields)
            ->filter_by_not_guest()
            ->filter_by_not_deleted()
            ->filter_by_not_suspended()
            ->when(true, function (repository $repository) use ($reference_context) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    new field('id', $repository->get_builder()),
                    $reference_context
                );
            });
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
     * @param user_repository|repository $repository
     * @param string $substring
     */
    protected function filter_query_by_fullname(repository $repository, string $substring): void {
        $repository->filter_by_full_name($substring);
    }

    /**
     * Returns next page of users
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
