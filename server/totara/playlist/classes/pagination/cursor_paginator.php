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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\pagination;

use core\pagination\base_cursor;
use core\pagination\offset_cursor_paginator;
use core\orm\query\builder;

/**
 * Class cursor_paginator
 * @package totara_playlist\pagination
 */
final class cursor_paginator extends offset_cursor_paginator {
    /**
     * @var cursor
     */
    protected $cursor;

    /**
     * cursor_paginator constructor.
     * @param builder $query
     * @param cursor $cursor
     */
    public function __construct(builder $query, cursor $cursor) {
        parent::__construct($query, $cursor);
    }

    /**
     * @param builder $query
     * @param bool $include_total
     *
     * @return base_cursor|null
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        $page = $this->cursor->get_page();
        $limit = $this->cursor->get_limit();

        if (0 == $limit) {
            // Zero limit means that we are fetching all rows - therefor set the page to zero so that
            // the builder is able to understand.
            $page = 0;
        }

        $builder_paginator = $query->paginate($page, $limit);
        if ($include_total) {
            $this->total = $builder_paginator->get_total();
        }

        $this->items = $builder_paginator->get_items();

        if (0 === $page) {
            // $page is zero means that we are loading everything - therefore, there is no next cursor.
            return null;
        }

        // Tweak the next loading limitation, since first initial load can just be anything.
        $next_page_limit = $this->cursor->get_limit_next_cursor();
        return $this->create_next_cursor($page, $next_page_limit);
    }
}