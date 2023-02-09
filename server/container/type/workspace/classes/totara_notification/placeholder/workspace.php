<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\totara_notification\placeholder;

use coding_exception;
use container_workspace\workspace as workspace_instance;
use html_writer;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

/**
 * Workspace placeholder for totara_notification
 */
class workspace extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var workspace_instance|null
     */
    protected $workspace;

    /**
     * @param workspace_instance|null $workspace
     */
    public function __construct(?workspace_instance $workspace) {
        $this->workspace = $workspace;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        $instance = self::get_cached_instance($id);
        if (!$instance) {
            try {
                $workspace = workspace_instance::from_id($id);
            } catch (\dml_missing_record_exception $ex) {
                $workspace = null;
            }
            $instance = new static($workspace);
            self::add_instance_to_cache($id, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create(
                'full_name',
                get_string('placeholder_workspace_fullname', 'container_workspace')
            ),
            option::create(
                'full_name_link',
                get_string('placeholder_workspace_fullname_linked', 'container_workspace')
            ),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('full_name_link' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->workspace === null) {
            throw new coding_exception("The workspace record is empty");
        }

        switch ($key) {
            case 'full_name':
                return $this->workspace->get_name();
            case 'full_name_link':
                $url = new moodle_url('/container/type/workspace/workspace.php', ['id' => $this->workspace->get_id()]);
                return html_writer::link($url, format_string($this->workspace->get_name()));
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->workspace !== null;
    }

}