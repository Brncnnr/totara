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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\type_resolver;
use totara_core\tui\tree\tree_node;

/**
 * Resolves the tui tree node fields for use within Vue.
 * This resolver class (and corresponding GraphQL schema) will need to be extended if you wish to return custom content.
 *
 * @package totara_core\webapi\resolver\type
 */
class tui_tree_node extends type_resolver {

    /**
     * @param string $field
     * @param tree_node $tree_node
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    final public static function resolve(string $field, $tree_node, array $args, execution_context $ec) {
        if (!$tree_node instanceof tree_node) {
            throw new coding_exception('Expected an instance of ' . tree_node::class);
        }

        switch ($field) {
            case 'id':
                return $tree_node->get_id();
            case 'label':
                // Use the context if set, but it is fine to fall back to the system context.
                $context = $ec->has_relevant_context() ? $ec->get_relevant_context() : context_system::instance();
                // Force the format to be plain - tree labels can only be simple text.
                $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);
                return $formatter->format($tree_node->get_label());
            case 'linkUrl':
                $link_url = $tree_node->get_link_url();
                if ($link_url !== null) {
                    return $link_url->out(false);
                }
                return null;
            case 'children':
                return $tree_node->get_children();
            case 'content':
                return static::get_content($tree_node, $args, $ec);
            default:
                throw new coding_exception("Unsupported field: $field");
        }
    }

    /**
     * Get the customisable content for this node and apply formatting/specific handling.
     *
     * @param tree_node $node
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    protected static function get_content(tree_node $node, array $args, execution_context $ec) {
        // Override this method if you need to apply formatting or other handling.
        return $node->get_content();
    }

}
