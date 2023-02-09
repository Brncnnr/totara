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
 * @package totara_comment
 */
namespace totara_comment\entity;

use core\orm\entity\entity;
use totara_comment\repository\comment_repository;

/**
 * @property int            $id
 * @property string         $component
 * @property string         $area
 * @property int|null       $format
 * @property string|null    $content
 * @property int            $userid
 * @property int            $instanceid
 * @property int            $timecreated
 * @property int|null       $timemodified
 * @property int|null       $parentid       The parent's id which if it is not null then this comment is considered as a
 *                                          to another comment.
 *
 * @property int|null       $timedeleted
 * @property int|null       $reasondeleted
 * @property string|null    $contenttext
 *
 * @method static comment_repository repository()
 */
final class comment extends entity {
    /**
     * @var string
     */
    public const TABLE = 'totara_comment';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return comment_repository::class;
    }
}