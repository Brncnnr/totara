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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

function ml_recommender_upgrade_default_configuration() {
    $new_init_values = [
        'query' => [
            'old_init_value' => 'mf',
            'new_init_value' => 'hybrid',
        ],
        'user_result_count' => [
            'old_init_value' => 25,
            'new_init_value' => 5,
        ],
        'item_result_count' => [
            'old_init_value' => 15,
            'new_init_value' => 5,
        ],
        'related_items_count' => [
            'old_init_value' => 3,
            'new_init_value' => 5,
        ]
    ];

    // Update the config only if:
    //      ( i) it exists;
    //      (ii) it is still at the original default value.
    foreach ($new_init_values as $key => $values) {
        $existing_config = get_config('ml_recommender', $key);
        if (!empty($existing_config) && $existing_config == $values['old_init_value']) {
            set_config($key, $values['new_init_value'], 'ml_recommender');
        } else {
            set_config($key, $existing_config, 'ml_recommender');
        }
    }
}
