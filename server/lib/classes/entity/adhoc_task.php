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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;

/**
 * Represents a single adhoc task.
 *
 * @property-read int $id record id
 * @property string $component associated component.
 * @property string $classname adhoc task class.
 * @property int $nextruntime task execution time.
 * @property int $faildelay interval before rerunning failed task.
 * @property string $customdata json string holding data for the next task run.
 * @property int $userid user that the adhoc task should run as
 * @property int $blocking whether the task blocks the entire cron process.
 *
 * @method static adhoc_task_repository repository()
 *
 * @package core
 */
class adhoc_task extends entity {
    public const TABLE = 'task_adhoc';
}
