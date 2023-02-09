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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use core_user\access_controller;
use core_user\totara_engage\share\recipient\user;
use totara_core\advanced_feature;
use totara_engage\entity\share;
use totara_engage\entity\share_recipient;
use totara_engage\share\share as share_model;

final class share_repository extends repository {

    /**
     * @param int $itemid
     * @param string $component
     * @return share|null
     */
    public function get_share(int $itemid, string $component): ?share {
        $builder = builder::table(share::TABLE)
            ->map_to(share::class)
            ->where('itemid', $itemid)
            ->where('component', $component);

        /** @var share $entity */
        $entity = $builder->one();
        return $entity;
    }

    /**
     * Get all shares for item.
     *
     * @return array|null
     */
    public function get_all_shares(): ?array {
        $builder = builder::table(share::TABLE);
        return $builder->fetch();
    }

    /**
     * Get the total number of sharers of this item.
     *
     * @param int $itemid
     * @param string $component
     * @param int|null $visibility
     * @return int
     */
    public function get_total_sharers(int $itemid, string $component, ?int $visibility = share_model::VISIBILITY_VISIBLE): int {
        $builder = builder::table(share::TABLE, 's')
            ->join([share_recipient::TABLE, 'sr'], function (builder $joining) {
                $joining
                    ->where_raw('sr.shareid = s.id')
                    ->where_raw('sr.sharerid != s.ownerid');
            })
            ->select('sr.sharerid')
            ->where('s.itemid', $itemid)
            ->where('s.component', $component)
            ->where('sr.visibility', $visibility)
            ->group_by('sr.sharerid');

        return $builder->count();
    }

    /**
     * Get the total number of recipients for this item.
     *
     * @param int $itemid
     * @param string $component
     * @param int|null $visibility
     * @return int
     */
    public function get_total_recipients(int $itemid, string $component, ?int $visibility = share_model::VISIBILITY_VISIBLE): int {
        $share = $this->get_share($itemid, $component);
        return $share ? $share->recipients($visibility)->count() : 0;
    }

    /**
     * Return the total number of recipients per recipient area.
     *
     * @param int $itemid
     * @param string $component
     * @param int|null $visibility
     * @return array
     */
    public function get_total_recipients_per_area(
        int $itemid,
        string $component,
        ?int $visibility = share_model::VISIBILITY_VISIBLE
    ): array {
        $builder = builder::table(share_recipient::TABLE, 'sr')
            ->join([share::TABLE, 's'], 'shareid', '=', 'id')
            ->select_raw('sr.area, count(*) as total')
            ->where('s.itemid', $itemid)
            ->where('s.component', $component)
            ->where('sr.visibility', $visibility)
            ->group_by('sr.area');

        return $builder->fetch();
    }

    /**
     * Get all recipients for specific share.
     *
     * @param int $itemid
     * @param string $component
     * @return array
     */
    public function get_recipients(int $itemid, string $component): array {
        $share = $this->get_share($itemid, $component);
        $recipients = $share ? $share->recipients()->get()->to_array() : [];

        // As unlinked item can be reshared with recipients, we need to filter the recipients.
        if (!empty($recipients)) {
            $recipients = array_filter($recipients, function ($recipient) {
                return $recipient['visibility'] == 1;
            });
        }

        // If workspaces aren't enabled, filter out any previous workspace shares
        if ($recipients && advanced_feature::is_disabled('container_workspace')) {
            $recipients = array_values(array_filter($recipients, function (array $share) {
                return $share['component'] !== 'container_workspace';
            }));
        }

        if (!empty($recipients)) {
            $recipients = array_filter(
                $recipients,
                function ($recipient) {
                    global $DB;

                    if ('core_user' !== $recipient['component'] && $recipient['area'] !== 'user') {
                        return true;
                    }

                    $access_controller = access_controller::for_user_id($recipient['instanceid']);
                    if (!$access_controller->can_view_profile()) {
                        return false;
                    }

                    $sql = '
                        SELECT 1 FROM "ttr_user" 
                        WHERE id = :user_id 
                        AND (deleted = 0 AND confirmed = 1)
                    ';

                    return $DB->record_exists_sql($sql, ['user_id' => $recipient['instanceid']]);
                }
            );
        }

        return $recipients;
    }

    /**
     * Check if a specific recipient exists in the recipients for a share.
     * @param int $itemid
     * @param string $component
     * @param int $recipient_instance_id
     * @param string $recipient_area
     * @param string $recipient_component
     * @param int|null $unused
     * @return bool
     */
    public function is_recipient(int $itemid, string $component, int $recipient_instance_id,
        string $recipient_area, string $recipient_component, ?int $unused = null
    ): bool {
        if ($unused !== null) {
            debugging('The is_recipient() six argument is no longer used, please review your code', DEBUG_DEVELOPER);
        }

        $builder = builder::table(share_recipient::TABLE, 'sr')
            ->join([share::TABLE, 's'], 'shareid', '=', 'id')
            ->where('s.itemid', $itemid)
            ->where('s.component', $component)
            ->where('sr.instanceid', $recipient_instance_id)
            ->where('sr.area', $recipient_area)
            ->where('sr.component', $recipient_component);

        return $builder->exists();
    }

    /**
     * @param int $id
     */
    public function delete_share_by_id(int $id): void {
        builder::table(share::TABLE)
            ->where('id', $id)
            ->delete();
    }

    /**
     * @param int $recipient_id
     * @param string $component
     * @param string $area
     * @return array
     */
    public function get_shares_by_recipient(int $recipient_id, string $component, string $area): array {
        return builder::table(share::TABLE, 's')
            ->join([share_recipient::TABLE, 'sr'], 'id', '=', 'shareid')
            ->map_to(share::class)
            ->where('sr.instanceid', $recipient_id)
            ->where('sr.area', $area)
            ->where('sr.component', $component)
            ->fetch();
    }
}
