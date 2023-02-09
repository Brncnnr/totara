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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package virtualmeeting_poc_user
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2022110800;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2022110800;       // Requires this Totara version.
$plugin->component = 'virtualmeeting_poc_user'; // To check on upgrade, that module sits in correct place
$plugin->dependencies = [
    'virtualmeeting_poc_app' => 2021010100,
];
