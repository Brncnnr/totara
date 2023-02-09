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
 * @package totara_playlist
 */
namespace totara_playlist\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_engage\access\access;
use totara_engage\entity\engage_bookmark;
use totara_engage\link\builder;
use totara_engage\rating\rating_manager;
use totara_engage\repository\bookmark_repository;
use totara_playlist\entity\playlist_resource;
use totara_playlist\formatter\playlist_formatter;
use totara_engage\entity\share;
use totara_engage\repository\share_repository;
use totara_playlist\playlist as model;
use totara_playlist\repository\playlist_resource_repository;
use totara_topic\provider\topic_provider;
use core\orm\query\builder as query_builder;

/**
 * Resolver for type totara_playlist_playlist
 */
class playlist extends type_resolver {
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
            case 'user':
                $userid = $source->get_userid();
                return \core_user::get_user($userid);

            case 'rating':
                $manager = rating_manager::instance($source->get_id(), 'totara_playlist', model::RATING_AREA);
                return $manager->summary();

            case 'topics':
                $id = $source->get_id();
                return topic_provider::get_for_item($id, 'totara_playlist', 'playlist');

            case 'updateable':
                return $source->can_update($USER->id);

            case 'contributable':
                return $source->can_user_contribute($USER->id);

            case 'totalresources':
                /** @var playlist_resource_repository $repo */
                $repo = playlist_resource::repository();
                return $repo->get_total_of_resources($source->get_id());

            case 'owned':
                // If the current actor is an owner of the same playlist.
                $ownerid = $source->get_userid();
                return $USER->id == $ownerid;

            case 'manageable':
                return $source->can_update($USER->id) && $source->can_delete($USER->id);

            case 'sharedbycount':
                /** @var share_repository $repo */
                $repo = share::repository();
                return $repo->get_total_sharers($source->get_id(), $source::get_resource_type());

            case 'bookmarked':
                /** @var bookmark_repository $repo */
                $repo = engage_bookmark::repository();
                return $repo->is_bookmarked($USER->id, $source->get_id(), $source::get_resource_type());

            case 'url':
                // Build the link with any provided sources
                $url_builder = builder::to('totara_playlist', ['id' => $source->get_id()]);

                if ($args['library_view']) {
                    $url_builder->set_attribute('library', true);
                }

                $url = $url_builder->url();
                if (!empty($args['source'])) {
                    $url->param('source', $args['source']);
                }

                return $url->out(false);

            case 'hasnonpublicresources':
                return $source->has_non_public_resources();

            default:
                $format = null;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $formatter = new playlist_formatter($source);
                return $formatter->format($field, $format);
        }
    }
}