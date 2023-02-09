<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */
defined('MOODLE_INTERNAL') || die;

use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\local;
use totara_contentmarketplace\workflow_manager\exploremarketplace;

/**
 * @var bool           $hassiteconfig
 * @var context_system $systemcontext
 * @var admin_root     $ADMIN
 * @var stdClass       $CFG
 */

$content_marketplace_capabilities = [
    'totara/contentmarketplace:config',
    'totara/contentmarketplace:add'
];

$has_setting_config = $hassiteconfig || has_any_capability($content_marketplace_capabilities, $systemcontext);

$ADMIN->add(
    'modules',
    new admin_category(
        'contentmarketplace',
        get_string('contentmarketplace', 'totara_contentmarketplace'),
        !$has_setting_config
    )
);

$marketplaceenabled = local::is_enabled();
if ($has_setting_config && $marketplaceenabled) {
    $ADMIN->add('contentmarketplace', new admin_externalpage(
        'manage_content_marketplaces',
        get_string('manage_content_marketplaces', 'totara_contentmarketplace'),
        $CFG->wwwroot . '/totara/contentmarketplace/marketplaces.php',
        'totara/contentmarketplace:config',
    ));

    $beforesibling = null;
    if (has_any_capability(['moodle/restore:restorefile', 'moodle/backup:downloadfile'], $systemcontext)) {
        $beforesibling = 'restorecourse';
    }
    $wm = new exploremarketplace();
    $ADMIN->add(
        'courses',
        new admin_externalpage(
            'exploremarketplaces',
            new lang_string('explore_totara_content', 'totara_contentmarketplace'),
            $wm->get_url(),
            ['totara/contentmarketplace:add'],
            (!$marketplaceenabled || !$wm->workflows_available())
        ),
        $beforesibling
    );

    // Load the settings for sub plugins.
    $manager = core_plugin_manager::instance();
    $sub_plugins = $manager->get_subplugins_of_plugin('totara_contentmarketplace');

    /** @var contentmarketplace $plugin */
    foreach ($sub_plugins as $plugin) {
        if ($plugin->is_enabled()) {
            $plugin->load_settings($ADMIN, 'contentmarketplace', $has_setting_config);
        }
    }

    // Clean up after ourselves, the admin tree is big enough without us leaving things around.
    unset($wm);
    unset($beforesibling);
    unset($manager);
    unset($sub_plugins);
}

// Clearing these local variables just in case some other scripts that get included might prefer to these.
unset($content_marketplace_capabilities);
unset($has_setting_config);
unset($marketplaceenabled);