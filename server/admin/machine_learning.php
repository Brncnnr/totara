<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core_ml
 */

use core_ml\settings_helper;
use totara_core\advanced_feature;

require_once(__DIR__ . '/../config.php');
global $CFG, $OUTPUT;

$action = optional_param('action', null, PARAM_ALPHA);
$plugin = optional_param('plugin', null, PARAM_PLUGIN);

require_login();
require_capability('moodle/site:config', \context_system::instance());

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('machine_learning_manage');

$features = advanced_feature::get_available();

if (null !== $action && null !== $plugin) {
    // Start doing the action
    require_sesskey();

    // Little bit of validation - if this plugin is an advanced feature
    // then it can only be toggled if the advanced feature is on.
    // The UI below will remove the dropdown completely, however this is a
    // check that nobody's bypassed the UI directly.
    $ml_plugin = 'ml_' . $plugin;
    if (in_array($ml_plugin, $features) && advanced_feature::is_disabled($ml_plugin)) {
        throw new \coding_exception("Cannot '{$action}' plugin '${plugin}' as the matching advanced feature is disabled.");
    }

    switch ($action) {
        case 'enable':
            settings_helper::enable_ml_plugin($plugin);
            break;

        case 'disable':
            settings_helper::disable_ml_plugin($plugin);
            break;

        default:
            throw new \coding_exception("Invalid action option '{$action}'");
    }
}

$table = new html_table();
$table->head = [
    get_string('ml', 'ml'),
    get_string('action', 'moodle'),
    get_string('settings', 'moodle')
];

$table->id = 'machine_learning_settings';
$table->data = [];

$manager = core_plugin_manager::instance();
$plugins = $manager->get_plugins_of_type('ml');

/** @var \core\plugininfo\ml $plugin */
foreach ($plugins as $plugin) {
    if (!$plugin->can_toggle()) {
        continue;
    }

    $plugin->init_display_name();
    $row = [
        $plugin->displayname,
    ];

    // Figure out if this is an advanced feature & if it should be available or not
    $feature = $plugin->type . '_' . $plugin->name;
    $can_toggle = true;
    if (in_array($feature, $features) && advanced_feature::is_disabled($feature)) {
        $can_toggle = false;
    }

    $action_url = new \moodle_url(
        '/admin/machine_learning.php',
        [
            'sesskey' => sesskey(),
            'action' => 'disable',
            'plugin' => $plugin->name
        ]
    );

    if (!$plugin->is_enabled()) {
        $action_url->param('action', 'enable');
    }

    $select = new single_select(
        $action_url,
        'state',
        [
            0 => get_string('off', 'ml'),
            1 => get_string('on', 'ml')
        ],
        (int) $plugin->is_enabled(),
        []
    );

    // If the feature is off, we force the engine off
    $row[] = $can_toggle ? $OUTPUT->render($select) : get_string('off', 'ml');
    $setting_url = $plugin->get_settings_url();

    if (null !== $setting_url) {
        $row[] = html_writer::link($setting_url, get_string('settings'));
    } else {
        $row[] = '';
    }

    $table->data[] = $row;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('ml_settings', 'ml'));

echo $OUTPUT->render($table);
echo html_writer::tag('p', get_string('warning', 'ml'));

echo $OUTPUT->footer();
