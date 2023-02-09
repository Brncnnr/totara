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
 * Formatter for the reply.
 */
final class reply_formatter extends base_formatter {

    /**
     * @inheritDoc
     */
    public function __construct(comment $comment) {
        if (!$comment->is_reply()) {
            throw new \coding_exception("Cannot use a comment within a reply formatter");
        }

        parent::__construct($comment);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $map = parent::get_map();
        $map['commentid'] = null;

        return $map;
    }

    /**
     * @inheritDoc
     */
    protected function get_field_name(string $field): string {
        if ($field == 'commentid') {
            $field = 'parent_id';
        }

        return parent::get_field_name($field);
    }
}