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
namespace engage_article\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use engage_article\formatter\article_formatter;
use engage_article\totara_engage\resource\article as model;
use totara_engage\entity\engage_bookmark;
use totara_engage\repository\bookmark_repository;
use totara_engage\timeview\time_view;
use totara_engage\entity\share;
use totara_engage\repository\share_repository;
use totara_reaction\loader\reaction_loader;
use totara_topic\provider\topic_provider;

class article extends type_resolver {
    /**
     * @param string            $field
     * @param model             $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $USER;
        if (!($source instanceof model)) {
            throw new \coding_exception("Invalid parameter \$source being passed in");
        }

        switch ($field) {
            case 'resource':
                return $source;

            case 'topics':
                $resourceid = $source->get_id();
                return topic_provider::get_for_item($resourceid, 'engage_article', 'engage_resource');

            case 'updateable':
                return $source->can_update($USER->id);

            case 'owned':
                $ownerid = $source->get_userid();
                return $USER->id == $ownerid;

            case 'timeview':
                $timeview = $source->get_timeview();
                return $timeview == null ? null : time_view::get_code($timeview);

            case 'sharedbycount':
                /** @var share_repository $repo */
                $repo = share::repository();
                return $repo->get_total_sharers($source->get_id(), $source::get_resource_type());

            case 'bookmarked':
                /** @var bookmark_repository $repo */
                $repo = engage_bookmark::repository();
                return $repo->is_bookmarked($USER->id, $source->get_id(), $source::get_resource_type());

            case 'image':
                $image = $source->get_image();
                return $image ? $image->out(false) : null;

            case 'reacted':
                $resource_id = $source->get_id();
                return reaction_loader::exist($resource_id, model::get_resource_type(), 'media', $USER->id);

            default:
                $format = null;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $formatter = new article_formatter($source);
                return $formatter->format($field, $format);
        }
    }
}