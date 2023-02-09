<?php
/*
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\controllers;

use context;
use context_system;
use core\entity\user;
use core_user;
use moodle_exception;
use moodle_url;
use totara_core\extended_context;
use totara_mvc\controller;
use totara_mvc\tui_view;
use totara_notification\loader\notifiable_event_user_preference_loader;
use totara_notification\interactor\notifiable_event_user_preference_interactor;

/*
 * This page lists a user's notification preferences.
 */
class user_preferences extends controller {

    /** @var int */
    private $user_id = null;

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return context_system::instance();
    }

    private function get_user_id(): int {
        return user::logged_in()->id;
    }

    /**
     * @inheritDoc
     */
    public function authorize(): void {
        global $USER;

        // Still use parent functionality.
        parent::authorize();

        // Get user ID.
        $this->user_id = $this->get_optional_param('userid', null, PARAM_INT);
        if (!empty($this->user_id) && !core_user::is_real_user($this->user_id, true)) {
            throw new moodle_exception('invaliduserid', 'error');
        }

        // Additionally check that the user has the required access.
        $interactor = new notifiable_event_user_preference_interactor($this->user_id ?? $USER->id);
        if (!$interactor->can_manage()) {
            throw new moodle_exception('error_user_preference_permission', 'totara_notification');
        }
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $params = [];
        if (!empty($this->user_id)) {
            $params['userid'] = $this->user_id;
        }

        $this->set_url(
            new moodle_url('/totara/notification/user_preferences.php', $params),
        );

        $extended_context = extended_context::make_with_context($this->get_context());

        // Js/Vue requires the array to be 0 indexed
        $user_resolver_preferences = notifiable_event_user_preference_loader::get_user_resolver_classes(
            $this->user_id ?? $this->get_user_id(),
            $extended_context,
            true
        );

        $props = [
            'extended-context' => $extended_context,
            'resolver-preferences' => array_values($user_resolver_preferences),
        ];

        return tui_view::create('totara_notification/pages/UserPreferences', $props)
            ->set_title(get_string('user_preferences_page_title', 'totara_notification'));
    }
}