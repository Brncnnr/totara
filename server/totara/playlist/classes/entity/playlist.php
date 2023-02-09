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
namespace totara_playlist\entity;

use core\orm\entity\entity;
use totara_engage\access\access;
use totara_playlist\repository\playlist_repository;

/**
 * @property string         $name
 * @property string|null    $summary
 * @property int            $summaryformat
 * @property int            $userid
 * @property int            $access
 * @property int            $timecreated
 * @property int            $timemodified
 * @property int|null       $contextid
 *
 * @method static playlist_repository repository()
 */
final class playlist extends entity {
    /**
     * @var string
     */
    public const TABLE = 'playlist';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @param string|int $value
     * @return void
     */
    protected function set_access_attribute($value): void {
        if (!access::is_valid($value)) {
            throw new \coding_exception("Invalid access value '{$value}'");
        }

        $this->set_attribute_raw('access', $value);
    }

    /**
     * @inheritDoc
     */
    public static function repository_class_name(): string {
        return playlist_repository::class;
    }
}