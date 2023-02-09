<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use engage_survey\totara_engage\resource\survey;
use totara_engage\access\access;
use totara_engage\webapi\middleware\require_valid_recipients;

/**
 * Mutation resolver for engage_survey_create
 */
class create extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return survey
     */
    public static function resolve(array $args, execution_context $ec): survey {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        if (isset($args['access']) && !is_numeric($args['access']) && is_string($args['access'])) {
            // Format the string access into a proper value that machine can understand.
            $access = access::get_value($args['access']);
            $args['access'] = $access;
        }

        /** @var survey $resource */
        $resource = survey::create($args, $USER->id);

        return $resource;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
            new require_valid_recipients('shares'),
        ];
    }

}