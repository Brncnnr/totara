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

namespace totara_competency\data_providers;

use core\entity\user;
use core\orm\collection;

/**
 * This class is a base class for a model specifically for things that depend on the user!
 * Please do not reuse it in other places. It might change
 *
 * @internal
 * @package totara_competency\models
 */
abstract class user_data_provider {

    protected $user;

    /**
     * Array of filters to apply when fetching the data
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Return whether data has been fetched
     *
     * @var bool
     */
    protected $fetched = false;

    /**
     * Page number
     *
     * @var int
     */
    protected $page = 0;

    /**
     * @var collection
     */
    protected $items;

    /**
     * Create progress model
     *
     * @param int|user $user User id or instance
     */
    public function __construct($user) {
        if ($user instanceof user) {
            $this->user = $user;

            if (!$this->user->exists()) {
                $this->user = null;
            }
        }
        if (is_numeric($user)) {
            $this->user = new user($user);
        }

        if (!$user) {
            throw new \coding_exception('Given user does not exist');
        }

        $this->items = new collection();
    }

    /**
     * Glorified progress constructor
     *
     * @param int|user $user User id or instance
     * @return $this
     */
    public static function for($user) {
        return new static($user);
    }

    /**
     * Get items for the model
     *
     * @return collection
     */
    public function get() {
        return $this->items;
    }

    /**
     * Check whether items have been fetched
     * Note, the class extending the model actually needs to update $this->fetched variable manually.
     *
     * @return bool
     */
    public function is_fetched(): bool {
        return $this->fetched;
    }

    /**
     * Set filters for this model
     *
     * @param array $filters
     * @return $this
     */
    public function set_filters(array $filters) {

        $this->filters = $filters;

        return $this;
    }

    /**
     * Set page number
     *
     * @param int $page
     * @return $this
     */
    public function set_page(int $page) {
        $this->page = $page;

        return $this;
    }

    /**
     * Reset filters
     *
     * @return $this
     */
    public function reset_filters() {
        return $this->set_filters([]);
    }

    /**
     * Get user
     *
     * @return user
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Set a collection of items
     *
     * @param collection $items
     * @return $this
     */
    protected function set_items(collection $items) {
        $this->items = $items;

        return $this;
    }
}