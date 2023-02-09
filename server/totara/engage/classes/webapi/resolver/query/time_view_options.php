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
 * @package totara_engage
 */
namespace totara_engage\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use totara_engage\timeview\time_view;

class time_view_options extends query_resolver {

    /**
     * Query resolver.
     *
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        return [
            [
                'value' => time_view::get_code(time_view::LESS_THAN_FIVE),
                'label' => time_view::get_string(time_view::LESS_THAN_FIVE)
            ],
            [
                'value' => time_view::get_code(time_view::FIVE_TO_TEN),
                'label' => time_view::get_string(time_view::FIVE_TO_TEN)
            ],
            [
                'value' => time_view::get_code(time_view::MORE_THAN_TEN),
                'label' => time_view::get_string(time_view::MORE_THAN_TEN)
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('engage_resources'),
        ];
    }

}