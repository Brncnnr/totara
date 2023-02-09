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

use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_engage\card\card_loader;
use totara_engage\query\query;

class contributions extends query_resolver {
    /**
     * @var string[]
     */
    private static $areas = [
        'saved',
        'owned',
        'search',
        'shared'
    ];

    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        if ($args['component'] !== "totara_engage") {
            throw new \coding_exception("The component {$args['component']} is not supported.");
        }

        if (!in_array($args['area'], self::$areas)) {
            throw new \coding_exception("The area {$args['area']} is not supported.");
        }

        $query = new query();
        $query->set_component($args['component']);
        $query->set_area($args['area']);
        $query->set_filters($args['filter']);

        if (!empty($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        $loader = new card_loader($query);
        $paginator = $loader->fetch();

        return [
            'cursor' => $paginator,
            'cards' => $paginator->get_items()->all()
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
        ];
    }

}