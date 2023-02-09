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
 * @package ml_recommender
 */

namespace ml_recommender;

use cache;
use core\orm\query\builder;
use ml_recommender\local\environment;
use ml_service\api;
use totara_core\advanced_feature;

/**
 * Load recommendations via the ml service. This is a middleman
 * which will return specific types of recommendations from the appropriate cache.
 */
class recommendations {

    /**
     * @var api
     */
    protected $api;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var null|int
     */
    protected $tenant_id;

    /**
     * @param api $api
     * @param int $user_id
     * @param int|null $tenant_id
     */
    private function __construct(int $user_id, int $tenant_id, api $api) {
        $this->api = $api;
        $this->user_id = $user_id;
        $this->tenant_id = $tenant_id;
    }

    /**
     * Create an instance of the recommendations helper per user/tenant. If the tenant_id is
     * not provided, one will be derived based on the user's context.
     *
     * @param int $user_id
     * @param int|null $tenant_id
     * @param api|null $api
     * @return recommendations
     */
    public static function make(int $user_id, ?int $tenant_id = null, ?api $api = null): recommendations {
        if (null === $tenant_id) {
            $user_context = \context_user::instance($user_id);
            $tenant_id = $user_context->tenantid;
        }
        $api = $api ?? api::make();
        return new self($user_id, $tenant_id ?? 0, $api);
    }

    /**
     * Returns true if the ml service is both enabled & configured.
     *
     * @return bool
     */
    public static function is_ml_service_enabled(): bool {
        global $CFG;
        return !empty($CFG->ml_service_key) && !empty($CFG->ml_service_url) && advanced_feature::is_enabled('ml_recommender');
    }

    /**
     * Sorting by recommendations can be tricky, as order comes from an external source and isn't something
     * that can be derived directly in tables. However we want the query to be sorted so things like pagination
     * can be returned simpler (even though we never return a result set large enough to paginate).
     *
     * This will build a special case syntax which sorts the results based on the recommended ids, and apply it
     * to a builder instance.
     *
     * @param builder $builder The builder instance to apply the ordering to.
     * @param string $id_field The name of the ID field to use in the ordering.
     * @param array $recommendations The list of recommended ids in order from the ml service.
     */
    public function apply_sort_by_recommendations(builder $builder, string $id_field, array $recommendations) {
        // If there are no recommendations, then no sorting is needed.
        if (empty($recommendations)) {
            return;
        }

        $cases = [];
        $params = [];
        $i = 0;
        foreach ($recommendations as $id) {
            $cases[] = "WHEN :rcmdsrt$i THEN $i";
            $params['rcmdsrt' . $i] = $id;
            $i++;
        }

        $statement = join(' ', $cases);
        $builder->add_select_raw("(CASE $id_field $statement END) as recommended_order", $params);
        $builder->order_by_raw('recommended_order');
    }

    /**
     * Will return a collection of IDs of items recommended for this user.
     * Note: these ids must be checked against general visibility rules, visibility may have
     * changed between the export and recommendation times.
     *
     * Returns the array, else will return a null if ml service is not enabled.
     *
     * This list will return from the real service, unless the service is taking too long
     * or isn't available, in which case it will fall back to the last good cached version.
     *
     * @param string $component
     * @return array|null
     */
    public function get_user_recommendations(string $component): ?array {
        if (!self::is_ml_service_enabled()) {
            return null;
        }

        $count = environment::get_user_result_count();

        // Connect to the cache, if we need it
        $cache = cache::make('ml_recommender', 'recommended_user_items');
        $cache_key = join('_', [$component, $this->tenant_id, $this->user_id, $count]);

        // Load the items from the service
        $items = $this->api->call_user_items($this->tenant_id, $this->user_id, $component, $count);
        if (null === $items) {
            // There was an error in the API, check if we can use the cache instead
            if (!$cache->has($cache_key)) {
                return [];
            }

            return $cache->get($cache_key);
        }

        // Persist the results from the service
        $results = array_map(function (array $item): int {
            return (int) $item[0];
        }, $items);
        $cache->set($cache_key, $results);

        return $results;
    }

    /**
     * Load the $CFG->item_result_count number of similar items based on the provided
     * component and item id.
     *
     * Returns an array of ids that are considered similar, or null if the engine is disabled.
     * This function uses the recommended_related_items cache.
     *
     * @param string $component
     * @param int $item_id
     * @return array|null
     */
    public function get_similar_items(string $component, int $item_id): ?array {
        if (!self::is_ml_service_enabled()) {
            return null;
        }

        $count = environment::get_item_result_count();

        // Connect to the cache, if we need it
        $cache = cache::make('ml_recommender', 'recommended_related_items');
        $cache_key = join('_', [$component, $this->tenant_id, $item_id, $count]);

        // First load the cache if it exists - item to item change less frequently.
        if ($cache->has($cache_key)) {
            return $cache->get($cache_key);
        }

        // Load the items from the service
        $items = $this->api->call_similar_items($this->tenant_id, $component . $item_id, $count);
        if (null === $items) {
            // There was an error in the API
            return [];
        }

        // Persist the results from the service to the cache
        $results = array_map(function (array $item): int {
            return (int) $item[0];
        }, $items);
        $cache->set($cache_key, $results);

        return $results;
    }
}