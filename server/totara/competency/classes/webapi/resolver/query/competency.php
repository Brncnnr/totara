<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

namespace totara_competency\webapi\resolver\query;

use core\orm\entity\entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\query_resolver;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * Query to return a single competency.
 */
class competency extends query_resolver {

    /**
     * Returns a competency, given its ID.
     *
     * @param array $args
     * @param execution_context $ec
     * @return competency_entity|entity
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }
        return competency_entity::repository()
            ->with(['framework', 'type'])
            ->where('id', $args['competency_id'])
            ->one();
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competencies'),
            new require_system_capability('totara/hierarchy:viewcompetency'),
        ];
    }

}