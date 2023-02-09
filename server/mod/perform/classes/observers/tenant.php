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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\tenant_deleted;
use container_perform\perform as perform_container;

class tenant {

    /**
     * Handle event when tenant is deleted. All performance activity courses for activities in the
     * deleted tenant are marked as hidden
     *
     * @param tenant_deleted $event
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function deleted(tenant_deleted $event) {
        global $DB;

        $deleted_tenant = $event->get_record_snapshot('tenant', $event->objectid);

        // Mark all performance activity course containers in the tenant hidden
        $perform_container_id = perform_container::get_category_id_from_tenant_category($deleted_tenant->categoryid);
        if ($perform_container_id !== false) {
            $DB->set_field('course', 'visible', '0', ['category' => $perform_container_id]);
        }
    }

}
