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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\webapi\resolver\mutation;

use coding_exception;
use completion_info;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\mutation_resolver;
use mod_contentmarketplace\exception\completion_not_enabled;
use mod_contentmarketplace\interactor\content_marketplace_interactor;
use mod_contentmarketplace\model\content_marketplace;

/**
 * Mutation for class set_self_completion
 */
class set_self_completion extends mutation_resolver {
    /**
     *  @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): bool {
        if (!isset($args['cm_id']) || !isset($args['status'])) {
            throw new coding_exception('Missing required field');
        }

        $cmid = $args['cm_id'];
        $status = $args['status'];
        $cm_model = content_marketplace::from_course_module_id($cmid);

        (new content_marketplace_interactor($cm_model))->can_view();

        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'contentmarketplace');

        // Check the module supports manual completion.
        // We do this before we check if completion is enabled as this is cheap.
        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            throw new completion_not_enabled('Activity\'s self completion is disabled');
        }

        if (!$cm->uservisible) {
            throw new coding_exception('Activity is not visible');
        }

        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            throw new completion_not_enabled('Course\'s self completion is disabled');
        }

        if (!$status && $completion->is_completed_via_rpl($cm,  user::logged_in()->id)) {
            throw new coding_exception('Activity\'s has completed via RPL');
        }

        $target_state = ($status) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        $completion->update_state($cm, $target_state);
        return $target_state;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login_course_via_coursemodule('cm_id')
        ];
    }
}