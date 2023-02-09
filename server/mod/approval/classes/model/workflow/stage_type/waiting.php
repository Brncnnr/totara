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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 */

namespace mod_approval\model\workflow\stage_type;

use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_feature\interactions;
use mod_approval\model\workflow\stage_type\state_manager\waiting as waiting_state_manager;
use mod_approval\model\workflow\stage_type\state_manager\base as state_manager;
use mod_approval\model\workflow\workflow_stage;

/**
 * Workflow stage type that sits and waits for a timed event to progress.
 * Provides the features that can be configured on a workflow.
 */
class waiting extends base {

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('stage_type_waiting', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 25;
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'WAITING';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 30;
    }

    /**
     * @inheritDoc
     */
    public static function get_configured_features(): array {
        return [
            formviews::class,
            interactions::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_available_actions(): array {
        return [
            withdraw_in_approvals::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function state_manager(workflow_stage $workflow_stage): state_manager {
        return new waiting_state_manager($workflow_stage);
    }
}
