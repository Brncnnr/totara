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
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\entity;

use core\orm\entity\entity;
use totara_reportedcontent\repository\review_repository;

/**
 * Entity for the specific review.
 * A review is something that a user has reported that requires admin
 * @property int $id
 * @property int $target_user_id
 * @property int|null $complainer_id
 * @property int|null $reviewer_id
 * @property string $content
 * @property int|null $format
 * @property string $url
 * @property int|null $status
 * @property int $time_created
 * @property int|null $time_content
 * @property int|null $time_reviewed
 * @property int $item_id
 * @property int $context_id
 * @property string $component
 * @property string $area
 * @method static review_repository repository()
 */
final class review extends entity {
    /**
     * @var string
     */
    public const TABLE = 'totara_reportedcontent';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return review_repository::class;
    }
}