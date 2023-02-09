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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\testing;

use coding_exception;
use contentmarketplace_linkedin\api\response\result;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\collection;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\data_provider\locales;
use contentmarketplace_linkedin\dto\locale;
use contentmarketplace_linkedin\dto\timespan;
use contentmarketplace_linkedin\entity\classification;
use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\entity\learning_object_classification;
use contentmarketplace_linkedin\model\learning_object as learning_object_model;
use core\orm\query\builder;
use core\testing\component_generator;
use ReflectionClass;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\testing\config_setup_generator;
use totara_contentmarketplace\testing\learning_object_generator;
use totara_contentmarketplace\token\token;
use totara_core\http\response;
use totara_core\http\response_code;

/**
 * @method static generator instance()
 */
class generator extends component_generator implements learning_object_generator, config_setup_generator {
    /**
     * Whether the locale provider had been modified or not with static memory.
     * This flag is only help to speed up the test, by not having to reset the list
     * of locales all the times.
     *
     * @var bool
     */
    private $locales_provider_modified;

    /**
     * Constructor of the generator.
     */
    protected function __construct() {
        parent::__construct();
        $this->locales_provider_modified = false;
    }

    /**
     * Set the configuration item for client_id.
     *
     * @param string $client_id
     * @return void
     */
    public function set_config_client_id(string $client_id): void {
        set_config('client_id', $client_id, 'contentmarketplace_linkedin');
    }

    /**
     * Set the configuration item for client_secret.
     *
     * @param string $client_secret
     * @return void
     */
    public function set_config_client_secret(string $client_secret): void {
        set_config('client_secret', $client_secret, 'contentmarketplace_linkedin');
    }

    /**
     * Set up the environment with either given client's id and secret or
     * the system will mock these two attributes itself.
     *
     * @param string|null $client_id
     * @param string|null $client_secret
     *
     * @return void
     */
    public function set_up_configuration(?string $client_id = null, ?string $client_secret = null): void {
        if (empty($client_id)) {
            $client_id = uniqid('clientid');
        }

        if (empty($client_secret)) {
            $client_secret = uniqid('clientsecret');
        }

        $this->set_config_client_id($client_id);
        $this->set_config_client_secret($client_secret);
    }

    /**
     * @param token $token
     * @return void
     */
    public function set_token(token $token): void {
        config::save_access_token($token->get_value());
        config::save_access_token_expiry($token->get_expiry());
    }

    /**
     * Load a static JSON response from a file. Which the file must be existing
     * within the location "server/totara/contentmarketplace/contentmarketplaces/linkedin/tests/fixtures/"
     *
     * @param string $json_filename
     * @return string
     */
    public function get_json_content_from_fixtures(string $json_filename): string {
        global $CFG;
        if (false === strpos($json_filename, '.json')) {
            $json_filename .= ".json";
        }

        $base_directory = "{$CFG->dirroot}/totara/contentmarketplace/contentmarketplaces/linkedin/tests/fixtures/json";
        $file = "{$base_directory}/{$json_filename}";

        if (!file_exists($file)) {
            throw new coding_exception("The file '{$file}' does not exist");
        }

        return file_get_contents($file);
    }

    /**
     * Load a static response from a file and wrap it in an appropriate mock response object.
     *
     * @param string $json_filename
     * @return result|collection
     */
    public function get_mock_result_from_fixtures(string $json_filename): result {
        $json_string = $this->get_json_content_from_fixtures($json_filename);
        $json_data = json_decode($json_string, false);

        return collection::create($json_data);
    }

    /**
     * $record is the hashmap where the keys are:
     * + title: String
     * + description: String
     * + description_include_html: String
     * + short_description: String
     * + locale_language: String
     * + locale_country: String
     * + last_updated_at: Int
     * + published_at: Int
     * + retired_at: Int
     * + level: String
     * + asset_type: String
     * + primary_image_url: String
     * + time_to_complete: Int
     * + web_launch_url: String
     * + ss_launch_url: String
     *
     * @param string $urn
     * @param array $record
     * @return learning_object
     */
    public function create_learning_object(string $urn, array $record = []): learning_object {
        global $CFG;

        if (!array_key_exists('title', $record)) {
            $record['title'] = "This is title " . rand(0, 100);
        }

        if (!array_key_exists('description', $record)) {
            $record['description'] = "This is description " . rand(0, 100);
        }

        if (!array_key_exists('description_include_html', $record)) {
            $record['description_include_html'] = "<p>{$record['description']}</p>";
        }

        if (!array_key_exists('last_updated_at', $record)) {
            $record['last_updated_at'] = time();
        }

        if (!array_key_exists('published_at', $record)) {
            $record['published_at'] = (int) $record['last_updated_at'];
        }

        if (!array_key_exists('level', $record)) {
            $record['level'] = constants::DIFFICULTY_LEVEL_BEGINNER;
        }

        if (!array_key_exists('asset_type', $record)) {
            $record['asset_type'] = constants::ASSET_TYPE_COURSE;
        }

        if (!array_key_exists('locale_language', $record)) {
            $record['locale_language'] = 'en';
        }

        if (!array_key_exists('locale_country', $record)) {
            $record['locale_country'] = 'US';
        }

        if (!array_key_exists('time_to_complete', $record)) {
            $record['time_to_complete'] = timespan::minutes(90)->get();
        }

        if (!array_key_exists('web_launch_url', $record)) {
            $record['web_launch_url'] = "{$CFG->wwwroot}/totara/contentmarketplace/tests/fixtures/learning_object.html";
        }

        if (!array_key_exists('availability', $record)) {
            $record['availability'] = constants::AVAILABILITY_AVAILABLE;
        }

        $entity = new learning_object();
        $entity->urn = $urn;

        $entity->set_attributes_from_array_record($record);
        $entity->save();

        return $entity;
    }

    /**
     * Create a learning object record for use in behat.
     *
     * @param array $record
     * @return void
     */
    public function create_learning_object_for_behat(array $record): void {
        // Have the option of specifying time_to_complete_unit in order to make the behat step more human readable.
        if (!empty($record['time_to_complete']) && !empty($record['time_to_complete_unit'])) {
            $record['time_to_complete'] = (new timespan($record['time_to_complete'], $record['time_to_complete_unit']))->get();
            unset($record['time_to_complete_unit']);
        }

        $this->create_learning_object($record['urn'], $record);
    }

    /**
     * Create a classification record for usage in behat.
     *
     * @param array $record
     * @return void
     */
    public function create_classification_for_behat(array $record): void {
        if (!empty($record['type'])) {
            $record['type'] = strtoupper($record['type']);
            constants::validate_classification_type($record['type']);
        }

        $this->create_classification($record['urn'], $record);
    }

    /**
     * @param string $json_fixture
     * @param array  $header
     * @param int    $code
     * @param string $content_type
     * @return response
     */
    public function create_json_response_from_fixture(
        string $json_fixture,
        array $header = [],
        int $code = response_code::OK,
        string $content_type = 'application/json'
    ): response {
        $json_content = $this->get_json_content_from_fixtures($json_fixture);
        return $this->create_json_response($json_content, $header, $code, $content_type);
    }

    /**
     * Create json response from a json string.
     *
     * @param string $json
     * @param array $header
     * @param int $code
     * @param string $content_type
     * @return response
     */
    public function create_json_response(
        string $json,
        array $header =  [],
        int $code = response_code::OK,
        string $content_type = 'application/json'
    ): response {
        return new response($json, $code, $header, $content_type);
    }

    /**
     * @param string|null $name
     * @return model
     */
    public function generate_learning_object(?string $name = null): model {
        $db = builder::get_db();

        while (true) {
            $random_id = rand(1, 10000000);
            $urn = "urn:li:lyndaCourse:{$random_id}";

            $existing = $db->record_exists(learning_object::TABLE, ['urn' => $urn]);
            if (!$existing) {
                break;
            }
        }

        $record = [];
        if (!empty($name)) {
            $record['title'] = $name;
        }

        $entity = $this->create_learning_object($urn, $record);
        return new learning_object_model($entity);
    }

    /**
     * $record is a hashmap where keys are:
     * + name: String
     * + locale_language: String
     * + locale_country: String
     * + type: String
     *
     * @param string|null $urn
     * @param array $record
     * @return classification
     */
    public function create_classification(?string $urn = null, array $record = []): classification {
        if (empty($urn)) {
            $urn = sprintf("urn:li:organization:%d", rand(1, 100000));
        }

        if (empty($record['name'])) {
            $record['name'] = 'Classification ' . rand(1, 9999);
        }

        if (empty($record['locale_language'])) {
            $record['locale_language'] = 'en';

            // Only set the locale country if the language is empty.
            if (empty($record['locale_country'])) {
                $record['locale_country'] = 'US';
            }
        }

        if (empty($record['type'])) {
            $record['type'] = constants::CLASSIFICATION_TYPE_SUBJECT;
        }

        $classification = new classification();
        $classification->urn = $urn;
        $classification->name = $record['name'];
        $classification->locale_language = $record['locale_language'];
        $classification->locale_country = $record['locale_country'] ?? null;
        $classification->type = $record['type'];

        $classification->save();
        $classification->refresh();

        return $classification;
    }

    /**
     * @param int $parent_id
     * @param int $child_id
     *
     * @return classification_relationship
     */
    public function create_classification_relationship(int $parent_id, int $child_id): classification_relationship {
        $relationship = new classification_relationship();
        $relationship->parent_id = $parent_id;
        $relationship->child_id = $child_id;

        $relationship->save();
        return $relationship;
    }

    /**
     * Create classification relationship for usage in behat.
     *
     * @param array $record
     * @return void
     */
    public function create_classification_relationship_for_behat(array $record): void {
        $db = builder::get_db();

        $parent_urn = trim($record['parent_urn']);
        $child_urn = trim($record['child_urn']);

        $parent_id = $db->get_field(classification::TABLE, 'id', ['urn' => $parent_urn], MUST_EXIST);
        $child_id = $db->get_field(classification::TABLE, 'id', ['urn' => $child_urn], MUST_EXIST);

        $this->create_classification_relationship($parent_id, $child_id);
    }

    /**
     * Create learning object classification relationship usage in behat.
     *
     * @param array $record
     * @return void
     */
    public function create_learning_object_classifications_for_behat(array $record): void {
        $db = builder::get_db();
        $learning_object_id = $db->get_field(learning_object::TABLE, 'id', ['urn' => $record['learning_object_urn']], MUST_EXIST);
        $classification_id = $db->get_field(classification::TABLE, 'id', ['urn' => $record['classification_urn']], MUST_EXIST);

        $this->create_learning_object_classification($learning_object_id, $classification_id);
    }

    /**
     * @param int $learning_object_id
     * @param int $classification_id
     * @return learning_object_classification
     */
    public function create_learning_object_classification(
        int $learning_object_id,
        int $classification_id
    ): learning_object_classification {
        $map = new learning_object_classification();
        $map->learning_object_id = $learning_object_id;
        $map->classification_id = $classification_id;
        $map->save();

        return $map;
    }

    /**
     * @param locale ...$locales
     * @return void
     */
    public function setup_locales_for_locales_provider(locale ...$locales): void {
        // Set up the locales.
        $ref_class = new ReflectionClass(locales::class);
        $property = $ref_class->getProperty("default_locales");
        $property->setAccessible(true);

        $property->setValue($locales);
        $property->setAccessible(false);

        $this->locales_provider_modified = true;
    }

    /**
     * Reset the default locales for the locales provider.
     * @return void
     */
    public function reset_locales_for_locales_provider(): void {
        if (!$this->locales_provider_modified) {
            return;
        }

        $ref_class = new ReflectionClass(locales::class);
        $property = $ref_class->getProperty("default_locales");
        $property->setAccessible(true);

        $property->setValue(null);
        $property->setAccessible(false);

        $this->locales_provider_modified = false;
    }
}