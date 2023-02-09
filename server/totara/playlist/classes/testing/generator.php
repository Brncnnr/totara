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

namespace totara_playlist\testing;

use stdClass, coding_exception, context;
use totara_engage\rating\rating_manager;
use totara_engage\share\shareable;
use totara_playlist\local\image_processor\contract as image_processor_contract;
use totara_playlist\playlist;
use totara_engage\access\access;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;
use totara_engage\share\recipient\recipient;
use core_user\totara_engage\share\recipient\user as user_recipient;
use totara_topic\provider\topic_provider;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;

final class generator extends \core\testing\component_generator {
    /**
     * @param array|stdClass $parameters
     * @return playlist
     */
    public function create_playlist($parameters = []): playlist {
        if ($parameters instanceof stdClass) {
            $parameters = get_object_vars($parameters);
        }

        if (!is_array($parameters)) {
            throw new \coding_exception("Invalid argument \$parameters");
        }

        if (isset($parameters['name'])) {
            $name = $parameters['name'];
        } else {
            if (\core_text::strlen($this->get_random_name()) > 75) {
                $name = \core_text::substr($this->get_random_name(), 0, 75);
            } else {
                $name = $this->get_random_name();
            }
        }

        $access = access::PRIVATE;
        if (isset($parameters['access'])) {
            $access = $parameters['access'];
        }

        $contextid = null;
        if (isset($parameters['contextid'])) {
            $contextid = $parameters['contextid'];
        }

        $userid = null;
        if (isset($parameters['userid'])) {
            $userid = $parameters['userid'];
        }

        $summary_format = $parameters['summaryformat'] ?? FORMAT_JSON_EDITOR;
        $summary = null;

        if (isset($parameters['summary'])) {
            $summary = $parameters['summary'];
            if (FORMAT_JSON_EDITOR == $summary_format && !document_helper::looks_like_json($summary)) {
                $summary = json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text($summary)]
                ]);
            }
        }

        $playlist = playlist::create($name, $access, $contextid, $userid, $summary, $summary_format);

        if (isset($parameters['topics']) && !empty($parameters['topics'])) {
            $playlist->add_topics_by_ids($parameters['topics']);
        }

        return $playlist;
    }

    /**
     * @param array|stdClass $parameters
     * @return playlist
     */
    public function create_public_playlist($parameters = []): playlist {
        if ($parameters instanceof stdClass) {
            $parameters = get_object_vars($parameters);
        }

        $parameters['access'] = access::PUBLIC;
        return $this->create_playlist($parameters);
    }

    /**
     * @return string
     */
    private function get_random_name(): string {
        $items = [
            'Security',
            'Kali linux',
            'How to Python - 102',
            'How to Python - 101',
            'How to deal with your HR',
            'How to cook Ruby ?',
            'How to programming with Python - 201',
            'Gaming industries ?',
            'How to avoid your HR ?',
            'How to REKT your machine ?',
            'God of War is awesome !'
        ];

        $nb = rand(0, (count($items) - 1));
        return $items[$nb];
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
     * @param shareable $playlist
     * @param recipient[] $recipients
     * @return share_model[]
     */
    public function share_playlist(shareable $playlist, array $recipients): array {
        $context = $playlist->get_context();

        // Make the create method public so we can test it.
        $class = new \ReflectionClass(share_manager::class);
        $method = $class->getMethod('create');
        $method->setAccessible(true);

        return $method->invokeArgs(null, [
            $playlist->get_id(),
            $playlist->get_userid(),
            playlist::get_resource_type(),
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
            // Can share a playlist.
            role_assign($role->id, $userid, $context->id);
            assign_capability('totara/playlist:share', $permission, $role->id, $context, true);
        }
    }

    /**
     * Callback from behat data generator.
     *
     * @param array $parameters
     * @return playlist
     */
    public function create_playlist_from_params(array $parameters): playlist {
        global $DB;

        if (!isset($parameters['name']) || !isset($parameters['username'])) {
            throw new \coding_exception(
                "Cannot create playlist from parameters that does not have user name and the name itself"
            );
        }

        $user_id = $DB->get_field('user', 'id', ['username' => $parameters['username']]);
        $access = access::get_value($parameters['access']);

        if (access::is_public($access) && empty($parameters['topics'])) {
            throw new \coding_exception("Cannot create public playlist without the topics");
        }

        $data = [
            'userid' => $user_id,
            'access' => $access,
            'contextid' => \context_user::instance($user_id)->id,
            'name' => $parameters['name'],
            'summary' => isset($parameters['summary']) ? $parameters['summary'] : null,

            // List of topic's id.
            'topics' => []
        ];

        if (!empty($parameters['topics'])) {
            $topics = explode(",", $parameters['topics']);
            $topics = array_map('trim', $topics);

            foreach ($topics as $topic_name) {
                $topic = topic_provider::find_by_name($topic_name);
                if (null === $topic) {
                    debugging("Cannot find topic by name '{$topic_name}'", DEBUG_DEVELOPER);
                    continue;
                }

                $data['topics'][] = $topic->get_id();
                $data['topics'][] = $topic->get_id();
            }
        }

        return $this->create_playlist($data);
    }

    /**
     * @param playlist $playlist
     * @param int $rating
     * @param int|null $user_id
     */
    public function add_rating(playlist $playlist, int $rating, ?int $user_id = null): void {
        global $USER;
        if (null == $user_id) {
            $user_id = $USER->id;
        }

        $manager = rating_manager::instance(
            $playlist->get_id(),
            'totara_playlist',
            $playlist::RATING_AREA
        );

        if (!$manager->can_rate($playlist->get_userid())) {
            throw new \coding_exception("Current user with id '{$playlist->get_userid()}' can not rate the playlist");
        }

        $manager->add($rating, $user_id);
    }

    /**
     * Behat step for creating playlist resources.
     *
     * @param array $parameters
     * @return void
     */
    public function create_playlist_resouce_from_params(array $parameters): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/totara/playlist/tests/behat/behat_totara_playlist.php");

        $playlist = \behat_totara_playlist::get_item_by_name($parameters['playlist']);

        [$plugin_type, $plugin_name] = \core_component::normalize_component($parameters['component']);
        $directory = \core_component::get_plugin_directory($plugin_type, $plugin_name);

        $behat_class = "behat_{$parameters['component']}";
        $file = "{$directory}/tests/behat/{$behat_class}.php";

        if (!file_exists($file)) {
            throw new \coding_exception("Unable to located behat file '{$file}'");
        }

        require_once($file);
        if (!method_exists($behat_class, "get_item_by_name")) {
            throw new \coding_exception("Function '{$behat_class}::get_item_by_name' does not exist");
        }

        $resource = call_user_func([$behat_class, 'get_item_by_name'], $parameters['name']);

        if (isset($parameters['user'])) {
            $user = \core_user::get_user_by_username($parameters['user'], '*', null, MUST_EXIST);
            $actor_id = $user->id;
        } else {
            // Not all the times $USER is being set.
            $actor_id = $USER->id;
        }

        $playlist->add_resource($resource, $actor_id);
    }

    /**
     * @return image_processor_contract
     */
    public function get_mock_image_processor(): image_processor_contract {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/playlist/tests/fixtures/mock_image_processor.php");

        return new \mock_playlist_image_processor();
    }

    /**
     * @param array $parameters
     * @return playlist
     */
    public function create_restricted_playlist($parameters = []): playlist {
        if (is_object($parameters)) {
            $parameters = (array) $parameters;
        }

        $parameters['access'] = access::RESTRICTED;
        return $this->create_playlist($parameters);
    }
    /**
     * Create a workspace with a catalogue image.
     *
     * @param string $name
     * @param int $access
     * @throws coding_exception
     */
    public function create_playlist_with_image(string $name, int $access) {
        global $USER;
        $userid = $USER->id;

        $article_generator = \engage_article\testing\generator::instance();

        $article1 = $article_generator->create_article_with_image('totara1','/totara/playlist/tests/fixtures/red.png', 1);
        $article2 = $article_generator->create_article_with_image('totara2','/totara/playlist/tests/fixtures/yellow.png', 1);
        $article3 = $article_generator->create_article_with_image('totara3','/totara/playlist/tests/fixtures/blue.png', 1);
        $article4 = $article_generator->create_article_with_image('totara4','/totara/playlist/tests/fixtures/green.png', 1);

        $playlist = \totara_playlist\playlist::create(
            $name,
            $access,
            $contextid = null,
            $userid
        );

        $playlist->add_resource($article1, $userid);
        $playlist->add_resource($article2, $userid);
        $playlist->add_resource($article3, $userid);
        $playlist->add_resource($article4, $userid);

        return $playlist;
    }
}
