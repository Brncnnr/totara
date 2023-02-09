<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\access;

use cache;
use context_system;
use core\orm\query\builder;

class author_access_handler {
    /**
     * @var int
     */
    private $actor_id;

    /**
     * Just a constant key that are used for the cache.
     * @var string
     */
    private const RESULT_KEY = 'result';

    /**
     * author_access_handler constructor.
     * @param int $actor_id
     */
    public function __construct(int $actor_id) {
        $this->actor_id = $actor_id;
    }

    /**
     * @return bool
     */
    private function is_participant(): bool {
        global $DB;
        return $DB->record_exists_sql(
            '
                SELECT 1 FROM "ttr_tenant" t 
                INNER JOIN "ttr_cohort_members" cm ON t.cohortid = cm.cohortid 
                WHERE cm.userid = :actor_id
            ',
            ['actor_id' => $this->actor_id]
        );
    }

    /**
     * Given the list of user's id(s). This function will try to return the list of
     * users that this actor is able to see.
     *
     * @param int[] $target_users
     * @return int[]
     */
    private function get_tenant_access_against_users(array $target_users): array {
        global $CFG, $DB;

        if (!$CFG->tenantsenabled) {
            debugging("Tenancy had been turned off for the site", DEBUG_DEVELOPER);
            return [];
        }

        $target_tenant_id = $DB->get_field('user', 'tenantid', ['id' => $this->actor_id]);

        $builder = builder::table('user', 'u');
        $builder->select(['u.id AS user_id']);

        if (!empty($target_tenant_id)) {
            $builder->where(
                function (builder $in_builder) use ($CFG, $target_tenant_id): void {
                    $in_builder->where('u.tenantid', $target_tenant_id);

                    if (!empty($CFG->tenantsisolated)) {
                        return;
                    }

                    // Not in isolation mode, hence we will enable the system user.
                    $in_builder->where_null('u.tenantid', true);
                }
            );
        } else {
            // User is system user.
            if (!$this->is_participant()) {
                $builder->when(
                    (!empty($CFG->tenantsisolated)),
                    function (builder $inner_builder): void {
                        $inner_builder->where_null('u.tenantid');
                    }
                );
            } else {
                // Hence we fetch all the users in a tenant that this user participate in or
                // the user that does not have tenant.
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->select('t.id');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->where('cm.userid', $this->actor_id);

                $join_condition = function (builder $join): void {
                    $join->where_field('u.tenantid', 'tenant.id');
                    $join->where_null('u.tenantid', true);
                };

                $builder->when(
                    (!empty($CFG->tenantsisolated)),
                    function (builder $inner_builder) use ($tenant_builder, $join_condition): void {
                        $inner_builder->join([$tenant_builder, 'tenant'], $join_condition);
                    },
                    function (builder $inner_builder) use ($tenant_builder, $join_condition): void {
                        $inner_builder->left_join([$tenant_builder, 'tenant'], $join_condition);
                    }
                );
            }
        }

        $builder->where_in('u.id', $target_users);
        $builder->results_as_arrays();

        $records = $builder->fetch();

        return array_map(
            function (array $record): int {
                return $record['user_id'];
            },
            $records
        );
    }

    /**
     * Saving the result to the request cache.
     *
     * @param array $results    Hashmap of user's id and the result of whether the actor is able to see
     *                          those users or not.
     * @return void
     */
    private function save_access_result_to_cache(array $results): void {
        $cache = cache::make('totara_comment', 'author_access');
        if ($cache->has($this->actor_id)) {
            $cache_data = $cache->get($this->actor_id);
            $old_accesses = $cache_data[static::RESULT_KEY];

            // Merging the old and new results together. Where we are keeping the new values from the $results.
            // If the values from the $results are stored against the same user's id but are different.
            foreach ($old_accesses as $user_id => $old_result_value) {
                if (!array_key_exists($user_id, $results)) {
                    $results[$user_id] = $old_result_value;
                }
            }
        }

        $cache_data = [
            static::RESULT_KEY => $results
        ];

        $cache->set($this->actor_id, $cache_data);
    }

    /**
     * @return array
     */
    private function get_access_result_from_cache(): array {
        $cache = cache::make('totara_comment', 'author_access');
        if (!$cache->has($this->actor_id)) {
            return [];
        }

        $cache_data = $cache->get($this->actor_id);
        return $cache_data[static::RESULT_KEY] ?? [];
    }

    /**
     * Calculating the access between the actor against the list of target user.
     *
     * @param int[] $target_users   The list of user's ids.
     * @return void
     */
    public function process_access_against_users(array $target_users): void {
        global $CFG;

        // Actor can see actor self.
        $result = [$this->actor_id => true];
        $that = $this;

        $target_users = array_filter(
            $target_users,
            function (int $target_user_id) use ($that): bool {
                return $that->actor_id !== $target_user_id;
            }
        );

        $system_context = context_system::instance();
        if (!$CFG->tenantsenabled || has_capability('totara/tenant:config', $system_context, $this->actor_id)) {
            // Tenancy is not enabled, hence the actor user can see every users.
            foreach ($target_users as $user_id) {
                $result[$user_id] = true;
            }
        } else {
            $can_see_users = $this->get_tenant_access_against_users($target_users);
            foreach ($target_users as $target_user_id) {
                $result[$target_user_id] = in_array($target_user_id, $can_see_users);
            }
        }

        $this->save_access_result_to_cache($result);
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function can_see_user(int $user_id): bool {
        $cache_result = $this->get_access_result_from_cache();

        if (!array_key_exists($user_id, $cache_result)) {
            $this->process_access_against_users([$user_id]);
            $cache_result = $this->get_access_result_from_cache();
        }

        return $cache_result[$user_id] ?? false;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public static function delete_cache_for_user(int $user_id): void {
        $cache = cache::make('totara_comment', 'author_access');
        $cache->delete($user_id);
    }
}