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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

/**
 * Database install script
 *
 * @return boolean
 */
function xmldb_totara_evidence_install() {
    global $CFG;
    require_once(__DIR__ . '/upgradelib.php');
    require_once($CFG->dirroot . '/totara/reportbuilder/db/upgradelib.php');

    totara_evidence_create_completion_types();

    // If this is a clean install (or a unit test installation) - reportbuilder might not have been installed yet and there is no chance of a report that needs to be updated.
    // If this is an upgrade from pre t13 or reportbulder has already been installed, we ensure the default filter exist.
    $pluginman = core_plugin_manager::instance();
    $plugin_info = $pluginman->get_plugin_info('totara_reportbuilder');
    if (empty($plugin_info)) {
        return true;
    }
    $plugin_status = $plugin_info->get_status();
    if ($plugin_status === core_plugin_manager::PLUGIN_STATUS_NEW || $plugin_status === core_plugin_manager::PLUGIN_STATUS_MISSING) {
        return true;
    }

    reportbuilder_rename_data('columns', 'evidence_item', 'base', 'location', 'type', 'location');

    totara_reportbuilder_inject_filter_into_report(
        'evidence_record_of_learning',
        'base',
        'source',
        '',
        ['operator' => 1, 'value' => 1]
    );

    return true;
}
