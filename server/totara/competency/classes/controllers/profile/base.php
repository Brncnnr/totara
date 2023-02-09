<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\controllers\profile;

use context;
use context_user;
use core\entity\user;
use moodle_exception;
use moodle_url;
use totara_competency\helpers\capability_helper;
use totara_competency\totara\menu\my_competencies;
use totara_core\advanced_feature;
use totara_mvc\controller;

/**
 * Base competency profile controller encapsulating the functionality that we need to display competency profile pages
 */
abstract class base extends controller {

    public const LOGGED_IN_USER = 0;

    /**
     * User id passed through the attribute
     *
     * @var user
     */
    protected $user;

    protected function setup_context(): context {
        require_login();

        // Let's make sure that we've tried to get user id off the query parameters
        // and if not we get a user id off the logged in user.
        $this->setup_user()->must_be_logged_in();

        return context_user::instance($this->user->id);
    }

    /**
     * Authorize the user
     *
     * @return void
     */
    protected function authorize(): void {
        advanced_feature::require('competency_assignment');
        // parent::authorize(); We don't need to call require login here, it's always required.

        // User will not be set if the record could not be found.
        if ($this->user === null) {
            throw new moodle_exception('invaliduser');
        }

        capability_helper::require_can_view_profile($this->user->id, $this->context);
    }

    /**
     * Return whether we display profile for current user or not
     *
     * @return bool
     */
    protected function is_for_current_user() {
        if (!$this->user) {
            return false;
        }

        return $this->user->id === intval($this->currently_logged_in_user()->id);
    }

    /**
     * Get request attributes
     *
     * @return $this
     */
    protected function setup_user(): self {
        if ($this->user !== null) {
            return $this;
        }

        $id = $this->get_optional_param('user_id', self::LOGGED_IN_USER, PARAM_INT);

        if ($id === self::LOGGED_IN_USER) {
            $id = $this->currently_logged_in_user()->id;
        }

        $this->user = user::repository()->find($id);

        if (!$this->user) {
            throw new moodle_exception('invaliduser', 'error');
        }

        return $this;
    }

    /**
     * Add navigation to the top of the page
     *
     * @param array $pages Pages to add
     * @return $this
     */
    protected function add_navigation(...$pages) {
        $this->get_page()->navigation->extend_for_user((object)($this->user->to_array()));

        if ($this->user->is_logged_in()) {
            $this->get_page()->set_totara_menu_selected(my_competencies::class);
        }

        if (!empty($pages)) {
            $this->get_page()->navbar->add(
                get_string('competency_profile', 'totara_competency'),
                $this->get_profile_url()
            );

            foreach ($pages as $page) {
                if (!is_array($page)) {
                    $page = [$page];
                }

                $this->get_page()->navbar->add(...$page);
            }
        } else {
            $this->get_page()->navbar->add(get_string('competency_profile', 'totara_competency'));
        }

        return $this;
    }

    /**
     * Ensure that we have logged in user
     *
     * @return $this
     */
    protected function must_be_logged_in() {
        $user = $this->currently_logged_in_user();

        if (intval($user->id ?? null) <= 0) {
            throw new moodle_exception('A user must be logged in to get here');
        }

        return $this;
    }

    public function get_base_url(): moodle_url {
        return new moodle_url('/totara/competency/profile', []);
    }

    public function get_profile_url(): moodle_url {
        return new moodle_url(
            '/totara/competency/profile/index.php',
            $this->is_for_current_user() ? [] : ['user_id' => $this->user->id]
        );
    }

    public function get_user_assignment_url(): moodle_url {
        return new moodle_url('/totara/competency/profile/assign/index.php',
            $this->is_for_current_user() ? [] : ['user_id' => $this->user->id]
        );
    }

    protected function get_back_to_profile_text(): string {
        if ($this->is_for_current_user()) {
            return get_string('back_to_competency_profile_self',  'totara_competency');
        }

        return get_string('back_to_competency_profile',  'totara_competency');
    }
}
