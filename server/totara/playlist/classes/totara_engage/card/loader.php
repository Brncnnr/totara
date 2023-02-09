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
namespace totara_playlist\totara_engage\card;

use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\order;
use totara_engage\access\access_manager;
use totara_engage\card\card_loader;
use totara_engage\card\card_resolver;
use totara_engage\entity\engage_resource;
use totara_engage\query\provider\helper;
use totara_playlist\entity\playlist;
use totara_playlist\entity\playlist_resource;
use totara_playlist\totara_engage\query\provider\playlist_provider;

class loader extends card_loader {
    /** @var int */
    private const MAX_SIGNED_VALUE = 2147483647;

    /** @var int */
    private $playlist_id;

    /**
     * @param int $playlist_id
     */
    public function set_playlist_id(int $playlist_id): void {
        $this->playlist_id = $playlist_id;
    }

    /**
     * @return offset_cursor_paginator
     */
    public function fetch(): offset_cursor_paginator {
        global $CFG, $DB;

        $builder = builder::table(playlist_resource::TABLE, 'pr');
        $builder->join([engage_resource::TABLE, 'er'], 'pr.resourceid', 'er.id');

        $builder->where('pr.playlistid', $this->playlist_id);
        $builder->select(
            [
                'er.id AS instanceid',
                'er.name AS name',
                'er.resourcetype AS component',
                'er.userid AS userid',
                'er.access AS access',
                'er.extra AS extra',
                'er.timecreated AS timecreated',
                'er.timemodified AS timemodified',
                'pr.sortorder',
            ]
        );

        $user_id = $this->query->get_userid();
        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($user_id)) {
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
                            $join->where_field('er.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                // In either cases, we need to exclude deleted/suspended users.
                $builder->where('u.deleted', 0);
                $builder->where('u.suspended', 0);
            }
        }

        $builder->order_by('pr.sortorder', order::DIRECTION_DESC);
        $builder->results_as_arrays();
        $builder->map_to(
            function (array $row) {
                $component = $row['component'];
                return card_resolver::create_card($component, $row);
            }
        );

        // Temporary solution to load all the cards, it will be removed when loading more component can work with drag
        // and drop component.

        $cursor = $this->query->get_cursor();
        $cursor->set_limit(static::MAX_SIGNED_VALUE);

        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Get resources not yet added to playlist.
     *
     * @return offset_cursor_paginator
     */
    public function fetch_not_added(): offset_cursor_paginator {
        global $CFG, $DB;

        $builder = $this->get_items_builder();
        $builder->where_not_exists(
            builder::table(playlist::TABLE, 'p')
            ->join([playlist_resource::TABLE, 'pr'], 'pr.playlistid', 'p.id')
            ->where('p.id', $this->playlist_id)
            ->where_raw('pr.resourceid = items.instanceid')
        );

        $builder->where_not_in('items.component', $this->query->get_exclude_component());

        // Sort the results.
        $sort = $this->query->get_sort();
        if ($sort) {
            $column = $this->query->get_sort_column($sort);
            $builder->order_by_raw($column);
        }

        $builder->results_as_arrays();
        $builder->map_to(
            function (array $row) {
                $component = $row['component'];
                return card_resolver::create_card($component, $row);
            }
        );

        $user_id = $this->query->get_userid();
        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($user_id)) {
            // Multi tenancy is on, and user is not a site admin
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);

            if (null !== $tenant_id) {
                // User is living within tenant.
                $builder->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('items.userid', 'u.id');
                        $join->where('u.deleted', 0);
                        $join->where('u.suspended', 0);

                        if (empty($CFG->tenantsisolated)) {
                            $join->where_raw(
                                "(u.tenantid = :tenant_id OR u.tenantid IS NULL)",
                                ['tenant_id' => $tenant_id]
                            );
                        } else {
                            $join->where('u.tenantid', $tenant_id);
                        }
                    }
                );
            } else {
                // User can be a participant.
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
                            $join->where_field('items.userid', 'u.id');
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
                            $join->where_field('items.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                $builder->where('u.deleted', 0);
                $builder->where('u.suspended', 0);
            }
        }

        return new offset_cursor_paginator($builder, $this->query->get_cursor());
    }

    /**
     * @inheritDoc
     */
    protected function get_queryable_providers(): array {
        $providers = helper::get_providers();

        // Remove playlist provider.
        $providers = array_filter($providers, function($provider) {
            return $provider !== playlist_provider::class;
        });

        return $providers;
    }
}