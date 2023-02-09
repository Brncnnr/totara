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

namespace engage_article\testing;

use stdClass, context;
use engage_article\totara_engage\resource\article;
use totara_engage\share\shareable;
use totara_engage\timeview\time_view;
use core_user\totara_engage\share\recipient\user as user_recipient;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;
use totara_engage\access\access;
use totara_topic\provider\topic_provider;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;

final class generator extends \core\testing\component_generator {
    /**
     * @var string[]
     */
    private static $items;

    /**
     * @param array|\stdClass $parameters
     * @return article
     */
    public function create_article($parameters = []): article {
        global $USER;

        if ($parameters instanceof \stdClass) {
            $parameters = get_object_vars($parameters);
        }

        if (!is_array($parameters)) {
            throw new \coding_exception("Invalid parameter \$parameters");
        }

        if (!isset($parameters['name'])) {
            $parameters['name'] = $this->get_random_name();

            if (\core_text::strlen($parameters['name']) > 75) {
                $parameters['name'] = \core_text::substr($parameters['name'], 0, 75);
            }
        }

        $userid = null;

        if (isset($parameters['userid'])) {
            $userid = $parameters['userid'];
            unset($parameters['userid']);
        } else {
            $userid = $USER->id;
        }

        if (!isset($parameters['content'])) {
            $parameters['content'] = "This is content";
        }

        if (!isset($parameters['timeview'])) {
            $parameters['timeview'] = time_view::LESS_THAN_FIVE;
        }

        if (!isset($parameters['contextid'])) {
            $context = \context_user::instance($userid);
            $parameters['contextid'] = $context->id;
        }

        if (isset($parameters['format']) && FORMAT_JSON_EDITOR == $parameters['format']) {
            $content = $parameters['content'];

            // A helper to convert a simple text into a nice json document content.
            if (!document_helper::looks_like_json($content)) {
                $parameters['content'] = json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text($content)]
                ]);
            }
        }

        /** @var article $article */
        $article = article::create($parameters, $userid);
        return $article;
    }

    /**
     * @param array|stdClass $parameters
     * @return article
     */
    public function create_public_article($parameters = []): article {
        if (is_object($parameters)) {
            $parameters = (array) $parameters;
        }

        $parameters['access'] = access::PUBLIC;
        return $this->create_article($parameters);
    }


    /**
     * @param array|stdClass $parameters
     * @return article
     */
    public function create_restricted_article($parameters = []): article {
        if (is_object($parameters)) {
            $parameters = (array) $parameters;
        }

        $parameters['access'] = access::RESTRICTED;
        return $this->create_article($parameters);
    }

    /**
     * @return string
     */
    private function get_random_name(): string {
        global $CFG;
        if (null === static::$items) {
            static::$items = require_once(
                "{$CFG->dirroot}/totara/engage/resources/article/tests/fixtures/article_names.php"
            );
        }

        $nb = rand(0, (count(static::$items) - 1));
        return static::$items[$nb];
    }

    /**
     * @param int $count
     * @return array
     */
    public function create_users(int $count): array {
        $users = [];
        for ($x = 1; $x <= $count; ++$x) {
            $user['firstname'] = "Some{$x}";
            $user['lastname'] = "Any{$x}";
            $users[] = $this->datagenerator->create_user($user);
        }

        return $users;
    }

    /**
     * @param array $users
     * @return array
     */
    public function create_user_recipients(array $users): array {
        $recipients = [];
        foreach ($users as $user) {
            $recipients[] = new user_recipient($user->id);
        }
        return $recipients;
    }

    /**
     * @param shareable $article
     * @param array $recipients
     * @return share_model[]
     */
    public function share_article(shareable $article, array $recipients): array {
        $context = $article->get_context();

        // Make the create method public so we can test it.
        $class = new \ReflectionClass(share_manager::class);
        $method = $class->getMethod('create');
        $method->setAccessible(true);

        return $method->invokeArgs(null, [
            $article->get_id(),
            $article->get_userid(),
            article::get_resource_type(),
            $recipients,
            $context->id
        ]);
    }

    /**
     * @param int $permission
     * @param int $userid
     * @param context $context
     *
     * @return void
     */
    public function set_capabilities(int $permission, int $userid, context $context): void {
        $roles = get_archetype_roles('user');
        foreach ($roles as $role) {
            // Can share a article.
            role_assign($role->id, $userid, $context->id);
            assign_capability('engage/article:share', $permission, $role->id, $context, true);
        }
    }

    /**
     * This is a callback from behat data generators.
     *
     * @param array $parameters
     * @return article
     */
    public function create_article_from_params(array $parameters): article {
        global $DB;

        if (!isset($parameters['name']) || !isset($parameters['content'])) {
            throw new \coding_exception("Cannot create the article without name or content");
        } else if (!isset($parameters['username'])) {
            throw new \coding_exception("Cannot create article without user");
        }

        $userid = $DB->get_field('user', 'id', ['username' => $parameters['username']], MUST_EXIST);
        $format = FORMAT_PLAIN;

        if (isset($parameters['format'])) {
            switch ($parameters['format']) {
                case 'FORMAT_JSON_EDITOR':
                    $format = FORMAT_JSON_EDITOR;
                    break;

                case 'FORMAT_HTML':
                    $format = FORMAT_HTML;
                    break;

                case 'FORMAT_PLAIN':
                default:
                    $format = FORMAT_PLAIN;
                    break;
            }
        }

        $data = [
            'name' => $parameters['name'],
            'content' => $parameters['content'],
            'format' => $format,
            'userid' => $userid
        ];

        $access = access::PRIVATE;
        if (isset($parameters['access'])) {
            $access = access::get_value($parameters['access']);
            if (access::is_public($access) && (!isset($parameters['topics'])) || empty($parameters['topics']) ) {
                throw new \coding_exception("Cannot set the article to public without the topics");
            }
        }

        $data['access'] = $access;
        $data['topics'] = [];

        if (isset($parameters['topics']) && !empty($parameters['topics'])) {
            $topics = explode(",", $parameters['topics']);
            $topics = array_map('trim', $topics);

            foreach ($topics as $topic_name) {
                $topic = topic_provider::find_by_name($topic_name);
                $data['topics'][] = $topic->get_id();
            }
        }

        return $this->create_article($data);
    }

    /**
     * Create a article with a catalogue image.
     *
     * @param string $name
     * @param string $path
     * @param int $access
     * @param string $alttext
     */
    public function create_article_with_image(string $name, string $path, int $access, string $alttext = null) {
        global $CFG, $USER;

        $userid = $USER->id;
        $draft_id = file_get_unused_draft_itemid();

        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' =>  'This is an article'
                        ]
                    ],
                ]
            ]
        ];

        $article = article::create(
            [
                'format' => FORMAT_JSON_EDITOR,
                'content' => json_encode($doc),
                'timeview' => time_view::LESS_THAN_FIVE,
                'draft_id' => $draft_id,
                'name' => $name,
                'access' => $access
            ],
            $userid
        );

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        $record = $this->create_image_record_for_article($article->get_context_id(), $draft_id, $userid, $name);
        $sourcefile = $CFG->dirroot . $path;

        $file = $fs->create_file_from_pathname($record, $sourcefile);
        $url = \moodle_url::make_draftfile_url(
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        $doc['content'][] = [
            'type' => 'image',
            'attrs' => [
                'filename' => $file->get_filename(),
                'url' => $url->out(),
                'alttext' => $alttext
            ],
        ];
        $article->update([
            'content' => json_encode($doc),
            'draft_id' => $draft_id,
        ]);

        return $article;
    }
    /**
     * @param int $context_id
     * @param int $draft_id
     * @param int $user_id
     * @param string $name
     * @return stdClass
     */
    public function create_image_record_for_article(int $context_id, $draft_id, int $user_id, string $name): stdClass {

        $record = new \stdClass();
        $record->contextid = $context_id;
        $record->component = 'user';
        $record->filearea = 'draft';
        $record->itemid = $draft_id;
        $record->filename = "{$name}.png";
        $record->userid = $user_id;
        $record->filepath = '/';

        return $record;
    }
}