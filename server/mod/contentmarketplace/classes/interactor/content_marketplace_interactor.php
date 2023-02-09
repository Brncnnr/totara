<?php
/**
 * This file is part of Totara Core
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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\interactor;

use container_course\interactor\course_interactor;
use mod_contentmarketplace\model\content_marketplace;
use totara_contentmarketplace\interactor\base;

class content_marketplace_interactor extends base {
    /**
     * @var content_marketplace
     */
    private $model;

    /**
     * @var course_interactor
     */
    private $course_interactor;

    /**
     * content_marketplace_interactor constructor.
     * @param content_marketplace $model
     * @param int|null            $user_id
     */
    public function __construct(content_marketplace $model, ?int $user_id = null) {
        parent::__construct($user_id);
        $this->model = $model;

        $this->course_interactor = course_interactor::from_course_id($this->model->course_id, $this->actor_id);
    }

    /**
     * The module is within the course, hence without ability to access the course,
     * user should not be able to view the content marketplace at all.
     *
     * @return bool
     */
    public function can_view(): bool {
        if (!$this->course_interactor->can_access()) {
            // User must have to have the access to the course in order to view the marketplace.
            return false;
        }

        $context_module = $this->model->get_context();
        return has_capability(
            'mod/contentmarketplace:view',
            $context_module,
            $this->actor_id
        );
    }

    /**
     * @return void
     */
    public function require_view(): void {
        $this->course_interactor->require_access();

        $context_module = $this->model->get_context();
        require_capability('mod/contentmarketplace:view', $context_module);
    }

    /**
     * If the actor is not able to view the content marketplace, then user
     * should not be able to launch at all.
     *
     * @return bool
     */
    public function can_launch(): bool {
        if (!$this->can_view()) {
            return false;
        }

        $context = $this->model->get_context();
        return has_capability(
            'mod/contentmarketplace:launch',
            $context,
            $this->actor_id
        );
    }

    /**
     * @return bool
     */
    public function has_view_capability(): bool {
        if (!$this->can_view()) {
            return false;
        }

        if (!$this->course_interactor->has_view_capability()) {
            return false;
        }

        return has_capability('moodle/course:view', $this->model->get_context(), $this->actor_id);
    }

    /**
     * @return bool
     */
    public function is_site_guest(): bool {
        return isguestuser($this->actor_id);
    }

    /**
     * Checks whether user is able to enrol to the course or not.
     *
     * @return bool
     */
    public function can_enrol(): bool {
        if (!$this->can_view()) {
            return false;
        }

        return $this->course_interactor->can_enrol();
    }

    /**
     * @return bool
     */
    public function is_enrolled(): bool {
        return  $this->course_interactor->is_enrolled();
    }

    /**
     * @return int
     */
    public function get_course_id(): int {
        return $this->model->get_course_id();
    }

    /**
     * @return bool
     */
    public function non_interactive_enrol_instance_enabled(): bool {
        return $this->course_interactor->non_interactive_enrol_instance_enabled();
    }

    /*
     * @return bool
     */
    public function supports_non_interactive_enrol(): bool {
        return  $this->course_interactor->supports_non_interactive_enrol();
    }
}