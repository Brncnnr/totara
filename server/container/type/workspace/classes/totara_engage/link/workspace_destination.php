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
 * @package container_workspace
 */

namespace container_workspace\totara_engage\link;

use container_workspace\formatter\workspace\formatter;
use container_workspace\workspace;
use core\format;
use moodle_url;
use totara_engage\link\destination_generator;

/**
 * Build the link to the workspace page
 *
 * @package container_workspace\totara_engage\link
 */
final class workspace_destination extends destination_generator {
    /**
     * Open the discussions tab
     */
    const TAB_DISCUSSIONS = 0;

    /**
     * Open the library tab
     */
    const TAB_LIBRARY = 1;

    /**
     * Open the members tab
     */
    const TAB_MEMBERS = 2;

    /**
     * @var array
     */
    protected $auto_populate = ['id'];

    /**
     * @return string
     */
    public function label(): string {
        $id = $this->attributes['id'] ?? null;
        if (!$id) {
            return parent::label();
        }

        $workspace = workspace::from_id($this->attributes['id']);
        $formatter = new formatter($workspace);
        return get_string(
            'back_button',
            'container_workspace',
            $formatter->format('name', format::FORMAT_PLAIN)
        );
    }

    /**
     * @return $this
     */
    public function tab_library(): workspace_destination {
        $this->set_attribute('tab', self::TAB_LIBRARY);
        return $this;
    }

    /**
     * @return $this
     */
    public function tab_discussions(): workspace_destination {
        $this->set_attribute('tab', self::TAB_DISCUSSIONS);
        return $this;
    }

    /**
     * @return $this
     */
    public function tab_members(): workspace_destination {
        $this->set_attribute('tab', self::TAB_MEMBERS);
        return $this;
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        return new moodle_url('/container/type/workspace/workspace.php');
    }

    /**
     * @param array $attributes
     * @param moodle_url $url
     */
    protected function add_custom_url_params(array $attributes, moodle_url $url): void {
        // Attach our library view if we want it
        if (!empty($attributes['tab'])) {
            switch ($attributes['tab']) {
                case self::TAB_LIBRARY:
                    $url->param('tab', 'library');
                    break;

                case self::TAB_MEMBERS:
                    $url->param('tab', 'members');
                    break;

                case self::TAB_DISCUSSIONS:
                    $url->param('tab', 'discussions');
                    break;
            }
        }
    }
}