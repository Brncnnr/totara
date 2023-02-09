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
 * @package editor_weka
 */
namespace editor_weka\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;

/**
 * Query resolver to get the draft file
 */
class draft_file extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return \stored_file
     */
    public static function resolve(array $args, execution_context $ec): \stored_file {
        global $USER;

        $context = \context_user::instance($USER->id);
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($context);
        }
        $fs = get_file_storage();

        $file = $fs->get_file(
            $context->id,
            'user',
            'draft',
            (int) $args['item_id'],
            '/',
            $args['filename']
        );

        if (!$file) {
            throw new \coding_exception("File was not found");
        }

        return $file;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }

}
