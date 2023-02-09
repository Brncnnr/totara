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
 * @package totara_engage
 */
namespace totara_engage\card;

use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\table;
use totara_engage\access\access;
use totara_engage\access\access_manager;
use totara_engage\entity\share;
use totara_engage\entity\share_recipient;
use totara_engage\query\option\section;
use totara_engage\query\provider\helper;
use totara_engage\query\provider\queryable;
use totara_engage\query\query;
use totara_engage\share\recipient\recipient;
use totara_engage\share\share as share_model;

class card_loader {

    /** @var query $query */
    protected $query;

    /**
     * card_loader constructor.
     * @param query $query
     */
    public function __construct(query $query) {
        $this->query = $query;
    }

    /**
     * @return offset_cursor_paginator
     */
    public function fetch(): offset_cursor_paginator {
        global $CFG, $DB;
        $builders = helper::get_builders($this->query);

        /** @var builder $base */
        $base = null;
        foreach ($builders as $builder) {
            if (null === $base) {
                $base = $builder;
            } else {
                $base->union_all($builder);
            }
        }

        $master = builder::table($base, 'master');
        $master->select('master.*');
        $master->results_as_arrays();

        $user_id = $this->query->get_userid();

        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($user_id)) {
            // Multi-tenancy is on, and user is not a site admin one.
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);

            if (null !== $tenant_id) {
                $master->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('master.userid', 'u.id');
                        $join->where('u.suspended', 0);
                        $join->where('u.deleted', 0);

                        if (!empty($CFG->tenantsisolated)) {
                            // Isolation mode is on, hence we are skipping those users that belong
                            // to the system level.
                            $join->where('u.tenantid', $tenant_id);
                        } else {
                            $join->where_raw(
                                '(u.tenantid = :tenant_id OR u.tenantid IS NULL)',
                                ['tenant_id' => $tenant_id]
                            );
                        }
                    }
                );
            } else {
                // User is participant. We will have to find out all the tenants that this user is participant of.
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->select('t.id AS tenant_id');
                $tenant_builder->where('cm.userid', $user_id);

                $collection = $tenant_builder->get();
                $tenant_ids = $collection->pluck('tenant_id');

                if (!empty($tenant_ids)) {
                    [$in_sql, $parameters] = $DB->sql_in($tenant_ids);
                    $master->join(
                        ['user', 'u'],
                        function (builder $join) use ($in_sql, $parameters): void {
                            $join->where_field('master.userid', 'u.id');
                            $join->where_raw("(u.tenantid {$in_sql} OR u.tenantid IS NULL)", $parameters);
                        }
                    );
                } else {
                    $master->join(['user', 'u'], 'master.userid', 'u.id');
                    $master->when(
                        (!empty($CFG->tenantsisolated)),
                        function (builder $inner_builder): void {
                            $inner_builder->where_null('u.tenantid');
                        }
                    );
                }

                // In both cases, we will have to only include those records from users that
                // are not suspended or deleted from the system.
                $master->where('u.suspended', 0);
                $master->where('u.deleted', 0);
            }
        }

        // Sort the results.
        $sort = $this->query->get_sort();
        if ($sort) {
            $column = $this->query->get_sort_column($sort);
            $master->order_by_raw("master.{$column}");
        }

        $master->map_to(
            function (array $record) {
                $component = $record['component'];
                return card_resolver::create_card($component, $record);
            }
        );

        return new offset_cursor_paginator($master, $this->query->get_cursor());
    }

    /**
     * Link recipient to the items shared with it.
     *
     * @param recipient $recipient
     * @return offset_cursor_paginator
     */
    public function fetch_shared(recipient $recipient): offset_cursor_paginator {
        // For this query to fetch all resources we need to set allsite on, otherwise we will
        // not get other users' public resources and playlists shared with this workspace.
        $this->query->set_section(section::ALLSITE);

        // Include restricted resources that might have been shared to the workspace.
        $this->query->set_restricted(true);

        $builder = $this->get_share_builder($recipient);
        $items_table = $this->get_items_table();

        $builder->join($items_table, function(builder $joining) {
            $joining->where_raw('items_table.instanceid = s.itemid')
                ->where_raw('items_table.component = s.component');
        });

        $builder->select([
            'items_table.*',
            'sr.timecreated AS dateshared'
        ]);

        return new offset_cursor_paginator($builder, $this->query->get_cursor());
    }

    /**
     * Link recipient to the items not yet shared with it.
     *
     * @param recipient $recipient
     * @return offset_cursor_paginator
     */
    public function fetch_not_shared(recipient $recipient): offset_cursor_paginator {
        $builder = $this->get_share_builder($recipient);
        $items_table = $this->get_items_table();

        $builder->right_join($items_table, function(builder $joining) {
            $joining->where_raw('items_table.instanceid = s.itemid')
                ->where_raw('items_table.component = s.component');
        });

        $builder->select([
            'items_table.*'
        ]);

        $builder->where_null('s.id');
        $builder->where_not_in('items_table.component', $this->query->get_exclude_component());

        return new offset_cursor_paginator($builder, $this->query->get_cursor());
    }

    /**
     * @param recipient $recipient
     * @return builder
     */
    protected function get_share_builder(recipient $recipient): builder {
        $builder = builder::table(share::TABLE, 's')
            ->join([share_recipient::TABLE, 'sr'], function(builder $joining) use($recipient) {
                $joining->where_raw('sr.shareid = s.id')
                    ->where('sr.instanceid', $recipient->get_id())
                    ->where('sr.area', $recipient->get_area())
                    ->where('sr.component', $recipient->get_component())
                    ->where('sr.visibility', share_model::VISIBILITY_VISIBLE);
            });

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

        return $builder;
    }

    /**
     * @return builder|null
     */
    protected function get_items_builder(): ?builder {
        $builder = null;

        $providers = $this->get_queryable_providers();
        foreach ($providers as $provider) {
            $provider = new $provider();

            $resource_builder = $provider->get_linked_builder($this->query, false);
            if (!empty($resource_builder)) {
                $linked_builder = $resource_builder->get_builder();
                $correlation_id = $resource_builder->get_correlation_id();

                // If this includes your library we need to make sure that the "shared with you"
                // or "saved resources" resources are at least public otherwise the users will
                // not be able to share them with the workspace as only the owner is allowed to
                // share non-public resources and playlists.
                if ($this->query->include_entire_library() || section::is_sharedwithyou($this->query->get_section())) {
                    $linked_builder->where(function (builder $builder) use ($correlation_id) {
                        $builder->where_null('esr.id')
                            ->or_where_not_null('esr.id');
                        if ($this->query->include_restricted()) {
                            $builder->where_in("{$correlation_id}.access", [
                                access::PUBLIC,
                                access::RESTRICTED
                            ]);
                        } else {
                            $builder->where("{$correlation_id}.access", access::PUBLIC);
                        }
                    });
                }

                if ($this->query->include_entire_library() || section::is_savedresources($this->query->get_section())) {
                    $linked_builder->where(function (builder $builder) use ($correlation_id) {
                        $builder->where_null('eb.id')
                            ->or_where_not_null('eb.id');
                        if ($this->query->include_restricted()) {
                            $builder->where_in("{$correlation_id}.access", [
                                access::PUBLIC,
                                access::RESTRICTED
                            ]);
                        } else {
                            $builder->where("{$correlation_id}.access", access::PUBLIC);
                        }
                    });
                }

                if (empty($builder)) {
                    $builder = $linked_builder;
                } else {
                    $builder->union_all($linked_builder);
                }
            }
        }

        // Sub-query to select all the items.
        return builder::table($builder, 'items');
    }

    /**
     * @return table
     */
    protected function get_items_table(): table {
        global $CFG, $USER, $DB;

        $items = $this->get_items_builder();
        $items->select(['items.*']);

        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($USER->id)) {
            // Only happening if the multi tenancy is enabled and user is not a site admin.
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $USER->id]);

            if (null !== $tenant_id) {
                $items->join(
                    ['user', 'u'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('items.userid', 'u.id');
                        $join->where('u.deleted', 0);
                        $join->where('u.suspended', 0);

                        if (empty($CFG->tenantsisolated)) {
                            // Isolation mode is off.
                            $join->where_raw(
                                "(u.tenantid = :tenant_id OR u.tenantid IS NULL)",
                                ['tenant_id' => $tenant_id]
                            );

                            return;
                        }

                        $join->where('u.tenantid', $tenant_id);
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
                    $items->join(
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
                    // Not a participant
                    $items->join(
                        ['user', 'u'],
                        function (builder $join): void {
                            $join->where_field('items.userid', 'u.id');
                            $join->where_null('u.tenantid');
                        }
                    );
                }

                $items->where('u.deleted', 0);
                $items->where('u.suspended', 0);
            }
        }

        $items_table = new table($items);
        $items_table->as('items_table');

        return $items_table;
    }

    /**
     * @return queryable[]
     */
    protected function get_queryable_providers(): array {
        return helper::get_providers();
    }
}