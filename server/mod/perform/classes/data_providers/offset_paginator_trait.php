<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers;

use core\orm\pagination\offset_cursor_paginator;
use core\pagination\offset_cursor;

/**
 * Common logic for filtering, fetching and getting paginated data for use in queries etc.
 *
 * @package mod_perform\data_providers
 */
trait offset_paginator_trait {

    /**
     * Get the offset to use for getting the next page of results.
     *
     * @param offset_cursor $offset_cursor
     * @return offset_cursor_paginator
     */
    public function get_offset(offset_cursor $offset_cursor): offset_cursor_paginator {
        $query = $this->build_query();
        $this->apply_query_filters($query);
        $this->apply_query_sorting($query);

        $paginator = new offset_cursor_paginator($query, $offset_cursor);

        return $paginator;
    }

}
