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
 * @package mod_approval
 */

namespace mod_approval\data_provider;

use core\orm\pagination\cursor_paginator;
use core\orm\query\sql\query;
use core\pagination\cursor;

/**
 * Common logic for filtering, fetching and getting paginated data for use in queries etc.
 *
 * @package mod_approval\data_provider
 */
trait cursor_paginator_trait {
    /**
     * Move the paginator to the next set of results and return it
     * NOTE: The caller is expected to call the applicable paginator methods to obtain the items, next_cursor, etc.
     *
     * @param cursor $cursor Caller should initialize
     * @param bool $include_total
     * @return cursor_paginator
     */
    public function get_next(cursor $cursor, bool $include_total = false): cursor_paginator {
        $query = $this->build_query();
        $this->apply_query_filters($query);
        $this->apply_query_sorting($query);

        $paginator = new cursor_paginator($query, $cursor, $include_total);
        $paginator->get();

        return $paginator;
    }

}
