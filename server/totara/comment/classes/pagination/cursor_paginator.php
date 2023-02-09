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
 * @package totara_comment
 */
namespace totara_comment\pagination;

use core\orm\query\builder;
use core\pagination\base_cursor;
use core\pagination\offset_cursor_paginator as base;

/**
 * The only reason that this paginator class exist is because that we would want
 * to provide the ability for the component that use totara_comment component
 * to be able to have different limited load in different page.
 *
 * For example: workspace's discussion just want to load a very first comment at the
 * first loaded then start loading the rest of the comments for the next run.
 */
final class cursor_paginator extends base {
    /**
     * Special cursor within totara_comment component.
     * @var cursor
     */
    protected $cursor;

    /**
     * offset_cursor_paginator constructor.
     * @param builder $query
     * @param cursor $cursor
     */
    public function __construct(builder $query, cursor $cursor) {
        parent::__construct($query, $cursor);
    }

    /**
     * @param builder $query
     * @param bool $include_total
     * @return base_cursor|null
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        $page = $this->cursor->get_page();
        $limit = $this->cursor->get_limit();

        $builder_paginator = $query->paginate($page, $limit);
        if ($include_total) {
            $this->total = $builder_paginator->get_total();
        }

        $this->items = $builder_paginator->get_items();

        // Since totara_comment allow the component using it to tweak the number of limit of how many items
        // to be loaded for the next page.
        $next_page_limit = $this->cursor->get_limit_for_next_cursor();
        return $this->create_next_cursor($page, $next_page_limit);
    }

    /**
     * Overridding the function in order to provide our totara_comment cursor.
     *
     * @param int $page
     * @param int $limit
     *
     * @return base_cursor|null
     */
    protected function create_next_cursor(int $page, int $limit): ?base_cursor {
        $cursor = parent::create_next_cursor($page, $limit);
        if (null === $cursor) {
            return null;
        }

        $next_cursor = new cursor();
        $next_cursor->set_page($cursor->get_page());
        $next_cursor->set_limit($cursor-> get_limit());

        return $next_cursor;
    }
}