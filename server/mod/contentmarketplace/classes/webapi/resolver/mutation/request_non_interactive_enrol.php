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
use container_course\course;
use core\webapi\execution_context;
use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\mutation_resolver;
use mod_contentmarketplace\interactor\content_marketplace_interactor;
use mod_contentmarketplace\model\content_marketplace;
use totara_contentmarketplace\course\enrol_manager;

/**
 * Self enrolment only for admin user and guest
 *
 * Mutation for Class supports_non_interactive_enrol
 */
class request_non_interactive_enrol extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        $cmid = $args['cm_id'];
        if (!isset($cmid)) {
            throw new coding_exception("Missing required field {$cmid}");
        }

        $cm_model = content_marketplace::from_course_module_id($cmid);
        $interactor = new content_marketplace_interactor($cm_model);

        if ($interactor->is_enrolled()) {
            throw new coding_exception('You have already enroled.');
        }

        if ($interactor->is_site_guest()) {
            throw new coding_exception('Site guest can not request self enrol');
        }

        // If self enrol configuration has been customised, you cannot do self enrol
        if (!$interactor->supports_non_interactive_enrol()) {
            throw new coding_exception('Not support non interactive enrol');
        }

        $course = course::from_id($cm_model->get_course_id());
        $manager = new enrol_manager($course);

        $manager->do_non_interactive_enrol($interactor->get_actor_id());
        return true;
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