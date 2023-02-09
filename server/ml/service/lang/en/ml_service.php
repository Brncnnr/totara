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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

defined('MOODLE_INTERNAL') || die();

$string['error_no_config_defined'] = 'The ml_service_url or ml_service_key configuration option have not been defined.';
$string['error_service_cannot_resolve'] = 'The service reports it cannot resolve Totara\'s domain name. Please check if it has been set correctly in the service.';
$string['error_service_problems'] = 'The service reported problems and may not be healthy. Check the information above.';
$string['error_service_running'] = 'Unable to connect to the service on {$a}. Please check if the Machine Learning Service is running.';
$string['export_has_not_run'] = 'Data export has not been run. Please check the script {$a->script}';
$string['export_has_run'] = 'Data export has been run';
$string['healthcheck_title'] = 'Machine Learning Service Healthcheck';
$string['healthcheck_subtitle_totara'] = 'Totara Information';
$string['healthcheck_subtitle_service'] = 'Machine Learning Service Information';
$string['healthcheck_subtitle_troubleshooting'] = 'Troubleshooting';
$string['healthy'] = 'Healthy';
$string['pluginname'] = 'Machine Learning Service';
$string['service_config_not_set'] = '{$a->key} is not set';
$string['service_config_set'] = '{$a->key} is set';
$string['service_config_set_to'] = '{$a->key} is set to {$a->value}';
$string['service_to_totara'] = 'Service to Totara connection... {$a}';
$string['totara_to_service'] = 'Totara to Service connection... {$a}';
$string['unhealthy'] = 'Unhealthy';
$string['unknown'] = 'Unknown';