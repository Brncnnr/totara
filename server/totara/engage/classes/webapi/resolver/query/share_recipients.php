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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core\theme\helper as theme_helper;
use totara_engage\access\access_manager;
use totara_engage\access\accessible;
use totara_engage\entity\share as share_entity;
use totara_engage\repository\share_repository;
use totara_engage\share\helper;
use totara_engage\share\provider as share_provider;

/**
 * Resolver for querying share recipients.
 */
class share_recipients extends query_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        if (!isset($args['itemid'])) {
            throw new \coding_exception('ItemID is a required field.');
        }

        if (!isset($args['component'])) {
            throw new \coding_exception('Component is a required field.');
        }

        $component = $args['component'];
        $itemid = $args['itemid'];

        if (!empty($itemid)) {
            try {
                $item = share_provider::create($component)->get_item_instance($itemid);
            } catch (\Exception $ex) {
                throw new \moodle_exception('error:permissiondenied', 'totara_engage', '', null, $ex->getMessage());
            }

            if ($item instanceof accessible) {
                if (!access_manager::can_access($item, $USER->id)) {
                    throw new \moodle_exception('error:permissiondenied', 'totara_engage', '', null, 'Cannot access item ' . $item->get_id());
                }
            } else {
                // Something that is shareable must be accessible. It is a one way binding.
                throw new \moodle_exception('error:permissiondenied', 'totara_engage', '', null, get_class($item));
            }
        }

        /** @var share_repository $repo */
        $repo = share_entity::repository();
        $recipients = $repo->get_recipients($itemid, $component);

        if (!empty($recipients)) {
            $theme_config = theme_helper::load_theme_config($args['theme'] ?? null);
            $recipients = helper::format_recipients($recipients, $theme_config);
        }

        return $recipients;
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