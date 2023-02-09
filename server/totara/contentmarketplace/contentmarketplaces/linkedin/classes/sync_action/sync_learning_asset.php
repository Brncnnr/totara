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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\sync_action;

use coding_exception;
use contentmarketplace_linkedin\api\v2\api;
use contentmarketplace_linkedin\api\v2\service\learning_asset\query\criteria;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\collection;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\element;
use contentmarketplace_linkedin\api\v2\service\learning_asset\service;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\dto\timestamp;
use contentmarketplace_linkedin\entity\classification;
use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\entity\learning_object_classification;
use contentmarketplace_linkedin\model\learning_object;
use core\orm\query\builder;
use progress_trace;
use totara_contentmarketplace\sync\external_sync;
use totara_contentmarketplace\sync\sync_action;
use totara_core\http\client;
use totara_core\http\exception\auth_exception;
use totara_core\http\exception\http_exception;

/**
 * Class learning_asset
 * @package contentmarketplace_linkedin\action
 */
class sync_learning_asset extends sync_action implements external_sync {
    /**
     * The mappable level for linkedin learning classifications
     *
     * @var array
     */
    private const MAPPABLE_LEVEL = [
        constants::CLASSIFICATION_TYPE_TOPIC => [constants::CLASSIFICATION_TYPE_SUBJECT],
        constants::CLASSIFICATION_TYPE_SUBJECT => [constants::CLASSIFICATION_TYPE_LIBRARY],
        constants::CLASSIFICATION_TYPE_LIBRARY => [],
    ];

    /**
     * The default threshold number which allow number http calls fail up to.
     *
     * @var int
     */
    private const DEFAULT_HTTP_FAILURE_THRESHOLD = 2;

    /**
     * The maximum count for linkedin learning's API to get the learning assets.
     * @var int
     */
    private const MAXIMUM_COUNT = 100;

    /**
     * @var client
     */
    private $client;

    /**
     * @var int
     */
    private $time_run;

    /**
     * A flag to say that this action is instantiated to only sync those records
     * that had been modified since the specific time, from linkedin learning.
     *
     * Set this to FALSE to perform full syncing. Otherwise true to optimize the sync
     * and only pull the newly updated records.
     *
     * By default, it is TRUE.
     *
     * @var bool
     */
    private $sync_with_last_time_modified;

    /**
     * The collection of asset types.
     * We are querying by each of the asset type, because from linkedin they have a limited
     * response when there are two or more asset types included in the query.
     * See https://docs.microsoft.com/en-us/linkedin/learning/reference/learningassets#limited-response-scenarios
     *
     * By default we are only going to query COURSE.
     *
     * @var array
     */
    private $asset_types;

    /**
     * The number of threshold which allow number of HTTP calls fail up to.
     *
     * @var int
     */
    private $http_failure_threshold;

    /**
     * The list of http exceptions that were caught during the execution.
     * Note that this list will be reset everytime the {@see sync_learning_asset::invoke()}
     * is called.
     *
     * @var http_exception[]
     */
    private $caught_request_exceptions;

    /**
     * sync_learning_asset constructor.
     * @param bool                $is_initial_run
     * @param int|null            $time_run
     * @param progress_trace|null $trace
     */
    public function __construct(
        bool $is_initial_run = false,
        ?int $time_run = null,
        ?progress_trace $trace = null
    ) {
        parent::__construct($is_initial_run, $trace);
        $this->client = null;
        $this->time_run = $time_run ?? time();
        $this->sync_with_last_time_modified = true;

        $this->asset_types = [
            constants::ASSET_TYPE_COURSE,
        ];

        $this->http_failure_threshold = self::DEFAULT_HTTP_FAILURE_THRESHOLD;
        $this->caught_request_exceptions = [];
    }

    /**
     * @param int $new_threshold
     * @return void
     */
    public function set_http_failure_threshold(int $new_threshold): void {
        $this->http_failure_threshold = $new_threshold;
    }

    /**
     * @return http_exception[]
     */
    public function get_caught_request_exceptions(): array {
        return $this->caught_request_exceptions;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_sync_with_last_time_modified(bool $value): void {
        $this->sync_with_last_time_modified = $value;
    }

    /**
     * @param string[] $asset_types
     * @return void
     */
    public function set_asset_types(string ...$asset_types): void {
        if (empty($asset_types)) {
            throw new coding_exception("Cannot set the asset types as empty");
        }

        $this->asset_types = [];

        foreach ($asset_types as $asset_type) {
            constants::validate_asset_type($asset_type);

            $this->asset_types[] = $asset_type;
        }
    }

    /**
     * @return bool
     */
    public function is_skipped(): bool {
        if (!config::client_id() || !config::client_secret()) {
            return true;
        }

        if ($this->is_initial_run && config::completed_initial_sync_learning_asset()) {
            return true;
        }

        if (!$this->is_initial_run && !config::completed_initial_sync_learning_asset()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function invoke(): void {
        if ($this->is_skipped()) {
            // The sync action should be skipped.
            return;
        }

        if (null === $this->client) {
            throw new coding_exception("Cannot run sync when client is not set");
        }

        $api = api::create($this->client);
        $time_consumes = [];

        // Reset the list of caught exception. This list of exception will be output to stdOut
        // for system admin or whoever is looking into the logs
        $this->caught_request_exceptions = [];

        foreach ($this->asset_types as $asset_type) {
            $time_start = microtime(true);

            $criteria = new criteria();
            $criteria->set_licensed_only(true);
            $criteria->set_asset_types([$asset_type]);

            if (!$this->is_initial_run && $this->sync_with_last_time_modified) {
                $last_modified_at = config::last_time_sync_learning_asset();

                if (null !== $last_modified_at) {
                    // Converting the last time modified into milliseconds. However, only when it
                    // is not null. Because we want to treat null as not provided for the API call.
                    // Otherwise, "null * 1000 = 0" which will be included in the API call.
                    $last_modified_at *= timestamp::MILLISECONDS_IN_SECOND;
                }

                $criteria->set_last_modified_after($last_modified_at);
            }

            $this->trace->output("Sync for type: {$asset_type}");
            $criteria->set_count(self::MAXIMUM_COUNT);

            $service = new service($criteria);
            $current = 0;

            // A flag to identify how many times Linkedin Learning response as failures to us.
            // In order to stop requesting, when the threshold number is reached.
            $request_failures = 0;

            // A flag to help identify if we are going to push the sync to another threshold to be
            // sure that we are not missing anything.
            $sync_pushed = false;

            while (true) {
                try {
                    /** @var collection $collection */
                    $collection = $api->execute($service);
                    $elements = $collection->get_elements();

                    if (empty($elements)) {
                        // No learning assets found anymore. This could be because of the exceeding limitting
                        // from Linkedin Learning that we are trying.
                        break;
                    }

                    foreach ($elements as $element) {
                        if (!$this->is_initial_run) {
                            // So on normal sync, we are trying to push fetching data a little further.
                            // Which we are mutating the offset, and because of that, there will be a different
                            // types of learning assets included in the response. Therefore, this block of code
                            // is here to ignore that sync happening.
                            if ($element->get_type() !== $asset_type) {
                                continue;
                            }
                        }

                        $this->do_sync_element($element);
                    }

                    $pagination = $collection->get_paging();
                    $total = $pagination->get_total();
                    $current += count($elements);

                    if (!$sync_pushed) {
                        $this->trace->output("Syncing {$current}/{$total}");
                    } else {
                        $this->trace->output("Syncing extra");
                    }

                    if (!$pagination->has_next()) {
                        if ($this->is_initial_run) {
                            // For initial run, we should skip the whole process when there is no next page.
                            break;
                        }

                        $sync_pushed = true;

                        // This is a hack to make sure that we are not missing anything from
                        // Linkedin Learning ends, when syncing the learning assets.
                        // By using the calculated new offset, base upon the current offset
                        // and the total of current elements.
                        $new_start = $pagination->get_start() + count($elements);
                        $criteria->set_start($new_start);
                        continue;
                    }

                    $next_href = $pagination->get_next_link();

                    $criteria->clear();
                    $criteria->set_parameters_from_paging_url($next_href);
                } catch (auth_exception $e) {
                    // We do not care about authentication exception, bubble it through.
                    throw $e;
                } catch (http_exception $e) {
                    // Bad response format response. This can potentially happen because of internal
                    // server error response from Linkedin LEarning which were captured and still output
                    // it as 200 response code.
                    $request_failures += 1;
                    $this->caught_request_exceptions[] = $e;
                }

                if ($request_failures === $this->http_failure_threshold) {
                    // Too many request failures, shutdown the sync for asset type gracefully.
                    break;
                }
            }

            $time_end = microtime(true);
            $total_time_consumes_for_type = round($time_end - $time_start);
            $this->trace->output("Finish syncing with the total of records: {$current}");

            if ($this->performance_debug) {
                $this->trace->output("Completed sync for type {$asset_type} after {$total_time_consumes_for_type}");
            }

            $time_consumes[] = $total_time_consumes_for_type;
        }

        if ($this->is_initial_run) {
            config::save_completed_initial_sync_learning_asset(true);
        }

        config::save_last_time_sync_learning_asset($this->time_run);

        if (!empty($this->caught_request_exceptions)) {
            // Output to the stdOut for debugging purpose.
            foreach ($this->caught_request_exceptions as $exception) {
                $this->trace->output($exception->getMessage());
                $this->trace->output($exception->getTraceAsString());
            }
        }

        if ($this->performance_debug) {
            $this->trace->output(
                sprintf(
                    "Completed sync after: %d seconds",
                    array_sum($time_consumes)
                )
            );
        }

        $this->remove_unused_classification();
    }

    /**
     * @return void
     * @throws coding_exception
     */
    private function remove_unused_classification(): void {
        $classification_ids = builder::table(classification::TABLE, 'c')
            ->join([learning_object_classification::TABLE, 'loc'], 'loc.classification_id', 'c.id')
            ->join([learning_object_entity::TABLE, 'lo'], 'lo.id', 'loc.learning_object_id')
            ->select_raw('c.id')
            ->group_by('c.id')
            ->get()
            ->keys();

        $db = builder::get_db();
        $classification_id_chunks = array_chunk($classification_ids, $db->get_max_in_params());
        $transaction = $db->start_delegated_transaction();
        foreach ($classification_id_chunks as $classification_id_chunk) {
            [$cm_ids_sql, $cm_ids_params] = $db->get_in_or_equal($classification_id_chunk, SQL_PARAMS_NAMED, 'param', false);
            $db->delete_records_select(classification::TABLE, "id $cm_ids_sql", $cm_ids_params);
        }
        $transaction->allow_commit();
    }

    /**
     * @param element $element
     * @return void
     */
    private function do_sync_element(element $element): void {
        $urn = $element->get_urn();

        if ($this->is_initial_run) {
            $learning_object = learning_object::create_from_element($element);
        } else {
            $repository = learning_object_entity::repository();
            $entity = $repository->find_by_urn($urn);

            if (null === $entity) {
                // The learning asset element is new to our system.
                $learning_object = learning_object::create_from_element($element);
            } else {
                $learning_object = new learning_object($entity);
                $learning_object->update_from_element($element);
            }
        }

        $entity = $learning_object->get_entity();
        $this->populate_classifications($entity, $element);
    }

    /**
     * @param learning_object_entity $entity
     * @param element                $element
     *
     * @return void
     */
    private function populate_classifications(learning_object_entity $entity, element $element): void {
        // Progress on classification path and its relationship.
        $db = builder::get_db();
        $classifications = $element->get_classifications();

        $relation_ids = [];
        foreach ($classifications as $classification_with_path) {
            $classification_with_path_type = $classification_with_path->get_type();
            if (!isset(self::MAPPABLE_LEVEL[$classification_with_path_type])) {
                // Skips for those that cannot be map.
                continue;
            }

            $classification_with_path_urn = $classification_with_path->get_urn();
            $classification_id = $db->get_field(
                classification::TABLE,
                'id',
                ['urn' => $classification_with_path_urn],
                IGNORE_MISSING
            );

            if (empty($classification_id)) {
                // Cannot find the classification's id within our database.
                $this->trace->output(
                    "Cannot find the classification with urn {$classification_with_path_urn}",
                    4
                );

                continue;
            }

            // Create the learning object classification relationship.
            $learning_object_relation_exist = $db->get_record(
                learning_object_classification::TABLE,
                [
                    'classification_id' => $classification_id,
                    'learning_object_id' => $entity->id
                ]
            );

            if (!$learning_object_relation_exist) {
                $relation = new learning_object_classification();
                $relation->classification_id = $classification_id;
                $relation->learning_object_id = $entity->id;
                $relation = $relation->save();
                $relation_ids[] = $relation->id;
            } else {
                $relation_ids[] = $learning_object_relation_exist->id;
            }

            $path = $classification_with_path->get_path();
            if (empty($path)) {
                // No path set.
                continue;
            }

            foreach ($path as $parent_classification) {
                $map_level = self::MAPPABLE_LEVEL[$classification_with_path_type] ?? [];
                if (!in_array($parent_classification->get_type(), $map_level)) {
                    continue;
                }

                $parent_classification_urn = $parent_classification->get_urn();
                $parent_classification_id = $db->get_field(
                    classification::TABLE,
                    'id',
                    ['urn' => $parent_classification->get_urn()],
                    IGNORE_MISSING
                );

                if (empty($parent_classification_id)) {
                    $this->trace->output(
                        "Cannot find the parent classification with urn {$parent_classification_urn}",
                        4
                    );

                    continue;
                }

                $classification_relationship_existing = $db->record_exists(
                    classification_relationship::TABLE,
                    [
                        'child_id' => $classification_id,
                        'parent_id' => $parent_classification_id
                    ]
                );

                if (!$classification_relationship_existing) {
                    $classification_relationship = new classification_relationship();
                    $classification_relationship->child_id = $classification_id;
                    $classification_relationship->parent_id = $parent_classification_id;

                    $classification_relationship->save();
                }
            }
        }
        $builder = builder::table(learning_object_classification::TABLE)->where('learning_object_id', $entity->id);
        if (!empty($relation_ids)) {
            $builder->where_not_in('id', $relation_ids);
        }

        $builder->delete();
    }

    /**
     * @param client $client
     * @return void
     */
    public function set_api_client(client $client): void {
        $this->client = $client;
    }
}