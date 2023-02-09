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
use totara_engage\entity\share_recipient;
use totara_engage\share\share as share_model;

final class share_recipient_repository extends repository {

    /**
     * Return the total number of recipients. This will split the count based on each
     * recipient area.
     *
     * @param int $shareid
     * @return array
     */
    public function get_total_recipients_per_area(int $shareid): array {
        $builder = builder::table(share_recipient::TABLE)
            ->select_raw('area, count(*) as total')
            ->where('shareid', $shareid)
            ->where('visibility', share_model::VISIBILITY_VISIBLE)
            ->group_by('area');

        return $builder->fetch();
    }

    /**
     * @param int $shareid
     * @param int $instanceid
     * @param string $area
     * @param string $component
     * @param int|null $visibility
     * @return share_recipient|null
     */
    public function get_recipient_by_visibility(
        int $shareid, int $instanceid, string $area, string $component, ?int $visibility = share_model::VISIBILITY_VISIBLE
    ): ?share_recipient {
        $builder = builder::table(share_recipient::TABLE)
            ->map_to(share_recipient::class)
            ->where('shareid', $shareid)
            ->where('instanceid', $instanceid)
            ->where('area', $area)
            ->where('component', $component)
            ->where('visibility', $visibility);

        /** @var share_recipient $entity */
        $entity = $builder->one();
        return $entity;
    }

    /**
     * Get all users with whom the item was shared.
     *
     * @param int $shareid
     * @param int|null $visibility
     * @return array
     */
    public function get_recipients(int $shareid, ?int $visibility = share_model::VISIBILITY_VISIBLE): array {
        $builder = builder::table(static::get_table())
            ->map_to(share_recipient::class)
            ->where('shareid', $shareid)
            ->where('visibility', $visibility);

        return $builder->fetch();
    }

    /**
     * Get all recipients of share1 that do not exist in share2.
     *
     * @param int $share1_id
     * @param int $share2_id
     * @return share_recipient[]
     */
    public function get_difference(int $share1_id, int $share2_id): array {
        $s2_builder = builder::table(share_recipient::TABLE, 's2')
            ->where('s2.shareid', $share2_id)
            ->where_raw('s2.instanceid = s1.instanceid')
            ->where_raw('s2.area = s1.area')
            ->where_raw('s2.component = s1.component');

        $s1_builder = builder::table(share_recipient::TABLE, 's1')
            ->map_to(share_recipient::class)
            ->where('s1.shareid', $share1_id)
            ->where_not_exists($s2_builder);

        return $s1_builder->fetch();
    }

    /**
     * @param int $shareid
     */
    public function delete_recipient_by_shareid(int $shareid): void {
        builder::table(share_recipient::TABLE)
            ->where('shareid', $shareid)
            ->delete();
    }

    /**
     * @param int $recipient_id
     * @return share_recipient
     */
    public function get_recipient_by_id(int $recipient_id): share_recipient {
        $entity = builder::table(share_recipient::TABLE)
            ->map_to(share_recipient::class)
            ->where('id', $recipient_id)
            ->one();

        /** @var share_recipient $entity */
        return $entity;
    }

    /**
     * @param string $component
     * @param string $area
     * @param int $instance_id
     *
     * @return void
     */
    public function delete_recipients_by_identifier(string $component, string $area, int $instance_id): void {
        $builder = builder::table(share_recipient::TABLE);

        $builder->where('instanceid', $instance_id);
        $builder->where('component', $component);
        $builder->where('area', $area);

        $builder->delete();
    }
}