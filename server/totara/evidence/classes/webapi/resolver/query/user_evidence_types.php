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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\webapi\resolver\query;

use core\entity\user;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_evidence\entity\evidence_item;
use totara_evidence\entity\evidence_type;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_evidence\models\helpers\evidence_item_capability_helper;

class user_evidence_types extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Set current user as default if there is no user_id provided
        $user_id = $args['input']['user_id'] ?? user::logged_in()->id;

        // Check capability
        if (!evidence_item_capability_helper::for_user($user_id)->can_view_list()) {
            return ['items' => []];
        }

        return [
            'items' => evidence_type::repository()
                ->as('et')
                ->where_exists(builder::table(evidence_item::TABLE)
                    ->where_field('typeid', 'et.id')
                    ->where('user_id', $user_id)
                )
                ->order_by('name')
                ->get()
                ->map_to(evidence_type_model::class)
                ->all()
        ];
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
