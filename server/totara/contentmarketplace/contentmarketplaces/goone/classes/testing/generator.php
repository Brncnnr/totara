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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\testing;

use coding_exception;
use contentmarketplace_goone\api;
use contentmarketplace_goone\mock_config_storage;
use contentmarketplace_goone\mock_curl;
use contentmarketplace_goone\model\learning_object;
use contentmarketplace_goone\oauth;
use contentmarketplace_goone\oauth_rest_client;
use core\testing\component_generator;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\testing\config_setup_generator;
use totara_contentmarketplace\testing\learning_object_generator;

/**
 * @method static generator instance()
 */
class generator extends component_generator implements learning_object_generator, config_setup_generator {

    /**
     * Set up an access token so that the mock curl client will be used instead of actually polling the Go1 API.
     */
    public function set_up_configuration(): void {
        set_config('oauth_access_token', '--ACCESS-TOKEN--', 'contentmarketplace_goone');
    }

    /**
     * @param bool $with_access_token
     * @return mock_config_storage
     */
    public function get_mock_config_storage(bool $with_access_token = true): mock_config_storage {
        global $CFG;
        require_once("$CFG->dirroot/totara/contentmarketplace/contentmarketplaces/goone/tests/fixtures/mock_config_storage.php");

        $config_storage = new mock_config_storage();
        if ($with_access_token) {
            $config_storage->set('oauth_access_token', '--ACCESS-TOKEN--');
        }
        return $config_storage;
    }

    /**
     * @param string $endpoint
     * @return oauth_rest_client
     */
    public function get_mock_rest_client(string $endpoint = api::ENDPOINT): oauth_rest_client {
        global $CFG;
        require_once("$CFG->dirroot/totara/contentmarketplace/contentmarketplaces/goone/tests/fixtures/mock_curl.php");

        $curl = new mock_curl();
        $oauth = new oauth($this->get_mock_config_storage(), $curl);
        return new oauth_rest_client($endpoint, $oauth, $curl);
    }

    /**
     * Get an API wrapper that uses mocked config data.
     *
     * @return api
     */
    public function get_mock_api(): api {
        $api = new api();
        $reflection_object = new \ReflectionObject($api);

        $reflection_client = $reflection_object->getProperty('client');
        $reflection_client->setAccessible(true);
        $reflection_client->setValue($api, $this->get_mock_rest_client());

        return $api;
    }

    /**
     * Get a mocked learning object from a JSON file.
     *
     * @param int $id
     * @return array
     */
    public function get_mock_learning_object(int $id): array {
        global $CFG;
        $path = "/totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures/learning-objects/$id/GET.json";

        $mock_file = @file_get_contents($CFG->dirroot . $path);
        if (!$mock_file) {
            throw new coding_exception("Must specify the ID of a mocked Go1 learning object that exists at $path");
        }

        return json_decode($mock_file, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Generate a learning object for use in the
     *
     * @param string|null $learning_object_id Note: This MUST actally be a learning object ID of a mocked record in the directory:
     *                                        totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures/learning-object
     * @return model
     */
    public function generate_learning_object(?string $learning_object_id = null): model {
        if (empty($learning_object_id) || !is_numeric($learning_object_id)) {
            throw new coding_exception(
                "Must specify an ID of a mocked Go1 learning object and not a name ('$learning_object_id' was specified)"
            );
        }

        $learning_object_id = $this->get_mock_learning_object($learning_object_id)['id'];

        return learning_object::load_by_external_id($learning_object_id, $this->get_mock_api());
    }

}
