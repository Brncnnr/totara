<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\webapi\resolver\query;

use context;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use editor_weka\hook\find_context;
use weka_texteditor;

/**
 * Query resolver for editor_weka_editor
 */
class editor extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return weka_texteditor
     */
    public static function resolve(array $args, execution_context $ec): weka_texteditor {
        global $CFG;

        // For backward compatible, we will need to populate usage identifier with
        // the current field 'component'/'area'.
        $temp_usage_identifier = [];
        if (!empty($args['component'])) {
            debugging(
                "The parameter 'component' had been deprecated, please use 'usage_identifier' instead.",
                DEBUG_DEVELOPER
            );

            // For backward compatible
            $temp_usage_identifier['component'] = $args['component'];
        }

        if (!empty($args['instance_id'])) {
            debugging(
                "The parameter 'instance_id' had been deprecated, please use 'usage_identifier' instead.",
                DEBUG_DEVELOPER
            );

            $temp_usage_identifier['instance_id'] = $args['instance_id'];
        }

        if (!empty($args['area'])) {
            debugging(
                "The parameter 'area' had been deprecated, please use 'usage_identifier' instead.",
                DEBUG_DEVELOPER
            );

            $temp_usage_identifier['area'] = $args['area'];
        }

        if (empty($args['usage_identifier']) && !empty($args['component']) && !empty($args['area'])) {
            $args['usage_identifier'] = $temp_usage_identifier;
        }

        require_once("{$CFG->dirroot}/lib/editor/weka/lib.php");
        $editor = new weka_texteditor();

        // Start looking for the context.
        $context = null;
        if (isset($args['context_id'])) {
            $context = context::instance_by_id($args['context_id']);
        } else if (isset($args['usage_identifier'])) {
            /** @var array $usage_identifier */
            $usage_identifier = $args['usage_identifier'];

            // The instance_id is only provided when updating an existing instance.
            $instance_id = null;

            if (isset($usage_identifier['instance_id'])) {
                $instance_id = (int) $usage_identifier['instance_id'];
            }

            $hook = new find_context($usage_identifier['component'], $usage_identifier['area'], $instance_id);
            $hook->execute();

            $context = $hook->get_context();
        }

        if (null !== $context) {
            $editor->set_context_id($context->id);

            if (!$ec->has_relevant_context() && CONTEXT_SYSTEM != $context->contextlevel) {
                $ec->set_relevant_context($context);
            }
        }

        return $editor;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            // We don't require logging in, as the editor can be used with or without a user in the session.
        ];
    }

}