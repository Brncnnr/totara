<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 */

/*
 * This script is included as first thing on all pages including installers.
 * This is not a full environment test, we just make sure users have
 * the correct PHP environment. The full environment test is done later
 * using information from admin/environment.xml file.
 *
 *  - Do not use any Totara function here.
 *  - Do not create any variables here.
 *  - Do not change any PHP settings here.
 *
 * Terminates PHP execution with status code 1 on error.
 */

require_once(__DIR__ . '/init.php');
\core\internal\config::initialise_environment();