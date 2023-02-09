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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 * @category totara_catalog
 */
namespace totara_playlist\totara_catalog;

use core\orm\query\raw_field;
use core_user\totara_engage\share\recipient\user;
use totara_catalog\provider;
use totara_core\advanced_feature;
use totara_engage\access\access;
use core\orm\query\builder;
use totara_engage\access\access_manager;
use totara_playlist\playlist as model_playlist;
use totara_engage\link\builder as link_builder;

/**
 * Totara catalog provider for playlists
 */
final class playlist extends provider {

    /**
     * @var [] Caches configuration for this provider.
     */
    private $config_cache = null;

    /**
     * @return bool
     */
    public static function is_plugin_enabled(): bool {
        return advanced_feature::is_enabled('engage_resources');
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('playlists', 'totara_playlist');
    }

    /**
     * @return string
     */
    public static function get_object_type(): string {
        return 'playlist';
    }

    /**
     * @return string
     */
    public function get_object_table(): string {
        return '{playlist}';
    }

    /**
     * @return string
     */
    public function get_objectid_field(): string {
        return 'id';
    }

    /**
     * @param array $objects
     * @return array
     */
    public function can_see(array $objects): array {
        global $USER;

        $results = [];

        $cache = \cache::make('totara_playlist', 'catalog_visibility');
        $access_items = $cache->get($USER->id);

        if (!is_array($access_items) && !$access_items) {
            $access_items = [];
        }

        // Library capability should be enabled
        $can_view = has_capability('totara/engage:viewlibrary', \context_user::instance($USER->id), $USER->id);

        // Lightweight visibility checks.
        // Note: the visibility is being checked in a local switch like this to avoid several class loads.
        foreach ($objects as $object) {
            if (!isset($access_items[$object->objectid]) || !$can_view) {
                // No visibility was found. Therefore, we will skip it.
                $results[$object->objectid] = false;
                continue;
            }

            $visibility = $access_items[$object->objectid];

            switch ($visibility->access) {
                case access::PRIVATE:
                    $results[$object->objectid] = $visibility->userid == $USER->id;
                    break;
                case access::PUBLIC:
                    $results[$object->objectid] = true;
                    break;
                case access::RESTRICTED:
                    $accessors = explode(',', $visibility->accessors);
                    $results[$object->objectid] = (
                        $visibility->userid == $USER->id || in_array($USER->id, $accessors)
                    );
                    break;
            }

            if ($results[$object->objectid] == false) {
                $results[$object->objectid] = access_manager::can_manage_engage(\context::instance_by_id($visibility->contextid));
            }
        }

        return $results;
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function prime_provider_cache(): void {
        global $DB, $USER, $CFG;

        if (advanced_feature::is_disabled('engage_resources')) {
            return;
        }

        $builder = builder::table('playlist', 'pl');
        $builder->join(
            ['user', 'out_user'],
            function (builder $join): void {
                $join->where_field('pl.userid', 'out_user.id');
                $join->where('out_user.deleted', 0);

                // Excluded non confirmed user.
                $join->where('out_user.confirmed', 1);
            }
        );

        $builder->left_join(
            ['engage_share', 'es'],
            function (builder $join): void {
                $join->where_field('pl.id', 'es.itemid');
                $join->where('es.component', model_playlist::get_resource_type());
            }
        );

        $builder->left_join(
            ['engage_share_recipient', 'esr'],
            function (builder $join): void {
                $join->where_field('es.id', 'esr.shareid');
                $join->where('esr.area', user::AREA);
                $join->where('esr.component', 'core_user');
            }
        );

        $builder->select([
            "pl.id",
            "pl.access",
            "pl.userid",
            "pl.contextid",
            new raw_field("{$DB->sql_group_concat('esr.instanceid', ',')} AS accessors"),
        ]);

        $builder->group_by([
            'pl.id',
            'pl.access',
            'pl.userid',
            'pl.contextid'
        ]);

        if (!empty($CFG->tenantsenabled) && !is_siteadmin()) {
            // Multi-tenancy is enabled, therefore we are going to include joins with cohort members
            // in order to filter out what the playlist that this user should be view able.
            $tenant_id = null;
            if (!empty($USER->tenantid)) {
                $tenant_id = $USER->tenantid;
            } else {
                $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $USER->id], MUST_EXIST);
            }

            if (null !== $tenant_id) {
                // This user is a part of tenant. Joinining table should be easy enough.
                $builder->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('pl.userid', 'u.id');
                        $join->where('u.suspended', 0);
                        $join->where('u.deleted', 0);

                        if (empty($CFG->tenantsisolated)) {
                            // Isolation mode is off.
                            $join->where_raw(
                                '(u.tenantid = :tenant_id OR u.tenantid IS NULL)',
                                ['tenant_id' => $tenant_id]
                            );
                        } else {
                            $join->where('u.tenantid', $tenant_id);
                        }
                    }
                );
                // The sql to fetch all the visibile records to the user will look something like bellow
                // --------------- SQL ---------------
                //  SELECT pl.id, pl.access,
                //      pl.userid,
                //      string_agg(CAST(esr.instanceid AS VARCHAR), ',') AS accessors
                //
                //  FROM phpu_00playlist "pl"
                //  LEFT JOIN phpu_00engage_share "es"
                //      ON pl.id = es.itemid
                //      AND es.component = $1
                //
                //  LEFT JOIN phpu_00engage_share_recipient "esr"
                //      ON es.id = esr.shareid
                //      AND esr.area = $2
                //      AND esr.component = $3
                //
                //  INNER JOIN phpu_00user "u" ON u.id = pl.userid
                //      AND (u.tenantid = :tenant_id OR u.tenantid IS NULL)
                //      AND u.deleted = 0
                //      AND u.suspended = 0
                //
                //  WHERE 1 = 1
                //  GROUP BY pl.id, pl.access, pl.userid
                // --------------- END OF SQL ---------------
            } else {
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->select('t.id AS tenant_id');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->where('cm.userid', $USER->id);

                $collection = $tenant_builder->get();
                $tenant_ids = $collection->pluck('tenant_id');

                if (!empty($tenant_ids)) {
                    // User at least participate within one tenant.
                    [$in_sql, $parameters] = $DB->sql_in($tenant_ids);
                    $builder->join(
                        ['user', 'u'],
                        function (builder $join) use ($in_sql, $parameters): void {
                            $join->where_field('pl.userid', 'u.id');
                            $join->where_raw(
                                "(u.tenantid {$in_sql} OR u.tenantid IS NULL)",
                                $parameters
                            );
                        }
                    );
                } else {
                    $builder->join(
                        ['user', 'u'],
                        function(builder $join): void {
                            $join->where_field('pl.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                // In either cases, we still have to excluded the records created by suspended/deleted user.
                $builder->where('u.suspended', 0);
                $builder->where('u.deleted', 0);
            }
        }

        $builder->results_as_objects();
        $access_items = $builder->fetch();

        // We are caching the list of items per user.
        $cache = \cache::make('totara_playlist', 'catalog_visibility');
        $cached_access_items = [];

        if (empty($access_items)) {
            $cached_access_items = [];
        } else {
            /** @var \stdClass $access_item */
            foreach ($access_items as $access_item) {
                $cached_access_items[$access_item->id] = $access_item;
            }
        }

        $cache->set($USER->id, $cached_access_items);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get_data_holder_config(string $key) {
        if (is_null($this->config_cache)) {
            $this->config_cache = [
                'sort' => [
                    'text' => 'name',
                    'time' => 'timecreated',
                ],
                'fts' => [
                    'high' => [
                        'name',
                    ],
                    'medium' => [
                        'ftstags', // Note: Topics.
                        'ftssummary', // Note: This makes hashtags searchable since they're in the summary.
                    ],
                    'low' => [
                    ],
                ],
                'image'       => 'image',
            ];
        }

        if (array_key_exists($key, $this->config_cache)) {
            return $this->config_cache[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function get_all_objects_sql(): array {
        $sql = '
            SELECT plst.id as objectid, \'playlist\' as objecttype, con.id as contextid
            FROM "ttr_playlist" plst
            INNER JOIN "ttr_context" con
                ON con.contextlevel = :level AND con.instanceid = plst.userid
            INNER JOIN "ttr_user" u 
                ON plst.userid = u.id
                AND u.deleted = 0
                AND u.confirmed = 1
        ';

        return [$sql, ['level' => CONTEXT_USER]];
    }

    /**
     * @param int $objectid
     * @return mixed|null
     */
    public function get_manage_link(int $objectid) {
        $link = new \moodle_url('/totara/engage/your_resources.php');
        return $link->out();
    }

    /**
     * @param int $objectid
     * @return \stdClass|null
     */
    public function get_details_link(int $objectid) {
        // Grab our existing filter
        $url = link_builder::to('totara_playlist', ['id' => $objectid])
            ->from('totara_catalog')
            ->out();

        $link = new \stdClass();
        $link->description = '';
        $link->button = new \stdClass();
        $link->button->url = $url;
        $link->button->label = get_string('catalog_view', 'moodle');
        return $link;
    }

    /**
     * These buttons show up to the left of the create button
     * @return array
     */
    public function get_buttons(): array {
        return [];
    }
}
