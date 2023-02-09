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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');

use totara_core\http\clients\curl_client;
use totara_core\http\response_code;
use totara_core\http\util;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\botfw\exception\unexpected_exception;
use totara_msteams\botfw\logger\syslog_logger;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\resolver\v3_resolver;
use totara_msteams\botfw\storage\database_storage;
use totara_msteams\botfw\util\http;
use totara_msteams\botfw\validator\validator;
use totara_msteams\my\bot_hook;
use totara_msteams\my\dispatcher\signin_request;
use totara_msteams\my\router as my_router;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/msteams/botindex.php');
$PAGE->set_cacheable(false);

$logger = new syslog_logger();

// Reject access if the full feature is disabled
if (\totara_core\advanced_feature::is_disabled('totara_msteams')) {
    $logger->log('The bot endpoint was accessed while the teams feature was disabled');
    http::send_error(response_code::BAD_REQUEST);
    die;
}

// Reject access to the endpoint if neither the bot feature nor the messaging extension feature is enabled
if (empty(get_config('totara_msteams', 'bot_feature_enabled')) && empty(get_config('totara_msteams', 'messaging_extension_enabled'))) {
    $logger->log('The bot endpoint was accessed while no features were enabled');
    http::send_error(response_code::BAD_REQUEST);
    die;
}

// Reject access during site maintenance.
if (!empty($CFG->maintenance_enabled)) {
    $logger->log('The bot endpoint was accessed while the site was being under maintenance.');
    http::send_error(response_code::SERVICE_UNAVAILABLE);
    die;
}

$headers = util::get_request_headers();
if (empty($headers)) {
    $logger->debug('No headers');
    http::send_error(response_code::BAD_REQUEST);
    die;
}

$contents = file_get_contents('php://input');
if ($contents === false || $contents === '') {
    $logger->debug('No input');
    http::send_error(response_code::BAD_REQUEST);
    die;
}

$json = json_decode($contents, false, 512, JSON_BIGINT_AS_STRING);
if ($json === null) {
    $logger->debug(json_last_error_msg());
    http::send_error(response_code::BAD_REQUEST);
    die;
}

try {
    $router = new my_router();

    $bot = builder::bot()
        ->router($router)
        ->authoriser(new default_authoriser())
        ->client(new curl_client())
        ->resolver(new v3_resolver())
        ->notification(new default_notification())
        ->storage(new database_storage())
        ->logger($logger)
        ->build();
    $input = activity::from_object($json);

    try {
        $bot->set_hook(new bot_hook());
        if (!$bot->process($input, $headers)) {
            http::send_error(response_code::UNAUTHORIZED);
            die;
        }
    } catch (auth_required_exception $ex) {
        $logger->debug('authorisation required');
        $bot->process_callback($input, function (activity $input, validator $validator) use ($bot, $router, $headers) {
            if (!$validator->validate_header($bot, $headers)) {
                return false;
            }
            $router->direct_dispatch(signin_request::class, $bot, $input);
            return true;
        });
    } catch (unexpected_exception $ex) {
        // Send a 'something went wrong' message, then rethrow an exception.
        $bot->process_callback($input, function (activity $input, validator $validator) use ($bot) {
            $bot->reply_text_to($input, get_string('botfw:generic_failure', 'totara_msteams'), true);
            return false;
        });
        throw $ex;
    }
} catch (Throwable $ex) {
    http::send_error(response_code::INTERNAL_SERVER_ERROR);
    $logger->debug("Exception: ".$ex->getMessage()."\nTrace: ".$ex->getTraceAsString()."\n");
}

die;
