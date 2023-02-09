<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

defined('MOODLE_INTERNAL') || die();

// Note that \totara_mobile\util::API_VERSION may also need to be changed,
// if endpoints or HTTP response codes have changed.

$plugin->version   = 2022110800;    // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2022110800;    // Requires this platform version.
$plugin->component = 'mobile_findlearning'; // To check on upgrade, that module sits in correct place.
