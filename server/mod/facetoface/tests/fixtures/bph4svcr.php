<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

require_once(__DIR__.'/../../../../config.php');
defined('BEHAT_SITE_RUNNING') || die();

require_login(null, false, null, false, true);

/*
Example:
  Given I click on "Virtual room" "link" in the "11 November" "table_row"
  And I click on "Join now" "button"
  And I switch to "totara_bph4svcr" window
  Then I should see "Behat: Virtual Room Placeholder Page" in the page title
  And I click on "Window close" "button_exact"
  And I switch to the main window
*/

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Behat: Virtual Room Placeholder Page</title>
<!-- Name a window for behat. (bph4svcr = behat placeholder for a seminar virtual class room) -->
<script>window.name = "totara_bph4svcr";</script>
</head>
<body>
<main role="main">
<h2>Virtual Room</h2>
<section><img alt="" src="<?php echo (new moodle_url('/mod/facetoface/tests/fixtures/leaves-green.png'))->out(false); ?>"></section>
<p>This is just a placeholder page used for acceptance testing. Please do not directly access here.</p>
</main>
<footer>
<!-- Add some buttons for convenience. -->
<button type="button" onclick="history.back(-1)">History back 1</button>
<button type="button" onclick="history.back(-2)">History back 2</button>
<button type="button" onclick="history.back(-3)">History back 3</button>
<button type="button" onclick="window.close()">Window close</button>
</footer>
</body>
</html>
