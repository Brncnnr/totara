<?php
/**
 * This file is part of Totara Core
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

/**
 * UPGRADING to Totara 13
 *
 * The config.php file that contains your site configuration is located in the top level directory.
 * This aids in security as it is outside of the webroot and should not be accessible via the web.
 */

// Describe types of all globals here to help editor autocompletion.
/** @var core_config $CFG */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require_once(__DIR__ . '/lib/init.php');

if (isset($_SERVER['REMOTE_ADDR']) && !empty($_COOKIE['BEHAT'])) {
    $CFG = \core\internal\config::initialise_behat_site();
} else {
    $CFG = \core\internal\config::initialise(__DIR__ . '/../config.php');
}

require(__DIR__ . DIRECTORY_SEPARATOR . 'lib/setup.php');