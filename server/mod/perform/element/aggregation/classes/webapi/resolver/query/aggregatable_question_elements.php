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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 */

namespace performelement_aggregation\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\data_providers\activity\sections as sections_provider;
use mod_perform\models\activity\section;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

/**
 * Get respondable section elements of activity.
 */
class aggregatable_question_elements extends query_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return section[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        return [
            'sections' => (new sections_provider())
                ->get_sections_with_aggregatable_section_elements($args['input']['activity_id'])
                ->map_to(section::class),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('input.activity_id', true),
            require_manage_capability::class
        ];
    }
}