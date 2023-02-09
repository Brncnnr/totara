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

namespace totara_reportedcontent\testing;

use coding_exception;
use container_workspace\discussion\discussion;
use container_workspace\entity\workspace_discussion;
use container_workspace\workspace;
use engage_article\totara_engage\resource\article;
use engage_survey\totara_engage\resource\survey;
use totara_playlist\playlist;
use totara_reportedcontent\review;

/**
 * Generator class for totara reportedcontent reviews
 */
final class generator extends \core\testing\component_generator {
    /**
     * Create a resource review (review for an article or a survey)
     *
     * @param array $parameters
     */
    public function create_resource_review_from_params(array $parameters): void {
        global $DB;

        $must_have = ['component', 'name', 'username'];
        foreach ($must_have as $key) {
            if (empty($parameters[$key])) {
                throw new \coding_exception("Must have '{$key}' when creating a resource review");
            }
        }

        $user_id = $DB->get_field('user', 'id', ['username' => $parameters['username']], MUST_EXIST);

        /** @var article|survey $resource */
        $resource = $this->get_component($parameters['component'], '', $parameters['name']);

        review::create(
            $resource->get_id(),
            $resource->get_context_id(),
            $resource->get_resourcetype(),
            '',
            $resource->get_url(),
            $resource->get_name(),
            FORMAT_PLAIN,
            $resource->get_timecreated(),
            $resource->get_userid(),
            $user_id
        );
    }

    /**
     * Create a review for a workspace discussion.
     *
     * @param array $parameters
     */
    public function create_discussion_review_from_params(array $parameters): void {
        global $DB;

        $must_have = ['workspace', 'username', 'discussion'];
        foreach ($must_have as $key) {
            if (empty($parameters[$key])) {
                throw new \coding_exception("Must have '{$key}' when creating a resource review");
            }
        }

        $user_id = $DB->get_field('user', 'id', ['username' => $parameters['username']], MUST_EXIST);
        $workspace = $this->get_component('container_workspace', '', $parameters['workspace']);
        $workspace_id = $workspace->get_id();
        $discussion = $this->get_component('container_workspace', discussion::AREA, $parameters['discussion'], compact('workspace_id'));

        review::create(
            $discussion->get_id(),
            $discussion->get_context()->id,
            'container_workspace',
            discussion::AREA,
            $discussion->get_url(),
            $discussion->get_content(),
            $discussion->get_content_format(),
            $discussion->get_time_created(),
            $discussion->get_user_id(),
            $user_id
        );
    }

    /**
     * Create a review for a comment on a discussion, resource or playlist.
     *
     * @param array $parameters
     */
    public function create_comment_review_from_params(array $parameters): void {
        global $DB;

        $must_have = ['component', 'name', 'area', 'username', 'comment'];
        foreach ($must_have as $key) {
            if (empty($parameters[$key])) {
                throw new \coding_exception("Must have '{$key}' when creating a resource review");
            }
        }

        $extra_params = [];
        if ($parameters['component'] === 'container_workspace' && $parameters['area'] === discussion::AREA) {
            if (empty($parameters['workspace'])) {
                throw new \coding_exception("Must have 'workspace' when creating a discussion comment review");
            }

            $workspace = $this->get_component('container_workspace', '', $parameters['workspace']);
            $extra_params['workspace_id'] = $workspace->get_id();
        }

        $user_id = $DB->get_field('user', 'id', ['username' => $parameters['username']], MUST_EXIST);
        $parent_component = $this->get_component($parameters['component'], $parameters['area'], $parameters['name'], $extra_params);

        // Find the comment for this component
        /** @var \totara_comment\entity\comment $comment_entity */
        $comment_entity = \totara_comment\entity\comment::repository()
            ->where('component', $parameters['component'])
            ->where('area', $parameters['area'])
            ->where('contenttext', $parameters['comment'])
            ->order_by('id')
            ->first_or_fail();

        $comment = \totara_comment\comment::from_entity($comment_entity);
        review::create(
            $comment->get_id(),
            $parent_component->get_context()->id,
            $parameters['component'],
            $parameters['area'],
            $parent_component->get_url(),
            $comment->get_content(),
            $comment->get_format(),
            $comment->get_timecreated(),
            $comment->get_userid(),
            $user_id
        );
    }

    /**
     * Simple helper to find the matching element based on names/component type
     * Keeping it simple so we don't chain complicated lookups in other components
     *
     * @param string $component
     * @param string $area
     * @param string $name
     * @param array $extra_params
     * @return mixed|article|survey|workspace|playlist|discussion
     */
    private function get_component(string $component, string $area, string $name, array $extra_params = []) {
        global $DB;

        // Go really low-level where possible to get the ids, it's just for testing
        switch ($component) {
            case 'engage_article':
                $resource_id = $DB->get_field('engage_resource', 'id', ['name' => $name, 'resourcetype' => $component], MUST_EXIST);
                return article::from_resource_id($resource_id);

            case 'engage_survey':
                $resource_id = $DB->get_field('engage_resource', 'id', ['name' => $name, 'resourcetype' => $component], MUST_EXIST);
                return survey::from_resource_id($resource_id);

            case 'totara_playlist':
                $playlist_id = $DB->get_field('playlist', 'id', ['name' => $name], MUST_EXIST);
                return playlist::from_id($playlist_id);

            case 'container_workspace':
                if ($area === discussion::AREA) {
                    $workspace_id = $extra_params['workspace_id'];

                    /** @var \container_workspace\entity\workspace_discussion $discussion_entity */
                    $discussion_entity = workspace_discussion::repository()
                        ->where('course_id', $workspace_id)
                        ->where('content_text', $name)
                        ->order_by('id')
                        ->first_or_fail();

                    return discussion::from_entity($discussion_entity);
                }

                $workspace_id = $DB->get_field('course', 'id', ['fullname' => $name, 'containertype' => $component], MUST_EXIST);
                return workspace::from_id($workspace_id);
        }

        throw new coding_exception("Component '{$component}' is not supported by the reportedcontent generator");
    }
}