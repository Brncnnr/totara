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
namespace engage_survey\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use engage_survey\totara_engage\resource\survey;
use totara_engage\access\access;
use totara_engage\exception\resource_exception;
use totara_engage\share\manager as share_manager;
use totara_engage\share\recipient\manager as recipient_manager;
use totara_engage\webapi\middleware\require_valid_recipients;

class update extends mutation_resolver {
    /**
     * Mutation resolver.
     *
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(array $args, execution_context $ec): survey {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $id = $args['resourceid'];
        $data = [];

        $survey = survey::from_resource_id($id);

        if (isset($args['access'])) {
            $value = access::get_value($args['access']);
            if (access::is_restricted($value) && empty($args['shares'])) {
                throw resource_exception::create('update', survey::get_resource_type());
            }
            if (access::is_public($value) && empty($args['topics'])) {
                throw resource_exception::create('update', survey::get_resource_type());
            }
            $data['access'] = $value;
        } else {
            $data['access'] = $survey->get_access();
        }

        if (isset($args['questions'])) {
            $data['questions'] = $args['questions'];
        }

        $survey->update($data, $USER->id);

        // Add/remove topics.
        if (!empty($args['topics'])) {
            $survey->remove_topics_by_ids($args['topics']);
            $survey->add_topics_by_ids($args['topics']);
        }

        // Shares
        if (!empty($args['shares'])) {
            $recipients = recipient_manager::create_from_array($args['shares']);
            share_manager::share($survey, survey::get_resource_type(), $recipients);
        }

        return $survey;
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