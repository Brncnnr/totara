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
use core\orm\entity\relations\belongs_to;
use totara_engage\repository\share_recipient_repository;

/**
 * @property int        $id
 * @property int        $shareid
 * @property int        $sharerid
 * @property int        $instanceid
 * @property string     $area
 * @property string     $component
 * @property int        $visibility
 * @property int        $notified
 * @property int        $timecreated
 * @property int        $timemodified
 */
final class share_recipient extends entity {
    /**
     * @var string
     */
    public const TABLE = 'engage_share_recipient';

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
        return share_recipient_repository::class;
    }

    /**
     * Relationship with share.
     *
     * @return belongs_to
     */
    public function share(): belongs_to {
        return $this->belongs_to(share::class, 'shareid');
    }
}