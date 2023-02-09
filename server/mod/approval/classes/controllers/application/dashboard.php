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

namespace mod_approval\controllers\application;

use cache;
use container_approval\approval as approval_container;
use context;
use mod_approval\data_provider\application\applications_for_others;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\totara\menu\dashboard as dashboard_menu;
use totara_mvc\tui_view;
use core\entity\user;

/**
 * The application dashboard.
 */
class dashboard extends base {

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        return approval_container::get_default_category_context();
    }

    /**
     * @inheritDoc
     */
    public function process(string $action = '') {
        $role_cache = cache::make('mod_approval', 'role_map');
        $role_maps_clean = $role_cache->get('maps_clean');
        if (empty($role_maps_clean)) {
            // This sets maps_clean to true, so this is really just a first run after install or upgrade,
            // or if an event observer has invalidated the maps and they haven't been regenerated yet.
            role_map_controller::regenerate_all_maps();
        }
        $capability_cache = cache::make('mod_approval', 'capability_map');
        $capability_maps_were_reset_this_session = $capability_cache->get('maps_reset');
        if ($capability_maps_were_reset_this_session == false) {
            $user = user::logged_in();
            capability_map_controller::regenerate_all_maps($user->id);
            $capability_cache->set('maps_reset', 1);
        }
        parent::process($action);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->set_url(self::get_url());
        $this->get_page()->set_totara_menu_selected(dashboard_menu::class);
        $user = user::logged_in();
        $props = [
            'current-user-id' => $user->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => self::can_view_others_applications($user->id),
                ],
                'new-application-on-behalf' => self::can_create_application_on_behalf($user->id)
            ]
        ];
        $title = get_string('dashboard_title', 'mod_approval');

        return parent::create_tui_view('mod_approval/pages/ApplicationDashboard', $props)
            ->set_title($title);
    }

    /**
     * @param integer $user_id
     * @return boolean
     */
    private static function can_view_others_applications(int $user_id): bool {
        $provider = new applications_for_others($user_id);
        return $provider->has_any_capability();
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/application/index.php';
    }

    /**
     * @inheritDoc
     */
    public static function get_url_for(int $application_id): string {
        return self::get_url()->out(false);
    }

    /**
     * Can create an application on behalf.
     * @param int $user_id
     * @return bool
     */
    private static function can_create_application_on_behalf(int $user_id): bool {
        return has_capability_in_any_context('mod/approval:create_application_any', [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], $user_id) ||
            has_capability_in_any_context('mod/approval:create_application_user', [CONTEXT_SYSTEM, CONTEXT_USER], $user_id);
    }
}
