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

$plugin->version   = 2022110800;             // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2022110800;             // Requires this Totara version
$plugin->component = 'ml_recommender';   // Full name of the plugin (used for diagnostics)

$plugin->dependencies = [
    'totara_engage' => 2020101200,
    'totara_playlist' => 2020101200,
    'engage_article' => 2020101200,
    'container_workspace' => 2020101200,
    'ml_service' => 2021072900,
];