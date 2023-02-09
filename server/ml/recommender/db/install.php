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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Initialise some global configuration variables.
 *
 * @return bool
 */
function xmldb_ml_recommender_install(): bool {
    global $CFG;

    // Path to recommender data store.
    $data_path = "{$CFG->dataroot}/recommender/data";
    set_config('data_path', $data_path, 'ml_recommender');

    // Number of recommended items per user to cache.
    set_config('user_result_count', 5, 'ml_recommender');

    // Number of recommended items per item to cache.
    set_config('item_result_count', 5, 'ml_recommender');

    // Path to python.
    set_config('py3path', '/usr/bin/python3', 'ml_recommender');

    // Recommender algorithm type.
    set_config('query', 'hybrid', 'ml_recommender');

    // Threads/cores to use.
    set_config('threads', 2, 'ml_recommender');

    // Number of related items to list.
    set_config('related_items_count', 5, 'ml_recommender');

    // Number of weeks worth of user-item interactions to analyse.
    set_config('interactions_period', 16, 'ml_recommender');

    return true;
}
