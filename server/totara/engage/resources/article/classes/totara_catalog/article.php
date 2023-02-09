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
 * @package engage_article
 * @category totara_catalog
 */
namespace engage_article\totara_catalog;

use core_user\totara_engage\share\recipient\user;
use totara_catalog\provider;
use totara_core\advanced_feature;
use totara_engage\access\access;
use core\orm\query\builder;
use engage_article\totara_engage\resource\article as model_article;
use core\orm\query\raw_field;
use totara_engage\access\access_manager;
use totara_engage\link\builder as link_builder;

final class article extends provider {

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
        // NOTE: articles are called resources in the front end.
        return get_string('resources', 'engage_article');
    }

    /**
     * @return string
     */
    public static function get_object_type(): string {
        return 'engage_article';
    }

    /**
     * @return string
     */
    public function get_object_table(): string {
        return '{engage_article}';
    }

    /**
     * @return string
     */
    public function get_objectid_field(): string {
        return 'id';
    }

    /**
     * Annoyingly the array of objects handed through is actually a single object wrapped in an array
     * The cache has been added to reduce database queries as much as possible, and this function
     * should work properly if the objects are ever handed through in bulk.
     *
     * @param array $objects
     * @return array
     */
    public function can_see(array $objects): array {
        global $USER;

        $results = [];

        $cache = \cache::make('engage_article', 'catalog_visibility');
        $cached_access_items = $cache->get($USER->id);

        // Library capability should be enabled
        $can_view = has_capability('totara/engage:viewlibrary', \context_user::instance($USER->id), $USER->id);

        // Lightweight visibility checks.
        // Note: the visibility is being checked in a local switch like this to avoid several class loads.
        foreach ($objects as $object) {
            if (!isset($cached_access_items[$object->objectid]) || !$can_view) {
                // The object is not appearing in the list of access-able items.
                $results[$object->objectid] = false;
                continue;
            }

            $visibility = $cached_access_items[$object->objectid];
            switch ($visibility->access) {
                case access::PRIVATE:
                    $results[$object->objectid] = $visibility->userid == $USER->id;
                    break;
                case access::PUBLIC:
                    $results[$object->objectid] = true;
                    break;
                case access::RESTRICTED:
                    // Prevent passing null as input parameter in PHP 8.1
                    $accessors = explode(',', $visibility->accessors ?? '');

                    $results[$object->objectid] = $visibility->userid == $USER->id || in_array($USER->id, $accessors);
                    break;
            }

            if ($results[$object->objectid] == false) {
                $results[$object->objectid] = access_manager::can_manage_engage(\context::instance_by_id($visibility->contextid));
            }
        }

        return $results;
    }

    /**
     * Creating a cache records for article visibility.
     * @return void
     */
    public function prime_provider_cache(): void {
        global $DB, $USER, $CFG;

        $builder = builder::table('engage_resource', 'er');
        $builder->left_join(
            ['engage_share', 'es'],
            function (builder $join): void {
                $join->where_field('er.id', 'es.itemid');
                $join->where_field('er.resourcetype', 'es.component');
            }
        );

        // Join the user so that we can exclude any deleted user.
        $builder->join(
            ['user', 'out_user'],
            function (builder $join): void {
                $join->where_field('er.userid', 'out_user.id');
                $join->where('out_user.deleted', 0);
                $join->where('out_user.confirmed', 1);
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

        $builder->where('er.resourcetype', model_article::get_resource_type());
        $builder->select([
            'er.instanceid AS id',
            'er.access',
            'er.userid',
            'er.contextid',

            // Field ttr_engage_share_recipient.instanceid is pointing to the user's id.
            new raw_field("{$DB->sql_group_concat('esr.instanceid', ',')} AS accessors")
        ]);

        $builder->group_by([
            'er.instanceid',
            'er.access',
            'er.userid',
            'er.contextid',
        ]);

        $builder->results_as_objects();

        if (!empty($CFG->tenantsenabled)) {
            // Multi-tenancy is on.
            $tenant_id = null;
            if (!empty($USER->tenantid)) {
                $tenant_id = $USER->tenantid;
            } else {
                $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $USER->id]);
            }

            if (null !== $tenant_id) {
                $builder->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('er.userid', 'u.id');
                        $join->where('u.deleted', 0);
                        $join->where('u.suspended', 0);

                        if (empty($CFG->tenantsisolated)) {
                            // Isolation mode is off. Therefore we will included none tenant's user as well.
                            $join->where_raw(
                                '(u.tenantid = :tenant_id OR u.tenantid IS NULL)',
                                ['tenant_id' => $tenant_id]
                            );
                        } else {
                            $join->where('u.tenantid', $tenant_id);
                        }
                    }
                );
            } else {
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->select('t.id AS tenant_id');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->where('cm.userid', $USER->id);

                $collection = $tenant_builder->get();
                $tenant_ids = $collection->pluck('tenant_id');

                if (!empty($tenant_ids)) {
                    [$in_sql, $parameters] = $DB->sql_in($tenant_ids);
                    $builder->join(
                        ['user', 'u'],
                        function (builder $join) use ($in_sql, $parameters): void {
                            $join->where_field('er.userid', 'u.id');
                            $join->where_raw(
                                "(u.tenantid {$in_sql} OR u.tenantid IS NULL)",
                                $parameters
                            );
                        }
                    );
                } else {
                    $builder->join(
                        ['user', 'u'],
                        function (builder $join): void {
                            $join->where_field('er.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                // In either cases, we still have to fetch for the records that are not from users
                // who got deleted/suspended.
                $builder->where('u.deleted', 0);
                $builder->where('u.suspended', 0);
            }
        }

        // We are caching per user's bucket. As this should only live within a request anyway - and for every request,
        // there will be a required actor.
        $cache = \cache::make('engage_article', 'catalog_visibility');
        $cached_access_items = [];

        $access_items = $builder->fetch();
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
     * @return array|null
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
                        'ftstags',
                    ],
                    'low' => [
                        'ftscontent',
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
            SELECT art.id as objectid, 
                   res.resourcetype as objecttype, 
                   res.id as resourceid, 
                   con.id as contextid
            FROM "ttr_engage_article" art
            JOIN "ttr_engage_resource" res 
                ON res.instanceid = art.id
                AND res.resourcetype = \'engage_article\'
            JOIN "ttr_user" u
                ON res.userid = u.id
                AND u.deleted = 0 AND u.confirmed = 1
            JOIN "ttr_context" con
                ON con.contextlevel = :level
                AND con.instanceid = res.userid
        ';

        return [$sql, ['level' => CONTEXT_USER]];
    }

    /**
     * @param int $objectid
     * @return string|null
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
        global $DB;

        $resourceid = $DB->get_field(
            'engage_resource',
            'id',
            [
                'resourcetype' => 'engage_article',
                'instanceid' => $objectid
            ]
        );

        $article_destination = link_builder::to('engage_article', ['id' => $resourceid]);
        $article_destination->from('totara_catalog');

        $url = $article_destination->out();

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
