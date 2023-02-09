<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\event;

use engage_article\totara_engage\resource\article;

final class article_created extends base_article_event {
    /**
     * @param article $resource
     * @param int|null $actor_id
     *
     * @return base_article_event
     */
    public static function from_article(article $resource, int $actor_id = null): base_article_event {
        if (empty($actor_id)) {
            // Normally the user who created article should be the same user that created this event.
            // This should be rarely happening, unless the upstream code is using this event wrongly.
            $actor_id = $resource->get_userid();
        }

        return parent::from_article($resource, $actor_id);
    }

    /**
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('articlecreated', 'engage_article');
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'create';
    }
}