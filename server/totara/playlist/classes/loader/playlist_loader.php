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
 * @package totara_playlist
 */
namespace totara_playlist\loader;

use core\orm\query\order;
use core\orm\query\table;
use core_user\totara_engage\share\recipient\user;
use totara_engage\access\access;
use totara_playlist\pagination\cursor_paginator;
use totara_playlist\playlist;
use totara_playlist\query\option\playlist_source;
use totara_playlist\query\option\playlist_sort;
use totara_playlist\query\playlist_query;
use core\orm\query\builder;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\entity\playlist_resource as playlist_resource_entity;
use totara_engage\entity\engage_bookmark;
use totara_engage\entity\share as share_entity;
use totara_engage\entity\share_recipient as share_recipient_entity;
use totara_engage\entity\rating as engage_rating_entity;

/**
 * Loader class to load playlists.
 */
final class playlist_loader {
    /**
     * @param playlist_query $query
     * @return builder
     */
    protected static function base_builder(playlist_query $query): builder {
        $user_id = $query->get_user_id();
        $user_fields_sql = get_all_user_name_fields(true, 'u', null, 'user_');

        // The first builder is where we are fetching all the playlists that are own
        // by the user or the playlists that are being set to public.
        $first_builder = builder::table(playlist_entity::TABLE, 'p');
        $first_builder->join(
            ['user', 'u'],
            function (builder $join): void {
                $join->where_field('p.userid', 'u.id');
                $join->where('u.deleted', 0);
                $join->where('u.suspended', 0);

                $join->where('u.confirmed', 1);
            }
        );

        $first_builder->select('p.*');
        $first_builder->add_select_raw($user_fields_sql);
        $first_builder->add_select([
            'u.id AS user_id',
            'u.email AS user_email',
            'u.imagealt AS user_imagealt',
            'u.picture AS user_picture'
        ]);

        $first_builder->results_as_arrays();
        $first_builder->map_to([static::class, 'create_playlist']);

        // Only fetching those playlists that are belong to this user or the public one.
        // The share resource will be treated as union query.
        $first_builder->where_raw(
            '(p.userid = :user_id_x OR p.access = :access_public)',
            [
                'user_id_x' => $user_id,
                'access_public' => access::PUBLIC
            ]
        );

        $access = $query->get_access();
        if (null === $access || access::is_restricted($access)) {
            // Second builder is where we are fetching the shared playlist with the current user.
            // will be union to the first builder.
            $second_builder = builder::table(playlist_entity::TABLE, 'p');
            $second_builder->join(
                ['user', 'u'],
                function (builder $join): void {
                    $join->where_field('p.userid', 'u.id');
                    $join->where('u.deleted', 0);
                    $join->where('u.suspended', 0);

                    $join->where('u.confirmed', 1);
                }
            );

            $second_builder->join(
                [share_entity::TABLE, 'es'],
                function (builder $join): void {
                    $join->where_field('es.itemid', 'p.id');
                    $join->where('es.component', 'totara_playlist');
                }
            );

            // Only looking for the playlists that are shared with this user.
            $second_builder->join(
                [share_recipient_entity::TABLE, 'esr'],
                function (builder $join): void {
                    $join->where_field('esr.shareid', 'es.id');
                    $join->where('component', 'core_user');
                    $join->where('area', user::AREA);
                }
            );

            $second_builder->where('esr.instanceid', $user_id);
            $second_builder->where_raw(
                '(p.access = :shared_access_restricted OR p.access = :shared_access_public)',
                [
                    'shared_access_restricted' => access::RESTRICTED,
                    'shared_access_public' => access::PUBLIC
                ]
            );

            $second_builder->select('p.*');
            $second_builder->add_select_raw($user_fields_sql);
            $second_builder->add_select([
                'u.id AS user_id',
                'u.email AS user_email',
                'u.imagealt AS user_imagealt',
                'u.picture AS user_picture'
            ]);
            $first_builder->union($second_builder);
        }

        return $first_builder;
    }

    /**
     * @param playlist_query $query
     * @return cursor_paginator
     *
     * @return void
     */
    public static function get_playlists(playlist_query $query): cursor_paginator {
        global $CFG, $DB;
        $base_builder = static::base_builder($query);

        $builder = builder::table($base_builder, 'p');
        $builder->select_raw('DISTINCT p.*');
        $builder->results_as_arrays();
        $builder->map_to([static::class, 'create_playlist']);

        $user_id = $query->get_user_id();
        $source = $query->get_source();

        if (null !== $source) {
            // If source is not provided, we will include everything, however, if it is, we start filtering them out.
            if (playlist_source::is_bookmarked($source)) {
                $builder->join(
                    [engage_bookmark::TABLE, 'eb'],
                    function (builder $join): void {
                        $join->where_field('eb.itemid', 'p.id');
                        $join->where('eb.component', 'totara_playlist');
                    }
                );

                // Joining the bookmark table, to load all the bookmark record of playlist.
                $builder->where('eb.userid', $user_id);
            } else if (playlist_source::is_own($source)) {
                $builder->where('p.userid', $user_id);
            }
        }

        $resource_id = $query->get_resource_id();
        if (null !== $resource_id && 0 !== $resource_id) {
            // Searching for playlists that contains this specific resources.
            $builder->join([playlist_resource_entity::TABLE, 'pr'], 'p.id', 'pr.playlistid');
            $builder->where('pr.resourceid', $resource_id);
        }

        $access = $query->get_access();
        if (null !== $access) {
            $builder->where('p.access', $access);
        }

        if (!empty($CFG->tenantsenabled)) {
            // Multi-tenancy is enabled. We will have to join with the user's tenant cohort table
            // to only included the playlists that this user is able to see.
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);
            if (null !== $tenant_id) {
                // This user is within a tenant. So we will have our join query easy.
                $builder->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('p.userid', 'u.id');
                        $join->where('u.suspended', 0);
                        $join->where('u.deleted', 0);

                        if (!empty($CFG->tenantsisolated)) {
                            // Isolation mode is on, therefore we will have to only fetch based on tenant's id.
                            $join->where('u.tenantid', $tenant_id);
                        } else {
                            // Isolation mode is off.
                            $join->where_raw(
                                '(u.tenantid = :tenant_id OR u.tenantid IS NULL)',
                                ['tenant_id' => $tenant_id]
                            );
                        }
                    }
                );

                // The sql will be looking something like below
                // -------------------------- SQL --------------------------
                // SELECT DISTINCT p.*
                // FROM (
                //  SELECT p.*,
                //      u.firstnamephonetic AS user_firstnamephonetic,
                //      u.lastnamephonetic  AS user_lastnamephonetic,
                //      u.middlename        AS user_middlename,
                //      u.alternatename     AS user_alternatename,
                //      u.firstname         AS user_firstname,
                //      u.lastname          AS user_lastname,
                //      u.id                as user_id,
                //      u.email             as user_email,
                //      u.imagealt          as user_imagealt,
                //      u.picture           as user_picture
                //  FROM phpu_00playlist "p"
                //  INNER JOIN phpu_00user "u" ON p.userid = u.id
                //  AND u.deleted = 0
                //  AND u.suspended = 0
                //  AND u.confirmed = 1
                //  WHERE (p.userid = $1 OR p.access = $2)
                //
                //  UNION
                //
                //  # Union with all the playlists that are shared to this user.
                //  SELECT p.*,
                //  u.firstnamephonetic AS user_firstnamephonetic,
                //  u.lastnamephonetic  AS user_lastnamephonetic,
                //  u.middlename        AS user_middlename,
                //  u.alternatename     AS user_alternatename,
                //  u.firstname         AS user_firstname,
                //  u.lastname          AS user_lastname,
                //  u.id                as user_id,
                //  u.email             as user_email,
                //  u.imagealt          as user_imagealt,
                //  u.picture           as user_picture
                //  FROM phpu_00playlist "p"
                //  INNER JOIN phpu_00user "u" ON p.userid = u.id
                //  AND u.deleted = 0
                //  AND u.suspended = 0
                //  AND u.confirmed = 1
                //  INNER JOIN phpu_00engage_share "es" ON es.itemid = p.id AND es.component = $3
                //  INNER JOIN phpu_00engage_share_recipient "esr"
                //      ON esr.shareid = es.id AND "esr".component = $4 AND "esr".area = $5
                //  WHERE esr.instanceid = $6
                //      AND (p.access = $7 OR p.access = $8)
                //  ) "p"
                //
                //  # This is where multi-tenancy is enabled, which we have to fetch only
                //  # the playlist that is available to this user within his/her tenant.
                //
                //  INNER JOIN phpu_00user "u" ON p.userid = u.id
                //  AND u.suspended = 0 AND u.deleted = 0
                //  AND (u.tenant_id = $9 OR u.tenant_id IS NULL)
                //  WHERE 1 = 1
                //  LIMIT 50 OFFSET 0
                // -------------------------- END OF SQL --------------------------
            } else {
                // We need to find out if the user is at least a participant to any of the tenants.
                // If not then the query can be much simpler.
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->select('t.id AS tenant_id');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->where('cm.userid', $user_id);

                $collection = $tenant_builder->get();
                $tenant_ids = $collection->pluck('tenant_id');

                if (!empty($tenant_ids)) {
                    [$in_sql, $parameters] = $DB->sql_in($tenant_ids);
                    $builder->join(
                        ['user', 'u'],
                        function (builder $join) use ($in_sql, $parameters): void {
                            $join->where_field('p.userid', 'u.id');
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
                            $join->where_Field('p.userid', 'u.id');

                            // Only look for non tenant stuff.
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                // In either cases, we will have to exclude records by users that are deleted/suspended.
                $builder->where('u.deleted', 0);
                $builder->where('u.suspended', 0);
            }
        }

        $sort = $query->get_sort();
        if (null !== $sort) {
            if (playlist_sort::is_rating($sort)) {
                // Sort order by rating. This is put as a sub-query because wea re
                $sub_query = builder::table(engage_rating_entity::TABLE, 'er');
                $sub_query->select_raw("er.instanceid AS id, AVG(er.rating) AS rating");

                $sub_query->where('er.component', 'totara_playlist');
                $sub_query->where('er.area', playlist::RATING_AREA);
                $sub_query->group_by('er.instanceid');

                $table = new table($sub_query);
                $table->as('r');

                $builder->left_join($table, 'p.id', 'r.id');

                $builder->add_select('r.rating AS playlist_rating');
                $builder->order_by('r.rating', order::DIRECTION_DESC);
            }
        }

        $cursor = $query->get_cursor();
        return new cursor_paginator($builder, $cursor);
    }

    /**
     * Loader factory method to create a playlist. Note that this function should not be used
     * elsewhere outside of this class.
     *
     * @param array $record
     * @return playlist
     *
     * @internal
     */
    public static function create_playlist(array $record): playlist {
        $user_fields = get_all_user_name_fields(false, 'u', 'user_');
        $user_fields['id'] = 'user_id';
        $user_fields['email'] = 'user_email';
        $user_fields['imagealt'] = 'user_imagealt';
        $user_fields['picture'] = 'user_picture';

        $user = [];
        foreach ($user_fields as $user_field => $sql_field) {
            if (!array_key_exists($sql_field, $record)) {
                debugging("No field '{$sql_field}' found from sql result", DEBUG_DEVELOPER);
                continue;
            }

            $user[$user_field] = $record[$sql_field];
            unset($record[$sql_field]);
        }

        if (array_key_exists('playlist_rating', $record)) {
            // Removing the playlist rating so that the entity will not complaining about non
            // column fields.
            unset($record['playlist_rating']);
        }

        $entity = new playlist_entity($record);
        return playlist::from_entity($entity, false, (object) $user);
    }
}