<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package engage_survey
 */

use totara_core\advanced_feature;
use totara_engage\access\access_manager;

// This file is a helper for the link generator so it has one page to target.
// We expect to see a $page property
require_once(__DIR__ . "/../../../../config.php");
global $OUTPUT, $PAGE, $USER;

require_login();
advanced_feature::require('engage_resources');
access_manager::require_library_capability();

$id = required_param("id", PARAM_INT);
$page = optional_param("page", 'vote', PARAM_ALPHA);
$source = optional_param("source", '', PARAM_TEXT);
$source_url = optional_param("source_url", '', PARAM_URL);

if ($page === 'redirect') {
    throw new \coding_exception('Infinite survey redirection loop detected.');
}

$url = \totara_engage\link\builder::to('engage_survey')
    ->set_attributes(['id' => $id, 'page' => $page, 'source' => $source])
    ->url();

if ($source_url) {
    $url->param('source_url', $source_url);
}

redirect($url);