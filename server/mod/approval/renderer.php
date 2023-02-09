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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use mod_approval\entity\form\form;
use mod_approval\model\form\approvalform_base;
use mod_approval\plugininfo\approvalform;

/**
  * Approval workflow renderer.
  */
class mod_approval_renderer extends plugin_renderer_base {
    /**
     * Render the approvalform plugin management table.
     *
     * @return string
     */
    public function render_approvalform_plugins(): string {
        global $CFG;
        $table = new html_table();
        $table->summary = '';
        $table->attributes['class'] = 'admintable generaltable';
        $table->head = [
            get_string('plugin'),
            get_string('form_instances', 'mod_approval'),
            get_string('version'),
            get_string('schema_version', 'mod_approval'),
            get_string('enabled', 'core_admin'),
            get_string('settings'),
            get_string('uninstallplugin', 'core_admin')
        ];
        foreach (core_plugin_manager::instance()->get_plugins_of_type('approvalform') as $plugin) {
            /** @var approvalform $plugin */
            $safedisplayname = s($plugin->displayname);
            $safeversion = s($plugin->versiondb);
            $enabled = $plugin->is_enabled();
            $approvalform_plugin = approvalform_base::from_plugin_name($plugin->name);
            $cells = [];
            // Name
            $cells[] = new html_table_cell($safedisplayname);
            // Forms
            $cells[] = form::repository()->where('plugin_name', '=', $plugin->name)->count();
            // Version
            $cells[] = new html_table_cell($safeversion);
            // Schema version
            $cells[] = $approvalform_plugin->get_form_version();
            // Settings
            if ($enabled) {
                $icon = $this->flex_icon('hide', ['alt' => get_string('disable_plugin', 'mod_approval', $safedisplayname)]);
                $action = 'disable';
            } else {
                $icon = $this->flex_icon('show', ['alt' => get_string('enable_plugin', 'mod_approval', $safedisplayname)]);
                $action = 'enable';
            }
            // TODO: Do not expose sesskey
            $button = html_writer::link("?action={$action}&plugin=" . s($plugin->name) . '&sesskey=' . sesskey(), $icon, ['role' => 'button']);
            $cells[] = new html_table_cell($button);
            if (file_exists($plugin->full_path('settings.php'))) {
                $cells[] = html_writer::link(new moodle_url("/{$CFG->admin}/settings.php", ['section' => $plugin->get_settings_section_name()]), get_string('settings'));
            } else {
                $cells[] = '';
            }
            // Uninstall
            $uninstall = '';
            if ($uninstallurl = core_plugin_manager::instance()->get_uninstall_url($plugin->component, 'overview')) {
                // WARNING: get_uninstall_url exposes sesskey, which we can do nothing with :(
                $uninstall = html_writer::link($uninstallurl, get_string('uninstallplugin', 'core_admin'));
            }
            $cells[] = $uninstall;
            $row = new html_table_row($cells);
            if (!$enabled) {
                $row->attributes['class'] = 'dimmed_text';
            }
            $table->data[] = $row;
        }

        return $this->render($table);
    }
}
