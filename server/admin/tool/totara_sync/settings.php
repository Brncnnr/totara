<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package tool
 * @subpackage totara_sync
 */

defined('MOODLE_INTERNAL') || die;
$systemcontext = context_system::instance();
if (has_capability('tool/totara_sync:manage', $systemcontext)) {
    require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

    $ADMIN->add('root', new admin_category('tool_totara_sync', get_string('pluginname', 'tool_totara_sync')), 'development');
    $ADMIN->add('tool_totara_sync', new admin_externalpage('totarasyncsettings',
            get_string('defaultsettings', 'tool_totara_sync'),
            "$CFG->wwwroot/admin/tool/totara_sync/admin/settings.php", 'tool/totara_sync:manage'));
    $ADMIN->add('tool_totara_sync', new admin_category('syncelements', get_string('elements', 'tool_totara_sync')));
    $ADMIN->add('tool_totara_sync', new admin_category('syncsources', get_string('sources', 'tool_totara_sync')));

    if (has_capability('tool/totara_sync:setfileaccess', $systemcontext) || tool_totara_sync_can_manage_any_element()) {
        $ADMIN->add(
            'syncelements',
            new admin_externalpage(
                'managesyncelements',
                get_string('manageelements', 'tool_totara_sync'),
                new moodle_url('/admin/tool/totara_sync/admin/elements.php'),
                'tool/totara_sync:manage'
            )
        );
    }

    $adduploadlink = false;
    foreach (tool_totara_sync_get_element_classes() as $class) {
        /** @var string|totara_sync_element $class */
        if (!method_exists($class, 'add_element_settings_structure')) {
            debugging('Invalid HR import element class '.$class, DEBUG_DEVELOPER);
            continue;
        }
        $class::add_element_settings_structure($ADMIN);

        $adduploadlink = $adduploadlink || $class::needs_upload_admin_node();
    }

    if ($adduploadlink) {
        $ADMIN->add(
            'syncsources',
            new admin_externalpage(
                'uploadsyncfiles',
                get_string('uploadsyncfiles', 'tool_totara_sync'),
                new moodle_url('/admin/tool/totara_sync/admin/uploadsourcefiles.php'),
                'tool/totara_sync:manage'
            )
        );
    }

    if (has_capability('tool/totara_sync:runsync', $systemcontext)) {
        $ADMIN->add(
            'tool_totara_sync',
            new admin_externalpage(
                'totarasyncexecute',
                get_string('syncexecute', 'tool_totara_sync'),
                new moodle_url('/admin/tool/totara_sync/admin/syncexecute.php'),
                'tool/totara_sync:runsync'
            )
        );
    }
    $ADMIN->add(
        'tool_totara_sync',
        new admin_externalpage(
            'totarasynclog',
            get_string('synclog', 'tool_totara_sync'),
            new moodle_url('/admin/tool/totara_sync/admin/synclog.php'),
            'tool/totara_sync:manage'
        )
    );
}
