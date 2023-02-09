<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_api
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2022110802;
$plugin->requires = 2022110800;       // Requires this Totara version.
$plugin->component = 'totara_api';  // To check on upgrade, that module sits in correct place

$plugin->dependencies = [
    'totara_oauth2' => 2021081200,
    'totara_tenant' => 2019070901
];