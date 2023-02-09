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

namespace totara_useraction\entity\filter;

use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\filter_factory;

/**
 * Filter factory for scheduled rules
 */
class scheduled_rule_filter_factory implements filter_factory {
    /**
     * Create a new instance of the filter.
     *
     * @param string $key
     * @param $value
     * @param int|null $user_id
     * @return filter|null
     */
    public function create(string $key, $value, ?int $user_id = null): ?filter {
        switch ($key) {
            case 'tenant_id':
                return (new equal('tenant_id'))->set_value($value);
            case 'id':
                return (new equal('id'))->set_value($value);
        }
        return null;
    }
}
