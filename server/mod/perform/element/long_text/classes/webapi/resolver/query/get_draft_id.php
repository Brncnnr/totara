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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

namespace performelement_long_text\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\activity;
use mod_perform\webapi\middleware\require_activity;
use performelement_long_text\long_text;

/**
 * Query get_draft_id
 *
 * Normally preparing the draft file area ID is a mutation, but in this case
 * it is a query in order for Apollo to appropriately cache the result in Vue.
 * This is required in order to support dynamic repeating long text instances,
 * where the same section element response is used for displaying multiple long text elements.
 *
 * @package performelement_long_text\webapi\resolver\query
 */
class get_draft_id extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');

        /** @var activity $activity */
        $activity = $args['activity'];
        $section_element_id = $args['section_element_id'];
        $participant_instance_id = $args['participant_instance_id'];

        /** @var element_response $element_response */
        $element_response = element_response::repository()
            ->where('section_element_id', $section_element_id)
            ->where('participant_instance_id', $participant_instance_id)
            ->one();

        $draft_id = 0;
        file_prepare_draft_area(
            $draft_id,
            $activity->get_context()->id,
            long_text::get_response_files_component_name(),
            long_text::get_response_files_filearea_name(),
            $element_response->id ?? null
        );

        return $draft_id;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
            require_activity::by_participant_instance_id('participant_instance_id', true),
        ];
    }

}
