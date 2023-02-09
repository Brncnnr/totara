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

defined('MOODLE_INTERNAL') || die();

$string['cachedef_recommended_related_items'] = 'Recommendations related items cache';
$string['cachedef_recommended_user_items'] = 'Recommendations user cache';
$string['data_path_label'] = 'Data directory (Deprecated)';
$string['data_path_help'] = 'Path to directory where recommender system data will be stored.';
$string['enable_recommenders'] = 'Recommendations';
$string['enable_recommenders_description'] = 'When enabled this will allow users to discover content specifically recommended for them (e.g. playlists, resources, surveys, workspaces). If disabled, the Recommended learning block will be hidden from site menus and API services will not be accessible.';
$string['exportdatatask'] = 'Export user data for recommendation processing';
$string['importdatatask'] = 'Import user recommendations';
$string['interactions_period_label'] = 'Time to analyse interactions (Deprecated)';
$string['interactions_period_help'] = 'Number of weeks from which to draw user-item interactions data.';
$string['interactions_period_option'] = '{$a} weeks';
$string['item_result_count_label'] = 'Number of items-to-item recommendations (Deprecated)';
$string['item_result_count_help'] = 'Number of items-to-item recommendations to be returned by recommender system.';
$string['invalid_user'] = 'Invalid user';
$string['pluginname'] = 'Recommendation engine';
$string['py3path_label'] = 'File path for python executable (Deprecated)';
$string['py3path_help'] = 'Path to the python executable that will run the recommendations script.';
$string['query_label'] = 'Recommendation algorithm (Deprecated)';
$string['query_help'] = '<ul><li>Full hybrid utilises content data, user meta-data and user-content interaction data. It takes the longest time to process, but has the highest granularity.</li><li>Partial hybrid utilises content meta-data and user-content interaction data.</li><li>Matrix factorisation utilises user-content interaction data only.  It has the fastest processing time, but the also the lowest granularity.</li></ul>';
$string['query_option_hybrid'] = 'Full hybrid';
$string['query_option_mf'] = 'Matrix factorisation';
$string['query_option_partial'] = 'Partial hybrid';
$string['recommendations'] = 'Engage recommendations';
$string['recsysconfigs'] = 'Recommendation engine configuration';
$string['related_items_count_label'] = 'Number of related items';
$string['related_items_count_help'] = 'Number of related items to list in Related panes.';
$string['threads_label'] = 'Processing threads (Deprecated)';
$string['threads_help'] = 'Number of cores/threads that may be utilised by the recommendations library.  It should be less than the number of physical cores.';
$string['userdataiteminteraction'] = 'Interaction';
$string['userdataitemrecommended_user'] = 'Recommendation';
$string['user_result_count_label'] = 'Number of items-to-user recommendations (Deprecated)';
$string['user_result_count_help'] = 'Number of items-to-user recommendations to be returned by recommender system.';
