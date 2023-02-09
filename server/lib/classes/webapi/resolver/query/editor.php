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
 * @package core
 */
namespace core\webapi\resolver\query;

use context;
use context_system;
use core\editor\abstraction\context_aware_editor;
use core\webapi\middleware\clean_content_format;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware;
use core\webapi\middleware\require_login;
use texteditor;

class editor extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return texteditor
     */
    public static function resolve(array $args, execution_context $ec): texteditor {
        global $CFG;

        // Default context to the context system.
        $context = context_system::instance();

        if (!empty($args['context_id'])) {
            $context = context::instance_by_id($args['context_id']);
        }

        if (!$ec->has_relevant_context() && CONTEXT_SYSTEM != $context->contextlevel) {
            $ec->set_relevant_context($context);
        }

        $format = $args['format'] ?? null;
        $framework = $args['framework'] ?? null;

        require_once("{$CFG->dirroot}/lib/editorlib.php");
        $editor = editors_get_preferred_editor($format, $framework);

        if ($editor instanceof context_aware_editor) {
            $editor->set_context_id($context->id);
        }

        return $editor;
    }

    /**
     * @return middleware[]
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new clean_content_format('format')
        ];
    }
}