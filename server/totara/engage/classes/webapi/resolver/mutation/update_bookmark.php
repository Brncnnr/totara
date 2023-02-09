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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_engage\bookmark\bookmark;

class update_bookmark extends mutation_resolver {

    /**
     * @param array             $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER, $DB;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        if (!isset($args['itemid'])) {
            throw new \coding_exception('ItemID is a required field.');
        }

        if (!isset($args['component'])) {
            throw new \coding_exception('Component is a required field.');
        }

        if (!isset($args['bookmarked'])) {
            throw new \coding_exception('Bookmarked is a required field.');
        }

        $itemid = $args['itemid'];
        $component = $args['component'];
        $bookmarked = $args['bookmarked'];

        $transaction = $DB->start_delegated_transaction();
        $bookmark = new bookmark($USER->id, $itemid, $component);

        // Do not expose internal exception out, so we just throw new moodle exception.
        try {
            if (!$bookmark->can_bookmark($USER->id)) {
                throw new \moodle_exception('error:permissiondenied', 'totara_engage', '', null, 'Cannot bookmark item ' . $itemid);
            }
        } catch (\Exception $ex) {
            throw new \moodle_exception('error:permissiondenied', 'totara_engage', '', null, 'Cannot bookmark item ' . $itemid);
        }

        $bookmarked ? $bookmark->add_bookmark() : $bookmark->remove_bookmark();
        $transaction->allow_commit();

        return true;
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