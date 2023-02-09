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
 * @package totara_webapi
 */

use totara_webapi\endpoint_type\dev;

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/webapi/dev_graphql_schema.php');

// ========================================================
// NOTE: this SHOULD NOT be enabled on production servers!
// ========================================================

if (!defined('GRAPHQL_DEVELOPMENT_MODE') or !GRAPHQL_DEVELOPMENT_MODE) {
    die;
}

$schema = \totara_webapi\graphql::get_schema(new dev());
$schema = \GraphQL\Utils\SchemaPrinter::doPrint($schema);
$forcedownload = !(defined('BEHAT_SITE_RUNNING') and BEHAT_SITE_RUNNING);
// Download is always forced for text/plain unless you explicitly skip XSS protection.
$options = ['allowxss' => true];
send_file($schema, "totara.graphqls", null, 0, true, $forcedownload, 'text/plain', false, $options);
