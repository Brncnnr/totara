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

namespace totara_core\webapi\resolver\query;

use coding_exception;
use context;
use context_system;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_text;
use moodle_exception;
use moodle_url;
use navigation_node;
use settings_navigation;
use totara_core\tui\tree\tree_node;

/**
 * Gets a tree structure with navigation links to settings pages.
 * What settings options are returned will be based upon what context is provided to the query.
 *
 * E.g. inputting the context ID of a module with return a tree with module settings and course settings,
 * user context will return user profile settings etc.
 *
 * @package totara_core\webapi\resolver\query
 */
class settings_navigation_tree extends query_resolver {

    /**
     * The deepest level of children that can be returned in the GraphQL query schema.
     */
    private const MAX_DEPTH = 6;

    /**
     * Resolve the tree.
     * Note that there are no capability checks done other than the standard require login check.
     *
     * @param array $args
     * @param execution_context $ec
     * @return tree_node[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        $context = context::instance_by_id($args['context_id'], IGNORE_MISSING);
        if (!$context) {
            throw new moodle_exception('nopermissions');
        }
        if (!$context instanceof context_system) {
            $ec->set_relevant_context($context);
        }

        $page_url = new moodle_url($args['page_url']);
        $settings_nav = self::initialise_settings_navigation($context, $page_url);

        return self::load_trees($settings_nav, $context);
    }

    /**
     * Load the tree roots that we want to return.
     *
     * @param settings_navigation $settings_nav
     * @param context $context
     * @return tree_node[]
     */
    private static function load_trees(settings_navigation $settings_nav, context $context): array {
        // TODO: Add caching in TL-32139

        $tree_roots = [];
        $open_ids = [];
        foreach ($settings_nav->children as $nav) {
            /** @var navigation_node $nav */
            if (!$nav->display) {
                // This node isn't supposed to be displayed.
                continue;
            }

            // Don't include the legacy site admin tree if it isn't explicitly enabled in config.php
            if (!self::show_legacy_site_admin_menu() && ($nav->key === 'siteadministration' || $nav->key === 'root')) {
                continue;
            }

            $tree = self::map_tree($context, $nav, $open_ids);
            if ($tree) {
                $tree_roots[] = $tree;
            }
        }

        return [
            'trees' => $tree_roots,
            'open_ids' => array_unique($open_ids),
        ];
    }

    /**
     * Set up and build the settings navigation tree.
     *
     * @param context $context
     * @param moodle_url $page_url
     * @return settings_navigation
     */
    private static function initialise_settings_navigation(context $context, moodle_url $page_url): settings_navigation {
        global $CFG, $PAGE;

        if (strpos($page_url->out(false), $CFG->wwwroot) !== 0) {
            throw new coding_exception('Invalid page_url specified: ' . $page_url->out(false));
        }

        // We have to reuse the existing $PAGE instance rather than using a fresh moodle_page,
        // as many plugins use the global $PAGE when adding their nodes to the navigation structure.
        $PAGE->set_url($page_url);
        $PAGE->set_context($context);

        // Extra context handling
        switch ($context->contextlevel) {
            case CONTEXT_COURSE:
                $PAGE->set_course(get_course($context->instanceid));
                break;
            case CONTEXT_MODULE:
                $PAGE->set_cm(get_coursemodule_from_id(null, $context->instanceid, null, null, MUST_EXIST));
                break;
        }

        settings_navigation::override_active_url($page_url, self::show_legacy_site_admin_menu());
        $settings_nav = new settings_navigation($PAGE);
        $settings_nav->initialise();

        return $settings_nav;
    }

    /**
     * Recursively map the navigation tree structure into a tui node tree that can be used in Vue.
     *
     * @param context $context
     * @param navigation_node $navigation_node
     * @param array|null $open_ids Pass an array by reference. Null is only accepted in order to work in unit tests.
     * @param tree_node|null $parent
     * @param int $depth
     * @return tree_node
     */
    private static function map_tree(
        context $context,
        navigation_node $navigation_node,
        array &$open_ids = null,
        tree_node $parent = null,
        int $depth = 1
    ): tree_node {
        $id = $navigation_node->key;

        // No additional string formatting needs to be applied to the label at this point, as it will be formatted using the
        // correct context later in the GraphQL type. However, some plugins that hook into the tree might have called
        // format_string() at some point, so we need to decode any HTML entities otherwise the label may not be displayed correctly.
        $label = core_text::entities_to_utf8($navigation_node->text);

        if ($parent !== null) {
            // Prefix the ID of this node with the parent node's ID to ensure it's always unique.
            $id = $parent->get_id() . '/' . $id;
        }

        if ($depth > self::MAX_DEPTH) {
            debugging(
                "The settings navigation tree node with ID '{$id}' is deeper than the max supported depth of " .
                self::MAX_DEPTH . ", and may not be resolved correctly.",
                DEBUG_DEVELOPER
            );
        }

        $tree_node = new tree_node($id, $label);

        // Note: For now, additional information such as the current active node, icons and styling are not returned.

        // Add the link that this node has.
        if (!empty($navigation_node->action) && $navigation_node->action instanceof moodle_url) {
            $tree_node->set_link_url($navigation_node->action);
        }

        // If this node is supposed to be open by default, then add it's ID to the list of open IDs.
        if ($navigation_node->forceopen) {
            $open_ids[] = $id;
        }

        // Now process the children of this node.
        foreach ($navigation_node->children as $child) {
            $child_node = static::map_tree($context, $child, $open_ids, $tree_node, $depth + 1);
            if ($child_node) {
                $tree_node->add_children($child_node);
            }
        }

        return $tree_node;
    }

    /**
     * @return bool
     */
    private static function show_legacy_site_admin_menu(): bool {
        global $CFG;
        return !empty($CFG->legacyadminsettingsmenu);
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
