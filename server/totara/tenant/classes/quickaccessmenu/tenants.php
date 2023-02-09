<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\quickaccessmenu;

use \totara_core\quickaccessmenu\group;
use \totara_core\quickaccessmenu\item;

class tenants implements \totara_core\quickaccessmenu\provider {
    /**
     * Returns the items for the multitenancy
     *
     * @return item[]
     */
    public static function get_items(): array {
        return [
            item::from_provider('tenantsmanage', group::get(group::PLATFORM), new \lang_string('tenants', 'totara_tenant'), 10),
            item::from_provider('tenantusers', group::get(group::PLATFORM), new \lang_string('users'), 11),
        ];
    }
}
