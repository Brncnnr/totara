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
namespace totara_comment\formatter;

use totara_comment\comment;

/**
 * Class comment_formatter
 * @package totara_comment\formatter
 */
final class comment_formatter extends base_formatter {

    /**
     * @inheritDoc
     */
    public function __construct(comment $comment) {
        if ($comment->is_reply()) {
            throw new \coding_exception("Comment is a reply, and it cannot be used for comment formatter");
        }

        parent::__construct($comment);
    }

    /**
     * @return array
     */
    public function get_map(): array {
        $map = parent::get_map();

        $map['totalreplies'] = null;
        return $map;
    }

    /**
     * @inheritDoc
     */
    protected function get_field_name(string $field): string {
        if ($field == 'totalreplies') {
            $field = 'total_replies';
        }

        return parent::get_field_name($field);
    }

}