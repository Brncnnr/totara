<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Aaron Machin <aaron.machin@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\entity;

use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use totara_useraction\entity\scheduled_rule;

/**
 * Entity representing a single scheduled rule execution history event.
 *
 * @property-read int $id
 * @property int $scheduled_rule_id
 * @property int $user_id
 * @property int $created
 * @property bool $success
 * @property string $action
 * @property string|null $message
 * @property-read scheduled_rule $scheduled_rule
 */
class scheduled_rule_history extends entity {
    public const TABLE = 'totara_useraction_scheduled_rule_history';
    public const CREATED_TIMESTAMP = 'created';

    /**
     * History events belong to a specific rule.
     *
     * @return belongs_to
     */
    public function scheduled_rule(): belongs_to {
        return $this->belongs_to(scheduled_rule::class, 'scheduled_rule_id');
    }

    /**
     * History events are associated to a user.
     *
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

    /**
     * Make sure we're always working with a boolean when dealing with the entity.
     *
     * @param int|null|bool $value
     * @return bool
     */
    public function get_success_attribute($value = false): bool {
        return boolval($value);
    }

    /**
     * Database stores an int, cast it back to an int if we're passed a boolean.
     *
     * @param $value
     * @return void
     */
    public function set_success_attribute($value): void {
        $this->set_attribute_raw('success', (int) $value);
    }
}
