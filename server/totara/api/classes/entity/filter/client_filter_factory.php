<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\entity\filter;

use core\orm\entity\filter\filter;
use core\orm\entity\filter\filter_factory;
use totara_api\entity\filter\tenant as tenant_filter;

/**
 * Convenience filters to use with the entities.
 */
class client_filter_factory implements filter_factory {

    /**
     * @inheritDoc
     */
    public function create(string $key, $value, ?int $user_id = null): ?filter {
        switch ($key) {
            case 'tenant_id':
                return $this->create_tenant_filter($value);
            // More filters go here
        }
        return null;
    }

    /**
     * @param $value
     *
     * @return filter
     */
    protected function create_tenant_filter($value): filter {
        return (new tenant_filter())
            ->set_value($value);
    }
}
