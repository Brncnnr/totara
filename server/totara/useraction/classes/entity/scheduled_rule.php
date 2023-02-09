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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\entity;

use core\entity\cohort;
use core\entity\tenant;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many_through;

/**
 * Entity representing a single scheduled rule.
 *
 * @property-read int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $tenant_id
 * @property bool $status
 * @property string $action
 * @property int $filter_status
 * @property int $filter_duration_source
 * @property int $filter_duration_unit
 * @property int $filter_duration_value
 * @property bool $filter_all_users
 * @property-read int $created
 * @property-read int|null $updated
 * @property-read tenant $tenant
 * @property-read cohort[]|collection $filter_audiences
 */
class scheduled_rule extends entity {
    public const TABLE = 'totara_useraction_scheduled_rule';
    public const CREATED_TIMESTAMP = 'created';
    public const UPDATED_TIMESTAMP = 'updated';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * @return has_many_through
     */
    public function filter_audiences(): has_many_through {
        return $this->has_many_through(
            scheduled_rule_audience_map::class,
            cohort::class,
            'id',
            'scheduled_rule_id',
            'cohort_id',
            'id'
        );
    }

    /**
     * Make sure we're always working with a boolean when dealing with the entity.
     *
     * @param int|null|bool $value
     * @return bool
     */
    public function get_status_attribute($value = false): bool {
        return boolval($value);
    }

    /**
     * Database stores an int, cast it back to an int if we're passed a boolean.
     *
     * @param bool $value
     * @return void
     */
    public function set_status_attribute($value): void {
        $this->set_attribute_raw('status', (int) $value);
    }

    /**
     * Making sure we always work with a bool. The model does not know how we store this value.
     *
     * @param int|null|bool $value
     * @return bool
     */
    public function get_filter_all_users_attribute($value = false): bool {
        return boolval($value);
    }

    /**
     * Database stores as an int, cast it back.
     *
     * @param bool $value
     * @return void
     */
    public function set_filter_all_users_attribute($value): void {
        $this->set_attribute_raw('filter_all_users', (int) $value);
    }

    /**
     * Rules can belong to a specific tenant.
     *
     * @return belongs_to
     */
    public function tenant(): belongs_to {
        return $this->belongs_to(tenant::class, 'tenant_id');
    }
}
