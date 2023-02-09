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
 * @package container_workspace
 */

namespace container_workspace\testing;

use stdClass, coding_exception;
use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\local\workspace_helper;
use container_workspace\member\member;
use container_workspace\totara_engage\share\recipient\library as library_recipient;
use container_workspace\workspace;
use core\json_editor\node\attachments;
use core\json_editor\node\paragraph;
use core\json_editor\helper\document_helper;
use core_container\container_category_helper;

/**
 * Generator for container workspace
 */
final class generator extends \core\testing\component_generator {
    /**
     * @var array
     */
    private static $names;

    /**
     * @var array
     */
    private static $discussions;

    /**
     * @param string|null   $name
     * @param string|null   $summary
     * @param int|null      $summary_format
     * @param int|null      $owner
     * @param bool          $is_private
     * @param bool          $is_hidden
     * @param int|null      $draft_id
     * @param int|null      $category_id Set when directly dealing with multi-tenancy categories
     * @return workspace
     */
    public function create_workspace(?string $name = null, ?string $summary = null,
                                     ?int $summary_format = null, ?int $owner = null,
                                     bool $is_private = false, bool $is_hidden = false,
                                     ?int $draft_id = null, ?int $category_id = null): workspace {
        global $USER;

        if (null === $name || '' === $name) {
            if (\core_text::strlen($this->random_name()) > 75) {
                $name = \core_text::substr($this->random_name(), 0, 75);
            } else {
                $name = $this->random_name();
            }
        }

        if (null === $summary_format) {
            $summary_format = FORMAT_PLAIN;
        }

        if (null === $owner || 0 === $owner) {
            $owner = $USER->id;
        }

        return workspace_helper::create_workspace(
            $name,
            $owner,
            $category_id,
            $summary,
            $summary_format,
            $draft_id,
            $is_private,
            $is_hidden
        );
    }

    /**
     * @param string|null   $name
     * @param string|null   $summary
     * @param string|null   $summary_format
     * @param int|null      $owner
     *
     * @return workspace
     */
    public function create_private_workspace(?string $name = null, ?string $summary = null,
                                             ?string $summary_format = null, ?int $owner = null): workspace {
        return $this->create_workspace(
            $name,
            $summary,
            $summary_format,
            $owner,
            true
        );
    }

    /**
     * @param string|null   $name
     * @param string|null   $summary
     * @param string|null   $summary_format
     * @param int|null      $owner
     *
     * @return workspace
     */
    public function create_hidden_workspace(?string $name = null, ?string $summary = null,
                                            ?string $summary_format = null, ?int $owner = null): workspace {
        return $this->create_workspace(
            $name,
            $summary,
            $summary_format,
            $owner,
            true,
            true
        );
    }

    /**
     * @return string
     */
    private function random_name(): string {
        global $CFG;

        if (!isset(static::$names)) {
            static::$names = [];
            static::$names = require(
                "{$CFG->dirroot}/container/type/workspace/tests/fixtures/workspace_names.php"
            );
        }

        $nb = rand(0, (count(static::$names) - 1));
        return static::$names[$nb];
    }

    /**
     * @return string
     */
    private function random_discussion_content(): string {
        global $CFG;

        if (!isset(static::$discussions)) {
            static::$discussions = [];
            static::$discussions = require(
                "{$CFG->dirroot}/container/type/workspace/tests/fixtures/discussions.php"
            );
        }

        $nb = rand(0, (count(static::$discussions) - 1));
        return static::$discussions[$nb];
    }

    /**
     * @param int $workspace_id
     * @param string|null $content
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return discussion
     */
    public function create_discussion(int $workspace_id, ?string $content = null, ?int $draft_id = null,
                                      ?int $content_format = null, ?int $actor_id = null): discussion {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $workspace = workspace::from_id($workspace_id);
        $interactor = new workspace_interactor($workspace, $actor_id);

        if (!$interactor->is_joined()) {
            // The actor has not yet joined the workspace yet, time to join the workspace automatically so that
            // we can generate the discussion easily.
            member::join_workspace($workspace, $actor_id);
        }

        if (null === $content_format) {
            $content_format = FORMAT_PLAIN;
        }

        if (null === $content || "" === $content) {
            $content = $this->random_discussion_content();
        }

        if (FORMAT_JSON_EDITOR == $content_format && !document_helper::looks_like_json($content)) {
            // $content is not a json document content.
            $content = json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text($content)
                ],
            ]);
        }

        return discussion_helper::create_discussion(
            $workspace,
            $content,
            $draft_id,
            $content_format,
            $actor_id
        );
    }

    /**
     * Callback from behat data generator.
     *
     * @param array $parameters
     * @return workspace
     */
    public function create_workspace_from_params(array $parameters): workspace {
        if (!isset($parameters['name']) || empty($parameters['owner'])) {
            throw new coding_exception(
                "Workspace name and owner are required"
            );
        }

        $user = \core_user::get_user_by_username($parameters['owner']);

        return $this->create_workspace(
            $parameters['name'],
            $parameters['summary'] ?? null,
            null,
            $user->id,
            $parameters['private'] ?? false,
            $parameters['hidden'] ?? false
        );
    }

    /**
     * @param workspace[] $workspaces
     * @return array
     */
    public function create_workspace_recipients(array $workspaces): array {
        $recipients = [];
        foreach ($workspaces as $workspace) {
            $recipients[] = new library_recipient($workspace->id);
        }
        return $recipients;
    }

    /**
     * Behat helper to add a user to a workspace
     *
     * @param array $parameters
     * @return void
     */
    public function create_workspace_owners(array $parameters): void {
        global $DB;

        if (empty($parameters['username']) || empty($parameters['workspace'])) {
            throw new coding_exception('`workspace` and `username` are required');
        }

        $user = \core_user::get_user_by_username($parameters['username']);
        $workspace_id = $DB->get_field('course', 'id', ['shortname' => strtolower($parameters['workspace'])]);
        $workspace = workspace::from_id($workspace_id);

        $roles = get_archetype_roles('workspaceowner');
        if (empty($roles)) {
            throw new coding_exception("No role for archetype 'workspaceowner'");
        }
        $role = reset($roles);

        $manager = $workspace->get_enrolment_manager();
        $manager->self_enrol_user($user->id, $role->id);
    }

    /**
     * Behat generator to create discussions in a workspace, optionally with attached files.
     *
     * @param array $parameters
     * @return void
     */
    public function create_discussions(array $parameters): void {
        global $DB, $CFG;

        if (empty($parameters['username']) || empty($parameters['workspace'] || empty($parameters['content']))) {
            throw new coding_exception('`workspace` and `username` are required');
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");

        $user = \core_user::get_user_by_username($parameters['username']);
        $workspace_id = $DB->get_field('course', 'id', ['shortname' => strtolower($parameters['workspace'])]);

        $content = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text($parameters['content'] ?? 'Discussion'),
            ]
        ];

        $discussion = discussion::create(json_encode($content), $workspace_id, null, FORMAT_JSON_EDITOR, $user->id);
        if (empty($parameters['files'])) {
            return;
        }

        $files = explode(',', $parameters['files']);
        $fs = get_file_storage();

        // Store the files directly against the discussion, bypassing the draft feature.
        // We don't want to store in the user draft as the core api uses the session $USER which
        // conflicts with this generator.
        $stored_files = [];
        foreach ($files as $file_path) {
            $path = $CFG->dirroot . '/container/type/workspace/tests/fixtures/' . trim($file_path);
            $record = [
                'component' => 'container_workspace',
                'filearea' => 'discussion',
                'contextid' => $discussion->get_context()->id,
                'itemid' => $discussion->get_id(),
                'filename' => basename(trim($file_path)) ?? 'file.txt',
                'filepath' => '/'
            ];
            $stored_files[] = $fs->create_file_from_pathname($record, $path);
        }

        $content['content'][] = attachments::create_raw_node_from_list($stored_files);

        // Update the discussion content to include the attached files. We have to prevent the update_content
        // call from deleting the files during the update as it likes to clean up things.
        $discussion->set_prevent_delete_files_on_update(true);
        $discussion->update_content(json_encode($content), null, FORMAT_JSON_EDITOR, $user->id);
    }

    /**
     * @param int $permission
     * @param int $userid
     */
    public function set_capabilities(int $permission, int $userid) {
        $roles = get_archetype_roles('user');
        foreach ($roles as $role) {
            // Can create workspace.
            $user_context = \context_user::instance($userid, MUST_EXIST);
            assign_capability('container/workspace:create', $permission, $role->id, $user_context, true);
        }
    }

    /**
     * @param workspace $workspace
     * @param int $user_id
     * @return member
     */
    public function create_self_join_member(workspace $workspace, int $user_id): member {
        return member::join_workspace($workspace, $user_id);
    }

    /**
     * @param workspace $workspace
     * @param int       $target_user_id
     * @param int|null  $actor_id
     * @return member
     */
    public function add_member(workspace $workspace, int $target_user_id, ?int $actor_id = null): member {
        return member::added_to_workspace($workspace, $target_user_id, false, $actor_id);
    }

    /**
     * @param array $parameters
     * @return \coursecat
     */
    public function create_category(array $parameters = []): \coursecat {
        global $DB;

        $parent_category_id = 0;
        if (isset($parameters['tenant_id'])) {
            $parent_category_id = $DB->get_field(
                'tenant',
                'categoryid',
                ['id' => $parameters['tenant_id']]
            );
        }

        return container_category_helper::create_container_category(
            workspace::get_type(),
            $parent_category_id,
            $parameters['id_number'] ?? uniqid('id_number_'),
            $parameters['name'] ?? null
        );
    }

    /**
     * By default, we don't allow workspace to be moved around the category.
     * This generator helper function is to help us by pass those logic rules from workspace's API,
     * so that we can test different scenarios. And this helper function should only be used in
     * PHPUNIT environment.
     *
     * @param workspace $workspace
     * @param int       $category_id
     *
     * @return void
     */
    public function move_workspace_to_category(workspace $workspace, int $category_id): void {
        global $DB;

        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception("Cannot move the workspace category outside of phpunit environment");
        }

        // Update the course's record manually.
        $course_record = new stdClass();
        $course_record->id = $workspace->id;
        $course_record->category = $category_id;

        $DB->update_record('course', $course_record);

        // Update the move of workspace's category context
        $new_parent = \context_coursecat::instance($category_id);
        $workspace_context = $workspace->get_context();

        $workspace_context->update_moved($new_parent);

        \cache_helper::purge_by_event('changesincoursecat');
        $workspace->rebuild_cache(true);
    }
}