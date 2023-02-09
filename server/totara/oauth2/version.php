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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */
defined('MOODLE_INTERNAL') || die();

// Totara OAuth2 provider

$plugin->version  = 2022110801;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2022110800;       // Requires this Totara version.
$plugin->component = 'totara_oauth2';  // To check on upgrade, that module sits in correct place

$plugin->dependencies = [
    "totara_mvc" => 2021052500
];