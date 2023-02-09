<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use \core\orm\query\builder;
use core\session\manager as session_manager;
use totara_webapi\phpunit\webapi_phpunit_helper;

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', false);

// No logging.
define('LOG_MANAGER_CLASS', '\core\log\dummy_manager');

$config = __DIR__ . '/../../server/config.php';
require($config);

require_once($CFG->libdir . '/clilib.php');

[$options, $filters] = cli_get_params(
    ['help' => false, 'output' => false, 'approver' => false],
    ['h' => 'help', 'o' => 'output', 'a' => 'approver']
);

if (!empty($options['help'])) {
    $basename = basename(__FILE__);
    cli_writeln("Write sanitised configuration and applications dashboard performance information to a file.

Usage:
    php {$basename} --output=</path/to/file.txt> --approver=<user id>

Required parameters:
    -o, --output       Full path to writeable file for collecting data
    -a, --approver     User ID of approver for dashboard performance check

Options:
    -h, --help            Print out this help

Example:
  $ php {$basename} -o=/home/vagrant/sf182-performance.txt -a=5
");
    exit(2);
}

if (empty($options['output'])) {
    cli_error("File output not provided, run this script with -h to see instructions");
}
ini_set('error_log', $options['output']);

if (empty($options['approver'])) {
    cli_error("Approver ID not provided, run this script with -h to see instructions");
}
$approver = new \core\entity\user($options['approver']);
if (is_null($approver) || empty($approver->id)) {
    cli_error("User with ID {$options['approver']} not found.");
}

class performance_test {
    use webapi_phpunit_helper;

    private $query = 'mod_approval_others_applications';

    public function test_query() {
        $options = $this->get_query_options();
        $result = $this->parsed_graphql_operation($this->query, $options);
        return $this->get_webapi_operation_data($result);
    }

    private function get_query_options($limit = 1, $page = 1, $filters = null, $sort_by = null): array {
        $options = [
            'pagination' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ];
        if (isset($filters)) {
            $options['filters'] = $filters;
        }
        if (isset($sort_by)) {
            $options['sort_by'] = $sort_by;
        }
        return ['query_options' => $options];
    }
}

// Header
error_log(
    PHP_EOL . '---------------------------------------'
    . PHP_EOL . 'New others_applications test!'
    . PHP_EOL . '---------------------------------------'
    . PHP_EOL . print_r($options,1)
    . PHP_EOL . print_r(['Approver' => $approver->fullname], 1)
    . PHP_EOL . '---------------------------------------'
    . PHP_EOL . 'Site configuration:'
    . PHP_EOL . '---------------------------------------'
);

// Config
$sanitised_cfg = clone $CFG;
unset($sanitised_cfg->config_php_settings);
foreach ($sanitised_cfg as $key => $value) {
    if (strpos($key, 'pass') !== false && !empty($value)) {
        $sanitised_cfg->{$key} = '*****';
    }
}
error_log(print_r($sanitised_cfg,1));

error_log(
    PHP_EOL . '---------------------------------------'
    . PHP_EOL . '---------------------------------------'
    . PHP_EOL . 'Database queries from others_applications resolving:'
    . PHP_EOL . '---------------------------------------'
);

// Performance
$start_time = microtime();
session_manager::set_user($approver->to_record());
$test = new performance_test();
builder::get_db()->set_debug(true);
ob_start();
$result = $test->test_query();
$output = ob_get_clean();
error_log(PHP_EOL . $output);
$items = $result['items'];
$end_time = microtime();
$duration = microtime_diff($start_time, $end_time);

error_log(
    PHP_EOL . '---------------------------------------'
    . PHP_EOL . '---------------------------------------'
    . PHP_EOL . 'Resolved items:'
    . PHP_EOL . '---------------------------------------'
);
error_log(print_r($items,1));

error_log(
    PHP_EOL . '---------------------------------------'
    . PHP_EOL . '---------------------------------------'
    . PHP_EOL . 'Performance:'
    . PHP_EOL . '---------------------------------------'
);
error_log(print_r([
    'Reads' => builder::get_db()->perf_get_reads(),
    'Writes' => builder::get_db()->perf_get_writes(),
    'Total query time' => builder::get_db()->perf_get_queries_time(),
    'Total time' => $duration,
],1));

print "Done, see {$options['output']}\n";