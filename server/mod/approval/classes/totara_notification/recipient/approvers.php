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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\totara_notification\recipient;

use mod_approval\model\application\application as application_model;
use mod_approval\model\workflow\workflow_stage_approval_level;
use totara_notification\recipient\recipient;

/**
 * Class approvers
 *
 * The approvers for the application at the given level.
 *
 * @package mod_approval\totara_notification\recipient
 */
class approvers implements recipient {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('notification:recipient_approvers', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_user_ids(array $data): array {
        $application = application_model::load_by_id($data['application_id']);
        $approval_level = workflow_stage_approval_level::load_by_id($data['approval_level_id']);
        return $application->get_approver_users($approval_level)->keys();
    }
}