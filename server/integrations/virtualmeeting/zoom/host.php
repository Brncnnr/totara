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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package virtualmeeting_zoom
 */

use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\http\clients\curl_client;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\exception\meeting_exception;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

$meetingid = required_param('meetingid', PARAM_INT);

try {
    $entity = new virtual_meeting_entity($meetingid);
    if ($entity->plugin !== 'zoom') {
        throw new meeting_exception('plugin url mismatch');
    }
    $meeting_dto = new meeting_dto($entity);
    $factory = new virtualmeeting_zoom_factory();
    $client = new curl_client();
    $provider = $factory->create_service_provider($client);
    $host_url = $provider->get_real_host_url($meeting_dto);
    redirect($host_url);
    die;
} catch (Throwable $ex) {
    header('Content-Type: text/html', true, 500);
    echo get_string('host_url_error', 'virtualmeeting_zoom');
    debugging($ex->getMessage(), DEBUG_DEVELOPER);
    die;
}
