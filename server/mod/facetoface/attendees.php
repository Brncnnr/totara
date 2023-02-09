<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../config.php');

// Seminar event ID.
$s = optional_param('s', 0, PARAM_INT);

// Action being performed.
$action = optional_param('action', 'approvalrequired', PARAM_ALPHA);

// Redirect to new endpoint.
$url = new moodle_url('/mod/facetoface/attendees/approvalrequired.php', array('s' => $s, 'action' => $action));
redirect($url);
