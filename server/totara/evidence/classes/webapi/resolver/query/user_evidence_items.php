<?php
/**
 * This file is part of Totara Learn
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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_core\hook\component_access_check;
use totara_evidence\data_providers\evidence as evidence_provider;
use totara_evidence\entity\evidence_item as evidence_entity;
use totara_evidence\models\evidence_item as evidence_model;
use totara_evidence\models\helpers\evidence_item_capability_helper;

class user_evidence_items extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Set current user as default if there is no user_id provided
        $user_id = $args['input']['user_id'] ?? user::logged_in()->id;

        // Check capability
        if (!evidence_item_capability_helper::for_user($user_id)->can_view_list()) {
             $hook = new component_access_check(
                'totara_evidence',
                user::logged_in()->id,
                $user_id,
                []
            );

            if (!$hook->execute()->has_permission()) {
                return [
                    'items' => [],
                    'total' => 0,
                    'next_cursor' => '',
                ];
            }
        }

        $args['input']['filters']['user_id'] = $user_id;

        return evidence_provider::create()
            ->set_filters($args['input']['filters'])
            ->set_page_size($args['input']['result_size'] ?? null)
            ->fetch_paginated(
                $args['input']['cursor'] ?? null,
                static function (evidence_entity $evidence) {
                    return evidence_model::load_by_entity($evidence);
                }
            );
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('evidence'),
        ];
    }
}
