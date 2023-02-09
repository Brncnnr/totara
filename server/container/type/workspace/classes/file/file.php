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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\file;

use container_workspace\discussion\discussion;
use container_workspace\entity\workspace_discussion;
use container_workspace\workspace;
use moodle_url;
use stored_file;
use totara_comment\comment;

/**
 * Model class which is used for wrapping around the stored file and the discussion's id that introduce
 * this very file to the system.
 */
final class file {
    /**
     * @var stored_file
     */
    private $stored_file;

    /**
     * @var int
     */
    private $discussion_id;

    /**
     * The author of each file.
     * @var \stdClass
     */
    private $user;

    /**
     * The alternative text that is used for displaying image on the screen.
     * If this is null, then most likely the stored file is not either the image, or
     * the stored file does not provide one.
     *
     * @var string|null
     */
    private $alt_text;

    /**
     * file constructor.
     * @param stored_file   $stored_file
     * @param \stdClass     $user           The author of discussion.
     */
    public function __construct(stored_file $stored_file, \stdClass $user) {
       $this->stored_file = $stored_file;
       $this->user = $user;

       $this->alt_text = null;
       $this->discussion_id = null;
    }

    /**
     * @param string $value
     * @return void
     */
    public function set_alt_text(string $value): void {
        $this->alt_text = $value;
    }

    /**
     * @return string|null
     */
    public function get_alt_text(): ?string {
        return $this->alt_text;
    }

    /**
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        return $this->user;
    }

    /**
     * @return int
     */
    public function get_filesize(): int {
        return $this->stored_file->get_filesize();
    }

    /**
     * @return int
     */
    public function get_time_created(): int {
        return $this->stored_file->get_timecreated();
    }

    /**
     * @return int
     */
    public function get_time_modified(): int {
        return $this->stored_file->get_timemodified();
    }

    /**
     * @return string
     */
    public function get_filename(): string {
        return $this->stored_file->get_filename();
    }

    /**
     * @return string
     */
    public function get_extension(): string {
        return pathinfo($this->stored_file->get_filename(), PATHINFO_EXTENSION);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->stored_file->get_id();
    }

    /**
     * @return int
     */
    public function get_discussion_id(): int {
        global $DB;
        if (null !== $this->discussion_id && 0 !== $this->discussion_id) {
            return $this->discussion_id;
        }

        $component = $this->stored_file->get_component();
        $file_area = $this->stored_file->get_filearea();

        if (workspace::get_type() === $component && discussion::AREA === $file_area) {
            // Discussion's id will be stored in item id of the file.
            $this->discussion_id = $this->stored_file->get_itemid();
            return $this->discussion_id;
        }

        if (comment::get_component_name() === $component && comment::is_valid_area($file_area)) {
            // This should be fast, as it is only fetching the necessary field.
            $params = [
                'id' => $this->stored_file->get_itemid(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ];

            $this->discussion_id = $DB->get_field(
                comment::get_entity_table(),
                'instanceid',
                $params,
                MUST_EXIST
            );

            return $this->discussion_id;
        }

        throw new \coding_exception('File area and its component where this file is used is invalid');
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        global $DB;
        $discussion_id = $this->get_discussion_id();

        return $DB->get_field(
            workspace_discussion::TABLE,
            'course_id',
            ['id' => $discussion_id],
            MUST_EXIST
        );
    }

    /**
     * @param bool $force_download
     * @return moodle_url
     */
    public function get_file_url(bool $force_download = false): moodle_url {
        return moodle_url::make_pluginfile_url(
            $this->stored_file->get_contextid(),
            $this->stored_file->get_component(),
            $this->stored_file->get_filearea(),
            $this->stored_file->get_itemid(),
            $this->stored_file->get_filepath(),
            $this->stored_file->get_filename(),
            $force_download
        );
    }

    /**
     * @return moodle_url
     */
    public function get_file_url_without_download(): moodle_url {
        return $this->get_file_url();
    }

    /**
     * Returning the url that navigate us to the place that this file is being used.
     *
     * @return moodle_url
     */
    public function get_context_url(): moodle_url {
        $discussion_id = $this->get_discussion_id();

        return new moodle_url(
            "/container/type/workspace/discussion.php",
            ['id' => $discussion_id]
        );
    }

    /**
     * @return void
     */
    public function delete(): void {
        $this->stored_file->delete();
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->stored_file->get_component();
    }

    /**
     * @return string
     */
    public function get_file_area(): string {
        return $this->stored_file->get_filearea();
    }

    /**
     * @return string
     */
    public function get_mimetype(): string {
        return $this->stored_file->get_mimetype();
    }
}