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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\webapi\resolver\query;

use context;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use editor_weka\webapi\resolver\query\editor;
use totara_comment\resolver_factory;
use totara_comment\webapi\resolver\middleware\validate_comment_area;
use weka_texteditor;

/**
 * Query to fetch editor configuration of other component and area, but will be masked with this very component
 * which is totara_comment
 *
 * This query had been deprecated and the behaviour of the query had been changed,
 * please use {@see editor} instead.
 *
 * @deprecated since Totara 13.3
 */
class editor_weka extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return weka_texteditor
     */
    public static function resolve(array $args, execution_context $ec): weka_texteditor {
        debugging(
            "The query 'totara_comment_editor_weka' had been deprecated and the behaviour had been changed. " .
            "Please use query 'editor_weka_editor' instead",
            DEBUG_DEVELOPER
        );

        if (!empty($args['id'])) {
            debugging(
                "The argument 'id' has been deprecated, please do not use it",
                DEBUG_DEVELOPER
            );
        }

        $component = $args['component'];
        $area = $args['area'];
        $instance_id = $args['instance_id'];

        $resolver = resolver_factory::create_resolver($component);
        $context_id = $resolver->get_context_id($instance_id, $area);
        $context = context::instance_by_id($context_id);

        if (!$ec->has_relevant_context() && CONTEXT_SYSTEM != $context->contextlevel) {
            $ec->set_relevant_context($context);
        }

        $editor = new weka_texteditor();
        $editor->set_context_id($context_id);

        return $editor;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new validate_comment_area('comment_area'),
        ];
    }
}