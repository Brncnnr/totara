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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\application;

use context;
use core\entity\user;
use core\notification;
use totara_mvc\tui_view;

/**
 * The edit application.
 */
class edit extends base {

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        return $this->get_application_from_param()->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $application_id = $this->get_application_id_param();
        $this->set_url(self::get_url_for($application_id));
        $application = $this->get_application_from_param();
        $interactor = $application->get_interactor(user::logged_in()->id);

        if (!$interactor->can_edit()) {
            if ($interactor->can_view()) {
                redirect(
                    view::get_url_for($application_id),
                    get_string('error:edit_application', 'mod_approval'),
                    null,
                    notification::ERROR
                );
            } else {
                redirect(dashboard::get_url(), get_string('error:view_application', 'mod_approval'), null, notification::ERROR);
            }
        }

        $initial_data = $this->load_application_query($application_id);
        $props = [
            'current-user-id' => user::logged_in()->id,
            'back-url' => dashboard::get_url()->out(false),
            'query-results' => $initial_data['data'],
        ];

        $page_title = $this->get_title('edit', $application);

        return parent::create_tui_view(
            'mod_approval/pages/ApplicationEdit',
            $props
        )->set_title($page_title);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/application/edit.php';
    }
}
