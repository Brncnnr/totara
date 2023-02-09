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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */

namespace engage_survey\totara_reaction\resolver;

use engage_survey\totara_engage\interactor\survey_interactor;
use engage_survey\totara_engage\resource\survey;
use totara_engage\access\access_manager;
use totara_reaction\resolver\base_resolver;

/**
 * Class survey_reaction_resovler
 * @package engage_survey\totara_reaction\resolver
 */
final class survey_reaction_resolver extends base_resolver {
    /**
     *
     * @param int $resourceid
     * @param int $userid
     * @param string $area
     *
     * @return bool
     */
    public function can_create_reaction(int $resourceid, int $userid, string $area): bool {
        if (survey::REACTION_AREA !== $area) {
            return false;
        }

        $survey = survey::from_resource_id($resourceid);

        // Confirm that the interactor can like this resource.
        $interactor = survey_interactor::create_from_accessible($survey, $userid);
        if (!$interactor->can_react()) {
            return false;
        }

        return access_manager::can_access($survey, $userid);
    }

    /**
     * @param int $resourceid
     * @param string $area
     * @return \context
     */
    public function get_context(int $resourceid, string $area): \context {
        $survey = survey::from_resource_id($resourceid);
        return $survey->get_context();
    }

    /**
     * @param int       $instance_id
     * @param int       $user_id
     * @param string    $area
     *
     * @return bool
     */
    public function can_view_reactions(int $instance_id, int $user_id, string $area): bool {
        if (survey::REACTION_AREA === $area) {
            $survey = survey::from_resource_id($instance_id);
            return access_manager::can_access($survey, $user_id);
        }

        throw new \coding_exception("Invalid area passed into the survey resolver: {$area}");
    }
}