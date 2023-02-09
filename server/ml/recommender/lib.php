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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

use ml_recommender\local\export\content_downloader;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides access to the recommender data exports so
 * the machine learning service can train/run the models with it.
 */
function ml_recommender_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    // Don't even proceed if recommendations are disabled
    if (advanced_feature::is_disabled('ml_recommender')) {
        throw new \coding_exception('Recommendations engine is disabled');
    }

    $content_downloader = content_downloader::make($filearea, $args);
    if (!$content_downloader->is_request_allowed()) {
        send_file_not_found();
        return;
    }

    // Find the matching file
    $found_file = $content_downloader->find_matching_file();
    if (empty($found_file)) {
        send_file_not_found();
        return;
    }

    // If multi-tenancy is disabled but the tenants.csv file is requested.
    // We return a 204 to indicate it's valid but has no content.
    if ($content_downloader->is_tenants_request_without_multitenancy($found_file)) {
        http_response_code(204);
        exit;
    }

    // If the file isn't available, signal to the service it needs to wait.
    // We return a 202 to indicate it's available but not ready yet.
    if (!$content_downloader->is_file_available($found_file)) {
        http_response_code(202);
        exit;
    }

    // Everything looks good, let the file be downloaded.
    send_file(
        $found_file,
        basename($found_file),
        null,
        0,
        false,
        $forcedownload,
        'text/csv'
    );
}