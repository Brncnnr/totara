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
namespace totara_engage\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use totara_engage\repository\share_repository;

/**
 * @property int        $id
 * @property int        $itemid
 * @property string     $component
 * @property int        $contextid
 * @property int        $ownerid
 * @property int        $timecreated
 * @property int        $timemodified
 *
 * @method static share_repository repository()
 */
final class share extends entity {
    /**
     * @var string
     */
    public const TABLE = 'engage_share';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @inheritDoc
     */
    public static function repository_class_name(): string {
        return share_repository::class;
    }

    /**
     * Relationship with sharers.
     *
     * @return has_many
     */
    public function sharers(): has_many {
        return $this->has_many(share_recipient::class, 'shareid')
            ->select('sharerid')
            ->group_by('sharerid');
    }

    /**
     * Relationship with recipients.
     * @param int|null $visibility
     * @return has_many
     */
    public function recipients(?int $visibility = 1): has_many {
        return $this->has_many(share_recipient::class, 'shareid')
            ->where('visibility', $visibility);
    }

}