<?php
/**
 * This file is part of Totara LMS
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
 * @author Qingyang Liu <qingyang.liy@totaralearning.com>
 * @package totara_topic
 */

namespace totara_topic\webapi\resolver\query;

use coding_exception;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_tag_area;

class get_config extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {

        $context = \context_user::instance(user::logged_in()->id);
        $ec->set_relevant_context($context);

        if (!isset($args['component']) || !isset($args['item_type'])) {
            throw new coding_exception('No required parameters being passed');
        }

        $component = $args['component'];
        $item_type = $args['item_type'];

        // We must return Boolean, not NULL.
        $enabled = core_tag_area::is_enabled($component, $item_type) ?? false;

        return [
            'enabled' => $enabled
        ];
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }
}