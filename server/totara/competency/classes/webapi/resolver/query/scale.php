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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\query;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\query_resolver;
use totara_competency\models\scale as scale_model;
use core\entity\user;

/**
 * Query to return a single competency scale.
 */
class scale extends query_resolver {

    /**
     * Returns a competency scale, given its ID or competency id.
     *
     * @param array $args
     * @param execution_context $ec
     * @return scale_model
     */
    public static function resolve(array $args, execution_context $ec): scale_model {
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance(user::logged_in()->id));
        }

        $possible_id_args = ['id', 'competency_id', 'framework_id'];
        self::enforce_single_id_arg($args, $possible_id_args);

        if (isset($args['id'])) {
            return scale_model::load_by_id_with_values($args['id']);
        }

        if (isset($args['competency_id'])) {
            return scale_model::find_by_competency_id($args['competency_id']);
        }

        return scale_model::find_by_framework_id($args['framework_id']);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competencies'),
            new require_system_capability('totara/hierarchy:viewcompetency')
        ];
    }

    /**
     * @param array $args
     * @param string[] $possible_id_args
     */
    private static function enforce_single_id_arg(array $args, array $possible_id_args): void {
        $id_arg_count = 0;

        foreach ($possible_id_args as $possible_id_arg) {
            if (isset($args[$possible_id_arg])) {
                $id_arg_count++;
            }
        }

        if ($id_arg_count !== 1) {
            $possible_id_args = array_map(function (string $possible_arg) {
               return "\"{$possible_arg}\"";
            }, $possible_id_args);

            throw new coding_exception('Please provide ' . implode(' OR ', $possible_id_args));
        }
    }

}