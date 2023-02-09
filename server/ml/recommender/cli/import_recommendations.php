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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
debugging('[DEPRECATION WARNING]: ml_recommender import recommendations has been deprecated', DEBUG_DEVELOPER);
$task = \core\task\manager::get_scheduled_task(\ml_recommender\task\import::class);
$task->execute();

