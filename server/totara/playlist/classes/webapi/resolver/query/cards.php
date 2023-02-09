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
 * @package totara_playlist
 */
namespace totara_playlist\webapi\resolver\query;

use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_engage\access\access_manager;
use totara_engage\card\card;
use totara_engage\query\query;
use totara_playlist\playlist;
use totara_playlist\totara_engage\card\loader;

class cards extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return card[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        // Do not expose internal exception out.
        try {
            $playlist = playlist::from_id($args['id']);
        } catch (\dml_exception $ex) {
            throw new \moodle_exception('error:permissiondenied', 'totara_playlist', '', null, $ex->getMessage());
        }

        if (!access_manager::can_access($playlist, $USER->id)) {
            throw new \moodle_exception('error:permissiondenied', 'totara_playlist', '', null, 'Cannot access item ' . $playlist->get_id());
        }

        if (isset($args['footnotes'])) {
            $footnotes = $args['footnotes'];
            if (isset($footnotes['item_id'])) {
                if ($footnotes['item_id'] != $playlist->get_id()) {
                    throw new \coding_exception("Footnotes are not from playlist with {$footnotes['item_id']}");
                }
            }

            if (isset($footnotes['type'])) {
                if ($footnotes['type'] != 'playlist') {
                    throw new \coding_exception("Footnotes type is invalid");
                }
            }

            if (isset($footnotes['area'])) {
                if ($footnotes['area'] != 'playlist') {
                    throw new \coding_exception("Footnotes area is invalid");
                }
            }

            if (isset($footnotes['component'])) {
                if ($footnotes['component'] != 'totara_playlist') {
                    throw new \coding_exception("Footnotes component is invalid");
                }
            }
        }

        $query = new query();
        $query->set_component('totara_playlist');
        $query->set_area('playlist');

        if (!empty($args['cursor'])) {
            $cursor = offset_cursor::decode($args['cursor']);
            $query->set_cursor($cursor);
        }

        $loader = new loader($query);
        $loader->set_playlist_id($args['id']);
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