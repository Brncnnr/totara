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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams;

use context_system;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use totara_msteams\my\helpers\catalog_helper;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * This is the external API for totara_msteams.
 */
class external extends external_api {

    /**
     * Parameter definitions of search_catalog.
     *
     * @return external_function_parameters
     */
    public static function search_catalog_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search term', VALUE_DEFAULT, 0),
            'from' => new external_value(PARAM_INT, 'from', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'limit', VALUE_DEFAULT, 10)
        ]);
    }

    /**
     * Return the list of catalogue items as template data for totara_msteams/listbox_items.
     *
     * @param string|null $query
     * @param integer $from
     * @param integer $limit
     * @return array
     * @throws moodle_exception $id or $f is wrong
     * @throws coding_exception $type is wrong
     */
    public static function search_catalog(?string $query, int $from, int $limit): array {
        global $PAGE;
        // A valid page context is required for the catalog images.
        $PAGE->set_context(context_system::instance());

        $objects = catalog_helper::search($query, $from, $limit + 1);
        $i = 0;
        $items = [];
        $more = false;
        foreach ($objects as $object) {
            if ($i++ >= $limit) {
                $more = true;
                break;
            }
            if (empty($object->link->url)) {
                continue;
            }
            $item = [
                'title' => $object->name,
                'subtitle' => $object->type,
                'type' => $object->objecttype,
                'text' => strip_tags($object->summary, '<u><s><b><i><strong><em><strike><br>'),
                'url' => $object->link->url
            ];
            if (!empty($object->image)) {
                $item['image'] = [
                    'src' => $object->image->url,
                    'alt' => $object->image->alt
                ];
            }
            $items[] = $item;
        }

        $result = ['items' => $items, 'from' => $from, 'limit' => count($items), 'more' => $more];
        if ($from === 0 && empty($objects)) {
            $result['empty'] = get_string('botfw:mx_nomatches', 'totara_msteams');
        }
        return $result;
    }

    /**
     * Returns an object that describes the structure of the return from render_session_list.
     *
     * @return external_description|null
     */
    public static function search_catalog_returns(): ?external_description {
        // It's not possible to define variable structures in this function.
        return null;
    }
}
