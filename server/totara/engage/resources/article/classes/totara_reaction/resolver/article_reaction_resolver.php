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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\totara_reaction\resolver;

use engage_article\totara_engage\interactor\article_interactor;
use engage_article\totara_engage\resource\article;
use totara_engage\access\access_manager;
use totara_reaction\resolver\base_resolver;

/**
 * Class article_reaction_resolver
 * @package engage_article\totara_reaction\resolver
 */
final class article_reaction_resolver extends base_resolver {
    /**
     * As long as the owner is not a
     *
     * @param int $resourceid
     * @param int $userid
     * @param string $area
     *
     * @return bool
     */
    public function can_create_reaction(int $resourceid, int $userid, string $area): bool {
        if (article::REACTION_AREA !== $area) {
            return false;
        }

        $article = article::from_resource_id($resourceid);

        // Confirm that the interactor can like this resource.
        $interactor = article_interactor::create_from_accessible($article, $userid);
        if (!$interactor->can_react()) {
            return false;
        }

        return access_manager::can_access($article, $userid);
    }

    /**
     * @param int $resourceid
     * @param string $area
     * @return \context
     */
    public function get_context(int $resourceid, string $area): \context {
        $article = article::from_resource_id($resourceid);
        return $article->get_context();
    }

    /**
     * @param int       $instance_id
     * @param int       $user_id
     * @param string    $area
     *
     * @return bool
     */
    public function can_view_reactions(int $instance_id, int $user_id, string $area): bool {
        if (article::REACTION_AREA === $area) {
            $article = article::from_resource_id($instance_id);
            return access_manager::can_access($article, $user_id);
        }

        throw new \coding_exception("Invalid area passed into the article resolver: {$area}");
    }
}