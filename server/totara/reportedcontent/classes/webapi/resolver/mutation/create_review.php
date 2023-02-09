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
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_reportedcontent\hook\get_review_context;
use totara_reportedcontent\review;

/**
 * Mutation for creating a reportedcontent review.
 * Any logged in user can report content
 *
 * @package totara_reportedcontent\webapi\resolver\mutation
 */
class create_review extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $item_id = $args['item_id'];
        $component = $args['component'];
        $area = $args['area'] ?? '';
        $url = $args['url'];
        $complainer_id = $USER->id;

        if (clean_param($url, PARAM_LOCALURL) !== $url || substr($url, 0, 1) === '/') {
            throw new \invalid_parameter_exception('url is not in a valid format');
        }

        // Get the review details from the appropriate hook
        $hook = new get_review_context($component, $area, $item_id);
        $hook->execute();

        if (!$hook->success) {
            throw new \coding_exception("Was unable to create a review, no hook observer was found or executed");
        }

        $context_id = $hook->context_id;
        $content = $hook->content;
        $format = $hook->format;
        $time_created = $hook->time_created;
        $user_id = $hook->user_id;

        $response = [
            'id' => null,
            'success' => false,
        ];

        // Do a check for a unique
        $repo = \totara_reportedcontent\entity\review::repository();
        $existing_id = $repo->get_existing_review_id($component, $area, $item_id, $context_id, $complainer_id);
        if (null !== $existing_id) {
            $response['id'] = $existing_id;
            return $response;
        }

        $review = review::create(
            $item_id,
            $context_id,
            $component,
            $area ?? '',
            $url,
            $content,
            $format,
            $time_created,
            $user_id,
            $complainer_id
        );
        $response['id'] = $review->get_id();
        $response['success'] = true;

        return $response;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }

}