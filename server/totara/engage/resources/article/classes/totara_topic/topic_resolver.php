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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\totara_topic;

use totara_topic\resolver\resolver as base;
use totara_topic\topic;
use engage_article\totara_engage\resource\article;

/**
 * Resolver for article's topic.
 */
final class topic_resolver extends base {
    /**
     * @param topic       $topic
     * @param int         $itemid
     * @param int         $actorid
     * @param string      $itemtype
     *
     * @return bool
     */
    public function can_add_usage(topic $topic, int $itemid, string $itemtype, int $actorid): bool {
        if ($itemtype !== 'engage_resource') {
            debugging("Invalid itemtype '{$itemtype}'", DEBUG_DEVELOPER);
            return false;
        }

        $article = article::from_resource_id($itemid);

        if (!$article->can_update($actorid)) {
            return false;
        }

        return true;
    }

    /**
     * @param topic  $topic
     * @param int    $instanceid
     * @param int    $actorid
     * @param string $itemtype
     *
     * @return bool
     */
    public function can_delete_usage(topic $topic, int $instanceid, string $itemtype, int $actorid): bool {
        if ($itemtype !== 'engage_resource') {
            debugging("Invalid itemtype '{$itemtype}'", DEBUG_DEVELOPER);
            return false;
        }

        $article = article::from_resource_id($instanceid);

        if (!$article->can_update($actorid)) {
            return false;
        }

        return true;
    }

    /**
     * @param int    $itemid
     * @param string $itemtype
     *
     * @return \context
     */
    public function get_context_of_item(int $itemid, string $itemtype): \context {
        $article = article::from_resource_id($itemid);
        $userid = $article->get_userid();

        return \context_user::instance($userid);
    }
}