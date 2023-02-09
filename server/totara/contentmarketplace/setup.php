<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// This file has been deprecated since Totara 15.0 and is no longer used.

// This is for backward compatible, ideally, we do not want any admin user to enable the content marketplaces plugin via
// this entry point anymore.
$action = optional_param('action', null, PARAM_ALPHA);
if ($action) {
    require_login();
    require_sesskey();

    $value = ($action == 'enable');
    set_config('enablecontentmarketplaces', $value);

    debugging(
        "Please do not use this entry point to enable/disable the content marketplace plugin",
        DEBUG_DEVELOPER
    );
}

// Redirect user to the manage marketplaces plugin.
redirect(new moodle_url('/totara/contentmarketplace/marketplaces.php'));
die();