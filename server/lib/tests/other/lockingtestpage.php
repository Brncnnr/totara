<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

if (!isset($_SERVER['REMOTE_ADDR'])) {
    // Do not copy this nasty hack elsewhere, normal scripts cannot be used both from CLI and web!!!
    define('CLI_SCRIPT', true);
} else {
    define('NO_MOODLE_COOKIES', true); // Session locking is not wanted here.
}

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');

$PAGE->set_pagelayout('maintenance');
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/lib/tests/other/lockingtestpage.php');

echo $OUTPUT->header();
if (!CLI_SCRIPT) {
    echo '<pre>';
}
echo "=== Locking test page ====\n\n";

$cronlockfactory = \core\lock\lock_config::get_lock_factory('test');
echo "Acquiring test lock with 10s timeout\n";
$lock = $cronlockfactory->get_lock('somelock', 10);
if (!$lock) {
    echo "Lock was not acquired\n";
    echo "...done";
    die;
}
echo "Lock was acquired\n";
echo "Holding lock for 60 seconds\n";
for ($i = 1; $i <= 60; $i++) {
    sleep(1);
    echo '.';
}
echo "\nReleasing lock\n";
$lock->release();
echo "Lock was released\n";
echo "\n\n...done\n";
