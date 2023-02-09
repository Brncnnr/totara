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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\repository;

class element_identifier_repository extends repository {

    /**
     * Filter to one or more element_identifier entities base on one or more identifiers.
     *
     * @param string|string[] $identifier
     * @return $this
     */
    public function filter_by_identifier($identifier) {
        return $this->where('identifier', $identifier);
    }

    /**
     * Filter to one or more element_identifier entities based on one or more ids.
     *
     * @param int|int[] $id
     * @return $this
     */
    public function filter_by_identifier_id($id) {
        return $this->where('id', $id);
    }

    /**
     * Filter to element_identifier entities which aren't linked from any elements.
     *
     * Used by a cleanup task to delete unused identifiers.
     *
     * @return $this
     */
    public function filter_by_unused_identifiers() {
        // NOTE: Don't add any multi-tenancy support into this query, it is designed to work globally.
        return $this
            ->left_join(['perform_element', 'fbui'], 'id', '=', 'identifier_id')
            ->select('*')
            ->where_null('fbui.id');
    }
}
