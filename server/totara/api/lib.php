<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds API links to category nav when required.
 *
 * When modifying this function, you may also want to update the system-level settings defined in
 * /server/totara/api/settings.php.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The context of the course
 * @return void|null return null if we don't want to display the node.
 */
function totara_api_extend_navigation_category_settings($navigation, $context) {
    global $PAGE, $DB;

    if (totara_core\advanced_feature::is_disabled('api')) {
        return null;
    }

    if (!$context->tenantid) {
        return null;
    }

    if (!($context instanceof context_coursecat)) {
        return;
    }

    $tenant = $DB->get_record('tenant', ['categoryid' => $context->instanceid]);
    if (!$tenant) {
        return null;
    }

    $categorycontext = context_coursecat::instance($tenant->categoryid);
    $nodes = [];

    // Check capabilities per node.
    if (has_capability('totara/api:manageclients', $categorycontext)) {
        $clients_url = new moodle_url('/totara/api/client/',
            [
                'tenant_id' => $tenant->id,
            ]
        );
        $nodes[] = navigation_node::create(
            get_string('clients', 'totara_api'),
            $clients_url,
            navigation_node::NODETYPE_LEAF,
            null,
            'totara_api_manage_clients',
            new pix_icon('i/settings', '')
        );
    }

    if (has_capability('totara/api:viewdocumentation', $categorycontext)) {
        $docs_url = new moodle_url('/totara/api/documentation/',
            [
                'tenant_id' => $tenant->id,
            ]
        );
        $nodes[] = navigation_node::create(
            get_string('documentation', 'totara_api'),
            $docs_url,
            navigation_node::NODETYPE_LEAF,
            null,
            'totara_api_documentation',
            new pix_icon('i/settings', '')
        );
    }

    // Add any other nodes inside API category here.

    // Conditionally add 'API' category if there's at least one node to show inside it.
    // This replicated admin tree behaviour.
    $category_api = $navigation->find('category_api', navigation_node::TYPE_CONTAINER);
    if (!$category_api && count($nodes) > 0) {
        $category_api = $navigation->add(
            get_string('pluginname', 'totara_api'),
            null,
            navigation_node::TYPE_CONTAINER,
            null,
            'category_api'
        );
    }

    foreach ($nodes as $node) {
        $category_api->add_node($node);

        // Expand and highlight current node.
        if ($PAGE->url->compare($node->action, URL_MATCH_EXACT)) {
            $category_api->force_open();
            $node->make_active();
        }
    }
}
