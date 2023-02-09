<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

/*
 * This file is not intended to be used directly from Javascript code,
 * all requests must be done via public API in core/webapi AMD module
 * or via the Apollo client code in VueJS.
 *
 * This endpoint is not a public API, the parameters or data structure
 * may change even in stable branches.
 *
 * The batching support is intended only for fast, non-recursive, read-only
 * queries. Batching is not suitable for mutations because execution is not
 * interrupted by errors and order of execution may not be guaranteed in future.
 */

use totara_webapi\controllers\ajax;
use totara_webapi\helper;
use totara_webapi\local\util;

// Find out if we should start session, we do this to allow
// concurrent requests for resources like strings, templates and flex icons.

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (defined('NO_MOODLE_COOKIES')) {
    // This should not happen, dev must be trying to include this page from elsewhere.
    die;
}

require(__DIR__ . '/classes/local/util.php');
define('NO_MOODLE_COOKIES', util::is_nosession_request());
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/classes/helper.php');
helper::validate_environment();

require_once(__DIR__ . '/../../config.php');
(new ajax())->process('graphql_request');