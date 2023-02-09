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
 * @package totara_comment
 */

namespace totara_comment\testing;

use coding_exception;
use core\json_editor\node\paragraph;
use totara_comment\comment;
use totara_comment\comment_helper;
use core\json_editor\helper\document_helper;
use core\orm\query\builder;
use core\testing\component_generator;

/**
 * Generator class for totara comment
 * @method static generator instance()
 */
final class generator extends component_generator {
    /**
     * @var array
     */
    private static $comments_content;

    /**
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @param string|null $content
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return comment
     */
    public function create_comment(int $instanceid, string $component, string $area, ?string $content = null,
                                   ?int $content_format = null, ?int $actor_id = null): comment {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        if (null === $content_format) {
            $content_format = FORMAT_PLAIN;
        }

        if (null === $content || '' === $content) {
            $content = $this->random_content();
        }

        if (FORMAT_JSON_EDITOR == $content_format && !document_helper::looks_like_json($content)) {
            $content = json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text($content)]
            ]);
        }

        return comment_helper::create_comment(
            $component,
            $area,
            $instanceid,
            $content,
            $content_format,
            null,
            $actor_id
        );
    }

    /**
     * @param int $comment_id
     * @param string|null $content
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return comment
     */
    public function create_reply(int $comment_id, ?string $content = null,
                                 ?int $content_format = null, ?int $actor_id = null): comment {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        if (null === $content_format) {
            $content_format = FORMAT_PLAIN;
        }

        if (null === $content || '' === $content) {
            $content = $this->random_content();
        }

        if (FORMAT_JSON_EDITOR == $content_format) {
            $result = json_decode($content_format, true);

            if (!is_array($result) || JSON_ERROR_NONE != json_last_error()) {
                $content = json_encode([
                    'type' => 'doc',
                    'content' => [
                        paragraph::create_json_node_from_text($content)
                    ]
                ]);
            }
        }

        return comment_helper::create_reply(
            $comment_id,
            $content,
            null,
            $content_format,
            $actor_id
        );
    }

    /**
     * This is a callback from behat data generators.
     *
     * @param array $parameters
     * @return comment
     */
    public function create_comment_from_params(array $parameters): void {
        global $DB;

        $must_have = ['component', 'name', 'username', 'area'];
        foreach ($must_have as $key) {
            if (empty($parameters[$key])) {
                throw new coding_exception("Must have '{$key}' when creating a comment");
            }
        }

        $userid = $DB->get_field('user', 'id', ['username' => $parameters['username']], MUST_EXIST);
        $instance_id = $this->get_component_id($parameters['name'], $parameters['component']);

        $this->create_comment(
            $instance_id,
            $parameters['component'],
            $parameters['area'],
            $parameters['content'] ?? null,
            $parameters['format'] ?? null,
            $userid
        );
    }

    /**
     * @return string
     */
    private function random_content(): string {
        global $CFG;

        if (!isset(static::$comments_content)) {
            static::$comments_content = [];
            static::$comments_content = require("{$CFG->dirroot}/totara/comment/tests/fixtures/comments.php");
        }

        $nb = rand(0, (count(static::$comments_content) - 1));
        return static::$comments_content[$nb];
    }

    /**
     * Lookup the item_id of the specific component based on name.
     * Keeping it simple so we don't chain complicated lookups
     * in other components
     *
     * @param string $name
     * @param string $component
     * @param string|null $area
     * @return int|null
     */
    private function get_component_id(string $name, string $component, ?string $area = null): ?int {
        global $DB;

        // Go really low-level where possible to get the ids, it's just for testing
        switch ($component) {
            case 'engage_article':
            case 'engage_survey':
                return $DB->get_field('engage_resource', 'id', ['name' => $name, 'resourcetype' => $component]);

            case 'totara_playlist':
                return $DB->get_field('playlist', 'id', ['name' => $name]);

            case 'mod_approval':
                return builder::table('approval_application')->where('title', $name)->or_where('id_number', $name)->value('id');
        }

        throw new coding_exception("Component '{$component}' is not supported by the totara_comment generator");
    }

    /**
     * @param \context $context
     * @return void
     */
    public function add_context_for_default_resolver(\context $context): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/comment/tests/fixtures/totara_comment_default_resolver.php");

        \totara_comment_default_resolver::add_callback(
            'get_context_id',
            function () use ($context): int {
                return $context->id;
            }
        );
    }
}