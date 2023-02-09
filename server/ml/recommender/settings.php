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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\local\environment;

defined('MOODLE_INTERNAL') || die;

// List of integers 1 through 50.
$options = [];
for ($i = 1; $i < 51; $i++) {
    $options[$i] = $i;
}

// Periods for interactions - 2 weeks to a year.
$periods = [];
for ($i = 2; $i < 53; $i++) {
    $periods[$i] = new lang_string('interactions_period_option','ml_recommender', $i);
}

// ML queries.
$queries = [
    'hybrid' => new lang_string('query_option_hybrid','ml_recommender'),
    'partial' => new lang_string('query_option_partial','ml_recommender'),
    'mf' => new lang_string('query_option_mf','ml_recommender'),
];

// Recommender system configuration.
$settings->add(
    new admin_setting_heading(
        'ml_recommender/recsysconfigs',
        get_string('recsysconfigs', 'ml_recommender'),
        get_string('recsysconfigs', 'ml_recommender')
    )
);

// Related items.
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/related_items_count',
        new lang_string('related_items_count_label','ml_recommender'),
        new lang_string('related_items_count_help', 'ml_recommender'),
        environment::get_related_items_count(),
        $options
    )
);

// Number of items-to-user recommendations
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/user_result_count',
        new lang_string('user_result_count_label','ml_recommender'),
        new lang_string('user_result_count_help', 'ml_recommender'),
        environment::get_user_result_count(),
        $options
    )
);

// Number of items-for-items records to return from recommender.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/item_result_count',
        new lang_string('item_result_count_label','ml_recommender'),
        new lang_string('item_result_count_help', 'ml_recommender'),
        environment::get_item_result_count(),
        $options
    )
);

// ML query.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/query',
        new lang_string('query_label','ml_recommender'),
        new lang_string('query_help', 'ml_recommender'),
        environment::get_query(),
        $queries
    )
);

// Time to analyse.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/interactions_period',
        new lang_string('interactions_period_label','ml_recommender'),
        new lang_string('interactions_period_help', 'ml_recommender'),
        environment::get_interactions_period(),
        $periods
    )
);

// Python executable.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configexecutable('ml_recommender/py3path',
        new lang_string('py3path_label', 'ml_recommender'),
        new lang_string('py3path_help', 'ml_recommender'),
        environment::get_py3path()
    )
);

// Threads.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
 */
$settings->add(
    new admin_setting_configselect(
        'ml_recommender/threads',
        new lang_string('threads_label','ml_recommender'),
        new lang_string('threads_help', 'ml_recommender'),
        environment::get_threads(),
        $options
    )
);

// Data path.
/**
 * @deprecated since Totara 17: This ml_recommender settings has been deprecated along with the ml_recommender service.
*/
$settings->add(
    new admin_setting_configdirectory('ml_recommender/data_path',
        new lang_string('data_path_label', 'ml_recommender'),
        new lang_string('data_path_help', 'ml_recommender'),
        environment::get_data_path()
    )
);
