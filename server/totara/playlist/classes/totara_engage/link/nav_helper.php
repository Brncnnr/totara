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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_article
 */

namespace totara_playlist\totara_engage\link;

use core\orm\query\builder;
use stdClass;
use totara_engage\access\access_manager;
use totara_engage\entity\engage_resource;
use totara_playlist\entity\playlist_resource;

/**
 * Class nav_helper
 *
 * @package totara_playlist\totara_engage\link
 */
final class nav_helper {
    /**
     * For the provided resource & playlist, return the next/previous links + info to render the page
     *
     * @param int $playlist_id
     * @param int $resource_id
     * @return array|null[]
     */
    public static function get_resource_link_info(int $playlist_id, int $resource_id): array {
        $base_query = builder::table(playlist_resource::TABLE, 'pr')
            ->join([engage_resource::TABLE, 'er'], 'er.id', '=', 'pr.resourceid')
            ->where('pr.playlistid', $playlist_id)
            ->select(['er.id', 'er.resourcetype', 'pr.sortorder'])
            ->order_by('pr.sortorder');

        // We must filter out any resources that fall foul of tenancy rules
        self::filter_multi_tenancy($base_query);

        $records = $base_query->fetch(true);
        $count = count($records);

        $previous_record = null;
        $current_index = null;

        // Loop until we find our current record
        foreach ($records as $i => $record) {
            if ($record->id != $resource_id) {
                $previous_record = $record;
                continue;
            }
            // Found the current record, therefore the previous record is known
            $current_index = $i;
            break;
        }

        // Grab the next record (if it exists)
        $next_record = $records[$current_index + 1] ?? null;

        // Build our links up
        $next_link = null;
        $previous_link = null;

        // Build our links
        if ($previous_record) {
            $previous_link = \totara_engage\link\builder::to($previous_record->resourcetype, ['id' => $previous_record->id])
                ->from('totara_playlist', ['id' => $playlist_id]);
        }
        if ($next_record) {
            $next_link = \totara_engage\link\builder::to($next_record->resourcetype, ['id' => $next_record->id])
                ->from('totara_playlist', ['id' => $playlist_id]);
        }

        $info = new stdClass();
        $info->current = $current_index + 1; // 0 based
        $info->total = $count;

        return [
            'previous' => $previous_link ? $previous_link->out() : null,
            'next' => $next_link ? $next_link->out() : null,
            'label' => get_string('resourceplaylistposition', 'totara_playlist', $info),
        ];
    }

    /**
     * Helper to generate the forward/back buttons for any playlist-style resources.
     *
     * @param int $resource_id
     * @param int $owner_id
     * @param string|null $source
     * @return array
     */
    public static function build_resource_nav_buttons(int $resource_id, int $owner_id, ?string $source): array {
        $back_button = self::build_back_button($owner_id, $source);
        $navigation_buttons = null;

        if ($source) {
            $destination = \totara_engage\link\builder::from_source($source);
            if ($destination instanceof playlist_destination) {
                $navigation_buttons = self::get_resource_link_info(
                    $destination->get_attribute('id'),
                    $resource_id
                );
            }
        }

        return [$back_button, $navigation_buttons];
    }

    /**
     * @param int $owner_id
     * @param string|null $source
     * @return array|null
     */
    public static function build_back_button(int $owner_id, ?string $source): ?array {
        global $USER;
        $back_button = null;

        if ($source) {
            $destination = \totara_engage\link\builder::from_source($source);
            $back_button = $destination->back_button_attributes();
        }
        if (!$back_button) {
            // Default to the user_library back button
            $library = \totara_engage\link\builder::to_library();
            $library->page_your_resources();

            if ($owner_id != $USER->id) {
                $library->page_owners_resources($owner_id);
            }

            $back_button = $library->back_button_attributes();
        }

        return $back_button;
    }

    /**
     * Apply any multi-tenancy rules. Returns true if multi-tenancy is enabled.
     *
     * @param builder $builder
     */
    private static function filter_multi_tenancy(builder $builder): void {
        global $USER, $CFG, $DB;
        $user_id = $USER->id;

        if (!empty($CFG->tenantsenabled) && !access_manager::can_manage_tenant_participants($user_id)) {
            // Multi-tenancy is on, and user is not a site admin one.
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);

            if (null !== $tenant_id) {
                $builder->join(
                    ['user', 'u'],
                    function(builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field('er.userid', 'u.id');
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
                    $builder->join(
                        ['user', 'u'],
                        function(builder $join) use ($in_sql, $parameters): void {
                            $join->where_field('er.userid', 'u.id');
                            $join->where_raw("(u.id {$in_sql} OR u.tenantid IS NULL)", $parameters);
                        }
                    );
                } else {
                    $builder->join(['user', 'u'], 'er.userid', 'u.id');
                    $builder->where_null('u.tenantid');
                }

                // In both cases, we will have to only include those records from users that
                // are not suspended or deleted from the system.
                $builder->where('u.suspended', 0);
                $builder->where('u.deleted', 0);
            }
        }
    }
}