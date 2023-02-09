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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\local\sync_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * @var bool              $hassiteconfig
 * @var admin_settingpage $settings_page
 */
if (isset($settings_page)) {
    $settings_page->add(
        new admin_setting_heading(
            'contentmarketplace_linkedin/set_up_integration',
            new lang_string('set_up_integration', 'contentmarketplace_linkedin'),
            ''
        )
    );

    $client_id_setting = new admin_setting_configtext(
        'contentmarketplace_linkedin/client_id',
        new lang_string('client_id', 'contentmarketplace_linkedin'),
        new lang_string('client_id_help', 'contentmarketplace_linkedin'),
        null,
        PARAM_ALPHANUM
    );

    $client_id_setting->set_updatedcallback([sync_helper::class, "settings_update_callback"]);
    $settings_page->add($client_id_setting);

    $client_secret_setting = new admin_setting_configpasswordunmask(
        'contentmarketplace_linkedin/client_secret',
        new lang_string('client_secret', 'contentmarketplace_linkedin'),
        new lang_string('client_secret_help', 'contentmarketplace_linkedin'),
        ''
    );

    $client_secret_setting->set_updatedcallback([sync_helper::class, "settings_update_callback"]);
    $settings_page->add($client_secret_setting);
}