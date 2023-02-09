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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package virtualmeeting_zoom
 */

use totara_core\http\client;
use totara_core\virtualmeeting\plugin\factory\auth_factory;
use totara_core\virtualmeeting\plugin\factory\factory;
use totara_core\virtualmeeting\plugin\provider\auth_provider;
use totara_core\virtualmeeting\plugin\provider\provider;
use virtualmeeting_zoom\providers\auth;
use virtualmeeting_zoom\providers\meeting;

/**
 * Plug-in factory
 */
class virtualmeeting_zoom_factory implements factory, auth_factory {
    /**
     * @inheritDoc
     */
    public function is_available(): bool {
        return get_config('virtualmeeting_zoom', 'client_id') && get_config('virtualmeeting_zoom', 'client_secret');
    }

    /**
     * @inheritDoc
     */
    public function create_service_provider(client $client): provider {
        return new meeting($client);
    }

    /**
     * @inheritDoc
     */
    public function create_setting_page(string $section, string $displayname, bool $fulltree, bool $hidden): ?admin_settingpage {
        global $CFG;
        $page = new admin_settingpage($section, $displayname, 'moodle/site:config', $hidden);
        if ($fulltree) {
            $a = new stdClass();
            $a->redirect_url = $CFG->wwwroot . '/integrations/virtualmeeting/auth_callback.php/zoom';
            $page->add(new admin_setting_heading(
                'virtualmeeting_zoom/header_app',
                new lang_string('setting_header_app', 'virtualmeeting_zoom'),
                new lang_string('setting_header_app_desc', 'virtualmeeting_zoom', $a)
            ));
            $page->add(new admin_setting_configtext(
                'virtualmeeting_zoom/client_id',
                new lang_string('setting_client_id', 'virtualmeeting_zoom'),
                new lang_string('setting_client_id_help', 'virtualmeeting_zoom'),
                ''
            ));
            $page->add(new admin_setting_configpasswordunmask(
                'virtualmeeting_zoom/client_secret',
                new lang_string('setting_client_secret', 'virtualmeeting_zoom'),
                '',
                ''
            ));
        }
        return $page;
    }

    /**
     * @inheritDoc
     */
    public function create_auth_service_provider(client $client): auth_provider {
        return new auth($client);
    }
}
