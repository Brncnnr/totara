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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\loader\member;

use coding_exception;
use container_workspace\member\member;
use core\entity\user;
use core\entity\user_enrolment;
use core\orm\entity\repository;
use core\pagination\base_cursor;
use core\pagination\offset_cursor_paginator;

/**
 * Paginator class for processing members of a workspace.
 */
class paginator extends offset_cursor_paginator {

    /**
     * @inheritDoc
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        if (!$query instanceof repository) {
            throw new coding_exception('Expected either a builder or a repository object');
        }

        $page = $this->cursor->get_page();
        $limit = $this->cursor->get_limit();

        $builder_paginator = $query->paginate($page, $limit);

        if ($include_total) {
            $this->total = $builder_paginator->get_total();
        }

        $this->items = $builder_paginator->get_items()->map(function (user $user) {
            /** @var user_enrolment $user_enrolment_entity*/
            $user_enrolment_entity = $user->user_enrolments->first();
            $user_enrolment_record = $user_enrolment_entity->to_record();
            unset($user_enrolment_record->enrol_instance);

            // forced to unset time_enrolled to make it compatible with member::from_record.
            unset($user_enrolment_record->time_enrolled);
            $user_enrolment_record->workspace_id = $user->workspace_id;

            $member = member::from_record($user_enrolment_record);

            $user_record = $user->to_record();
            unset($user_record->workspace_id);
            unset($user_record->user_id);
            unset($user_record->user_enrolments);
            unset($user_record->timemodified);
            $member->set_user_record($user_record);

            // set cohorts.
            $cohorts = [];
            foreach ($user->user_enrolments as $user_enrolment) {
                if (!empty($user_enrolment->enrol_instance->cohort) && $user_enrolment->enrol_instance->enrol === 'cohort') {
                    $cohorts[] = $user_enrolment->enrol_instance->cohort;
                }
            }
            $member->set_audiences($cohorts);

            return $member;
        });

        return $this->create_next_cursor($page, $limit);
    }
}