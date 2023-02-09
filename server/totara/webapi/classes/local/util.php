<?php
/**
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

namespace totara_webapi\local;

use GraphQL\Error\FormattedError;
use Throwable;
use totara_webapi\client_aware_exception_helper;

/**
 * Class util
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class util {

    /**
     * Send response and stop execution.
     *
     * @param array $response
     * @param int $status_code optional status code for transfer protocol related errors
     * @param bool $stop_execution
     * @return void - does not return
     */
    public static function send_response(array $response, $status_code = null, bool $stop_execution = true) {
        if (!$status_code) {
            $status_code = 200;
        }
        if (!headers_sent()) {
            header('Content-type: application/json; charset=utf-8', true, $status_code);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Accept-Ranges: none');
        }

        if (!empty($response['errors']) and !isset($response['error'])) {
            // BC for Moodle ajaxexception
            $errors = [];
            foreach ($response['errors'] as $e) {
                $errors[] = $e['message'];
            }
            $response['error'] = implode("\n", $errors);
        }
        echo json_encode($response, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($stop_execution) {
            die;
        }
    }

    /**
     * Send general error message without any logging.
     *
     * @param string $message
     * @param int $statuscode optional status code for transfer protocol related errors
     * @param bool $stop_execution
     */
    public static function send_error(string $message, $statuscode = null, bool $stop_execution = true) {
        self::send_response(['errors' => [['message' => $message]]], $statuscode, $stop_execution);
    }

    /**
     * Default error handler for Web API ajax.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array|null $errcontext (not used in PHP 8.0)
     * @return bool false means use default error handler
     */
    public static function error_handler($errno, $errstr, $errfile, $errline, $errcontext = null) {
        if ($errno == 4096) {
            // Fatal catchable error.
            throw new \coding_exception('PHP catchable fatal error', $errstr);
        }
        return false;
    }

    /**
     * Default exception handler for Web API ajax.
     *
     * @param Throwable $ex
     * @return void - does not return. Terminates execution!
     */
    public static function exception_handler($ex) {
        global $CFG, $PAGE;

        // Detect active db transactions, rollback and log as error.

        abort_all_db_transactions();

        $PAGE->set_context(null);

        self::log_exception($ex);

        $response = [
            'errors' => [FormattedError::createFromException($ex, (bool)$CFG->debugdeveloper)],
        ];

        self::send_response($response, 500);
    }

    /**
     * Log exceptions during ajax execution.
     *
     * @param Throwable $ex
     */
    public static function log_exception($ex) {
        global $CFG;
        // For now just logging if on developer mode to not flood the logs if someone sends a whole lot of invalid requests
        if ($CFG->debugdeveloper && (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST)) {
            $message = $ex->getMessage();
            $info = get_exception_info($ex);
            error_log(
                sprintf(
                    "AJAX API error: %s Debug: %s\n%s",
                    $message,
                    $info->debuginfo,
                    format_backtrace($info->backtrace, true)
                )
            );
        }
    }

    /**
     * GraphQL error formatter, converts registered exceptions to client-aware exceptions.
     *
     * @param Throwable $error
     * @return array
     */
    public static function graphql_error_formatter(Throwable $error): array {
        $previous_exception = $error->getPrevious();
        if ($previous_exception && client_aware_exception_helper::exception_registered($previous_exception)) {
            $error = client_aware_exception_helper::create($previous_exception);
        }

        return FormattedError::createFromException($error);
    }

    /**
     * Error handler for graphql-php error logging.
     *
     * @param array $errors
     * @param callable $formatter
     * @return array
     */
    public static function graphql_error_handler(array $errors, callable $formatter) {
        foreach ($errors as $error) {
            /** @var Throwable $error */
            $prev = $error->getPrevious();
            if (!$prev) {
                continue;
            }
            self::log_exception($prev);
        }
        return array_map($formatter, $errors);
    }

    /**
     * Get all files with given extension from directory
     *
     * @param string $dir
     * @param string $extension
     * @return array
     */
    public static function get_files_from_dir(string $dir, string $extension): array {
        if (!file_exists($dir) || !is_readable($dir) || !is_dir($dir)) {
            return [];
        }

        $files = [];
        // Use scandir to guarantee sort order.
        $files_and_dirs = scandir($dir);
        foreach ($files_and_dirs as $file_or_dir) {
            if (preg_match("/\\.".preg_quote($extension)."$/", $file_or_dir)) {

                $name = basename($file_or_dir, ".{$extension}");
                if ($name !== clean_param($name, PARAM_SAFEDIR)) {
                    continue;
                }
                // Exclude directories.
                $path = $dir . DIRECTORY_SEPARATOR . $file_or_dir;
                if (is_dir($path)) {
                    continue;
                }
                $files[$name] = $path;
            }
        }

        return $files;
    }

    /**
     * Take the POST raw data and decode it as JSON.
     *
     * @return mixed|null
     */
    public static function parse_http_request() {
        $params = file_get_contents('php://input');
        if (!$params) {
            return null;
        }
        $params = json_decode($params, true);
        if (json_last_error() !== JSON_ERROR_NONE or $params === null) {
            return null;
        }

        return $params;
    }

    /**
     * Returns true if this is a request which should not initiate the session
     *
     * @param array|null $request_params
     * @return bool
     */
    public static function is_nosession_request($request_params = null): bool {
        $nosession = $session = null;
        $params = !is_null($request_params) ? $request_params : self::parse_http_request();

        if (is_array($params) && !empty($params)) {
            if (array_key_exists('operationName', $params) || array_key_exists('query', $params)) {
                $params = [$params];
            }

            foreach ($params as $op) {
                if (isset($op['operationName']) && substr($op['operationName'], - strlen('_nosession')) === '_nosession') {
                    $nosession = true;
                } else {
                    $session = true;
                }
            }
        }

        // If one operation does not have the nosession suffix the whole request is treated as session request
        return $session === null && $nosession === true;
    }

}