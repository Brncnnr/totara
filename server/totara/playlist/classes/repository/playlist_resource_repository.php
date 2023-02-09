<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
namespace totara_playlist\repository;

use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\order;
use totara_engage\access\access;
use totara_engage\access\access_manager;
use totara_engage\entity\engage_resource;
use totara_playlist\entity\playlist_resource;

final class playlist_resource_repository extends repository {
    /**
     * @param int $playlistid
     * @return playlist_resource[]
     */
    public function get_all_for_playlist(int $playlistid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(playlist_resource::class);

        $builder->where('playlistid', $playlistid);
        $builder->order_by('sortorder', order::DIRECTION_DESC);
        return $builder->fetch();
    }

    /**
     * @param int $playlistid
     * @param int|null $user_id
     * @return int
     */
    public function get_total_of_resources(int $playlistid, ?int $user_id = null): int {
        global $USER, $DB, $CFG;

        $builder = builder::table(static::get_table(), 'pr');
        $builder->where('pr.playlistid', $playlistid);
        $builder->join(['playlist', 'p'], 'p.id', 'pr.playlistid');

        if (null === $user_id) {
            $user_id = $USER->id;
        }

        // We need to filter on multi-tenancy as resources could cross lines
        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($user_id)) {
            // Join on the engage_resources table as we have to filter against the owner of the resource
            $builder->join([engage_resource::TABLE, 'er'], 'er.id', 'pr.resourceid');

            // Multi tenancy is on, and user is not a site admin.
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id]);
            if (null !== $tenant_id) {
                // User is a part of any tenant.
                $builder->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('er.userid', 'u.id');
                        $join->where('u.deleted', 0);
                        $join->where('u.suspended', 0);

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
            } else {
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
                            $join->where_field('p.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                // In either cases, we need to exclude deleted/suspended users.
                $builder->where('u.deleted', 0);
                $builder->where('u.suspended', 0);
            }
        }

        return $builder->count();
    }

    /**
     * @param int $resourceid
     * @param int $playlistid
     * @return playlist_resource|null
     */
    public function find_resource(int $resourceid, int $playlistid): ?playlist_resource {
        $builder = builder::table(static::get_table());
        $builder->map_to(playlist_resource::class);

        $builder->where('resourceid', $resourceid);
        $builder->where('playlistid', $playlistid);

        /** @var playlist_resource|null $entity */
        $entity = $builder->one();
        return $entity;
    }

    /**
     * @param entity|playlist_resource $entity
     * @return entity|playlist_resource
     */
    public function save_entity(entity $entity): entity {
        $sortorder = $entity->sortorder;

        if (null == $sortorder) {
            $builder = builder::table(static::get_table());
            $builder->select_raw('MAX(sortorder) AS sortorder');
            $builder->where('playlistid', $entity->playlistid);

            $record = $builder->one();

            if (null == $record || null == $record->sortorder) {
                $entity->sortorder = 1;
            } else {
                $entity->sortorder = $record->sortorder + 1;
            }
        }

        return parent::save_entity($entity);
    }

    /**
     * @param int $resourceid
     * @return void
     */
    public function delete_resource_by_resourceid(int $resourceid): void {
        $builder = builder::table(static::get_table());
        $builder->map_to(playlist_resource::class);
        $builder->where('resourceid', $resourceid);

        /** @var playlist_resource|null $entity */
        $entity = $builder->one();

        // Resouce is not in the playlist.
        if (empty($entity)) {
            return;
        }

        $entity->delete();
    }

    /**
     * @param int $playlist_id
     */
    public function delete_resources_by_playlistid(int $playlist_id): void {
        builder::table(static::get_table())
            ->where('playlistid', $playlist_id)
            ->delete();
    }

    /**
     * @param int $sort_order
     * @param int $playlist_id
     * @return playlist_resource|null
     */
    public function find_resource_by_sortorder(int $sort_order, int $playlist_id): ?playlist_resource {
        $builder = builder::table(static::get_table());
        $builder->map_to(playlist_resource::class);

        $builder->where('sortorder', $sort_order);
        $builder->where('playlistid', $playlist_id);

        /** @var playlist_resource|null $entity */
        $entity = $builder->one();
        return $entity;
    }

    /**
     * @param int $playlist_id
     * @param int $resource_id
     */
    public function remove_resource(int $playlist_id, int $resource_id): void {
        builder::table(static::get_table())
            ->where('resourceid', $resource_id)
            ->where('playlistid', $playlist_id)
            ->delete();
    }

    /**
     * Checking whether the given playlist has any non public resources or not.
     *
     * @param int $playlist_id
     * @return bool
     */
    public function has_non_public_resources(int $playlist_id): bool {
        $builder = builder::table(static::get_table(), 'pr');
        $builder->join(['engage_resource', 'er'], 'pr.resourceid', 'er.id');

        $builder->where('pr.playlistid', $playlist_id);
        $builder->where('er.access', '<>', access::PUBLIC);

        return $builder->exists();
    }
}