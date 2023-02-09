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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

namespace ml_service;

use ml_recommender\local\flag;

/**
 * Perform a healthcheck of the ml_service service.
 *
 * @package ml_service
 */
class healthcheck {

    const STATE_UNKNOWN = 0;
    const STATE_HEALTHY = 1;
    const STATE_UNHEALTHY = 2;

    /**
     * @var api
     */
    protected $api;

    /**
     * Collection of information about the Totara instance
     *
     * @var array
     */
    protected $totara_info = [];

    /**
     * Collection of information about the Machine Learning instance
     *
     * @var array
     */
    protected $service_info = [];

    /**
     * Collection of tips for troubleshooting.
     *
     * @var array
     */
    protected $troubleshooting = [];

    /**
     * The state of the connection from Totara to the ML Service
     *
     * @var int
     */
    protected $state_totara_to_service = self::STATE_UNKNOWN;

    /**
     * The state of the connection from the ML Service to Totara
     *
     * @var int
     */
    protected $state_service_to_totara = self::STATE_UNKNOWN;

    /**
     * Track whether the data has been exported
     *
     * @var null|bool
     */
    protected $data_exported = null;

    /**
     * @param api|null $api $api
     */
    public function __construct(?api $api = null) {
        $this->api = $api ?? new api();
    }

    /**
     * @param api|null $api
     * @return healthcheck
     */
    public static function make(?api $api = null): healthcheck {
        return new self($api);
    }

    /**
     * @param int $status
     * @return string
     */
    public static function state_label(int $status): string {
        if (self::STATE_UNKNOWN === $status) {
            return get_string('unknown', 'ml_service');
        }

        if (self::STATE_UNHEALTHY === $status) {
            return get_string('unhealthy', 'ml_service');
        }

        if (self::STATE_HEALTHY === $status) {
            return get_string('healthy', 'ml_service');
        }

        throw new \coding_exception('Invalid state of healthcheck.');
    }

    /**
     * Perform a series of diagnostic checks on both Totara & the ML service.
     *
     * @return $this
     */
    public function check_health(): healthcheck {
        global $CFG;

        // Check the config are all set correctly
        [$url_set, $message] = $this->get_config_status('ml_service_url', false);
        $this->totara_info[] = $message;
        [$key_set, $message] = $this->get_config_status('ml_service_key', true);
        $this->totara_info[] = $message;

        // Check the scheduled tasks are configured as expected
        // We check this ahead of the rest as it doesn't require a connection to the service to verify
        if ($this->is_data_exported()) {
            $this->totara_info[] = get_string('export_has_run', 'ml_service');
        } else {
            $a = ['script' => 'server/ml/recommender/cli/export_data.php'];
            $this->troubleshooting[] = get_string('export_has_not_run', 'ml_service', $a);
            $this->state_totara_to_service = self::STATE_UNHEALTHY;
        }

        if (!($url_set && $key_set)) {
            $this->state_totara_to_service = self::STATE_UNHEALTHY;
            $this->troubleshooting[] = get_string('error_no_config_defined', 'ml_service');
            return $this;
        }

        // Connect to the ML service and see if it's happy
        $response = $this->api->get('/health-check');
        if (!$response->is_ok()) {
            $this->state_totara_to_service = self::STATE_UNHEALTHY;

            // Why did it fail?
            if ($error = $response->try_get_error_message()) {
                $this->service_info[] = $error;
            } else {
                $message = $response->get_body();

                if (strstr($message, 'Connection timed out') !== false) {
                    $this->troubleshooting[] = get_string('error_service_running', 'ml_service', $CFG->ml_service_url);
                } else {
                    $this->service_info[] = $message;
                }
            }
        } else {
            $this->state_totara_to_service = self::STATE_HEALTHY;
            $this->state_service_to_totara = self::STATE_HEALTHY;

            $data = $response->get_body_as_json(true);
            $this->service_info = $data['totara'];

            if (!empty($data['errors'])) {
                // Do a bit of error checking to translate common errors
                foreach ($data['errors'] as $error) {
                    if (stristr($error, 'Unable to resolve the Totara instance hostname')) {
                        $this->troubleshooting[] = get_string('error_service_cannot_resolve', 'ml_service');
                    } else {
                        $this->service_info[] = $error;
                    }
                }

                $this->troubleshooting[] = get_string('error_service_problems', 'ml_service');
                $this->state_service_to_totara = self::STATE_UNHEALTHY;
            }
        }

        // Finally add the healthy/unhealthy labels to the top of each section.
        $healthy = self::state_label($this->state_totara_to_service);
        array_unshift(
            $this->totara_info,
            get_string('totara_to_service', 'ml_service', $healthy)
        );
        $healthy = self::state_label($this->state_service_to_totara);
        array_unshift(
            $this->service_info,
            get_string('service_to_totara', 'ml_service', $healthy)
        );

        return $this;
    }

    /**
     * @return array
     */
    public function get_totara_info(): array {
        return $this->totara_info;
    }

    /**
     * @return array
     */
    public function get_service_info(): array {
        return $this->service_info;
    }

    /**
     * @return int
     */
    public function get_state_totara_to_service(): int {
        return $this->state_totara_to_service;
    }

    /**
     * @return int
     */
    public function get_state_service_to_totara(): int {
        return $this->state_service_to_totara;
    }

    /**
     * @return array
     */
    public function get_troubleshooting(): array {
        return $this->troubleshooting;
    }

    /**
     * @return bool
     */
    protected function is_data_exported(): bool {
        if (null === $this->data_exported) {
            $this->data_exported = flag::is_complete(flag::EXPORT);
        }

        return $this->data_exported;
    }

    /**
     * Check if a specific config option is set or not.
     *
     * @param string $key
     * @param bool $private
     * @return array
     */
    protected function get_config_status(string $key, bool $private): array {
        global $CFG;

        $a = ['key' => '$CFG->' . $key];
        if (empty($CFG->$key)) {
            return [false, get_string('service_config_not_set', 'ml_service', $a)];
        }

        if (!$private) {
            $a['value'] = $CFG->$key;
            return [true, get_string('service_config_set_to', 'ml_service', $a)];
        }

        return [true, get_string('service_config_set', 'ml_service', $a)];
    }
}