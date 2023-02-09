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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\application;

use context;
use core\entity\user;
use core\notification;
use mod_approval\interactor\application_interactor;
use mod_approval\totara\menu\dashboard as dashboard_menu;
use totara_mvc\tui_view;

/**
 * The view application.
 */
class view extends base {
    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        return $this->get_application_from_param()->get_context();
    }

    /**
     * @inheritDoc
     */
    public function process(string $action = '') {
        parent::process($action);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $application_id = $this->get_application_id_param();
        $this->set_url(self::get_url_for($application_id));
        $application = $this->get_application_from_param();
        $interactor = $application->get_interactor(user::logged_in()->id);

        if ($application->current_state->is_draft()) {
            // Silently redirect to the dashboard. A user shouldn't be able to access this page.
            redirect(dashboard::get_url());
        }
        if (!$interactor->can_view()) {
            redirect(dashboard::get_url(), get_string('error:view_application', 'mod_approval'), null, notification::ERROR);
        }
        $this->get_page()->set_totara_menu_selected(dashboard_menu::class);
        $initial_data = $this->load_application_query($application_id);
        $props = [
            'back-url' => dashboard::get_url()->out(false),
            'context-id' => $application->get_context()->id,
            'current-user' => $this->execute_graphql_operation('mod_approval_user_own_profile')['data']['profile'],
            'application-id' => $application->id,
            'query-results' => $initial_data['data'],
        ];

        $page_title = $this->get_title('view', $application);

        return parent::create_tui_view('mod_approval/pages/ApplicationView', $props)
            ->set_title($page_title);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/application/view.php';
    }
}
