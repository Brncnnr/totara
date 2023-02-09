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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_article
 */

namespace totara_engage\link;

use coding_exception;
use core_user;
use moodle_url;

/**
 * Build the link to the library pages
 *
 * @package engage_article\totara_engage\link
 */
final class library_destination extends destination_generator {
    /**
     * Your Resources library page
     *
     * @var int
     */
    const PAGE_YOUR_RESOURCES = 0;

    /**
     * Saved resources library page
     *
     * @var int
     */
    const PAGE_BOOKMARKED = 1;

    /**
     * Shared with you library page
     *
     * @var int
     */
    const PAGE_SHARED = 2;

    /**
     * User Name's Library page
     *
     * @var int
     */
    const PAGE_OWNERS_RESOURCES = 3;

    /**
     * Search results library page
     *
     * @var int
     */
    const PAGE_SEARCH_RESULTS = 4;

    /**
     * Helper to link to "Other user's resources" page. Requires
     * the user id to be provided.
     *
     * @param int $user_id
     * @return $this
     */
    public function page_owners_resources(int $user_id): library_destination {
        $this->attributes['user_id'] = $user_id;
        $this->attributes['page'] = self::PAGE_OWNERS_RESOURCES;
        return $this;
    }

    /**
     * Helper to create a link to the "Your Resources" page
     *
     * @return $this
     */
    public function page_your_resources(): library_destination {
        $this->attributes['page'] = self::PAGE_YOUR_RESOURCES;
        return $this;
    }

    /**
     * Helper to create a link to the "Saved resources" page
     *
     * @return $this
     */
    public function page_bookmarked(): library_destination {
        $this->attributes['page'] = self::PAGE_BOOKMARKED;
        return $this;
    }

    /**
     * Helper to create a link to the "Shared with you" page
     *
     * @return $this
     */
    public function page_shared(): library_destination {
        $this->attributes['page'] = self::PAGE_SHARED;
        return $this;
    }

    /**
     * Helper to link to the "Search results" page
     *
     * @param string $search
     * @return $this
     */
    public function page_search(string $search): library_destination {
        $this->attributes['search'] = $search;
        $this->attributes['page'] = self::PAGE_SEARCH_RESULTS;
        return $this;
    }

    /**
     * @return string
     */
    public function label(): string {
        $page = $this->attributes['page'] ?? null;

        switch ($page) {
            case self::PAGE_OWNERS_RESOURCES:
                $user_id = $this->attributes['user_id'] ?? null;
                if (empty($user_id)) {
                    throw new coding_exception('User\'s resources page requires the user_id attribute to be set.');
                }
                $owner = core_user::get_user($user_id);
                return get_string('usersresources', 'totara_engage', fullname($owner));

            case self::PAGE_SHARED:
                return get_string('sharedwithyou', 'totara_engage');

            case self::PAGE_BOOKMARKED:
                return get_string('savedresources', 'totara_engage');

            case self::PAGE_SEARCH_RESULTS:
                return get_string('searchresults', 'totara_engage');

            case self::PAGE_YOUR_RESOURCES:
            default:
                return get_string('yourresources', 'totara_engage');
        }
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        $page = $this->attributes['page'] ?? null;

        switch ($page) {
            case self::PAGE_OWNERS_RESOURCES:
                $user_id = $this->attributes['user_id'] ?? null;
                if (empty($user_id)) {
                    throw new coding_exception('User\'s resources page requires the user_id attribute to be set.');
                }
                return new moodle_url('/totara/engage/user_resources.php', ['user_id' => $user_id]);

            case self::PAGE_SHARED:
                return new moodle_url('/totara/engage/shared_with_you.php');

            case self::PAGE_BOOKMARKED:
                return new moodle_url('/totara/engage/saved_resources.php');

            case self::PAGE_SEARCH_RESULTS:
                $search_term = $this->attributes['search'] ?? optional_param('search', null, PARAM_TEXT);
                return new moodle_url('/totara/engage/search_results.php', ['search' => $search_term]);

            case self::PAGE_YOUR_RESOURCES:
            default:
                return new moodle_url('/totara/engage/your_resources.php');
        }
    }
}