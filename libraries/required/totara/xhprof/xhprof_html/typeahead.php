<?php
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

/**
 * AJAX endpoint for XHProf function name typeahead.
 *
 * @author(s)  Kannan Muthukkaruppan
 *             Changhao Jiang
 */

// Totara: Start modification
require_once(__DIR__.'/../../../../../server/config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
\core\session\manager::write_close();
// End modification.

// by default assume that xhprof_html & xhprof_lib directories
// are at the same level.
$GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/../xhprof_lib';

require_once $GLOBALS['XHPROF_LIB_ROOT'].'/display/xhprof.php';

// Totara: use own XHProfRuns implementation.
// $xhprof_runs_impl = new XHProfRuns_Default();
$xhprof_runs_impl = new xhprof_totara_run();
// End modification.

require_once $GLOBALS['XHPROF_LIB_ROOT'].'/display/typeahead_common.php';
