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
 * @package totara_topic
 */
namespace totara_topic\output;

use core\output\template;
use moodle_url;

/**
 * This output componen is used for rendering a box of action icons within a column
 * of a report build. It allows us to use the AMD modules for the interactions at FRONT-END
 */
final class report_actions extends template {
    /**
     * @param moodle_url|null $deleteurl
     * @param moodle_url|null $updateurl
     * @param int             $totalusage
     *
     * @return report_actions
     */
    public static function create(
        ?moodle_url $deleteurl,
        ?moodle_url $updateurl,
        int $totalusage = 0
    ): report_actions {
        $data = [
            'deleteurl' => null,
            'updateurl' => null,
            'delete_url_title' => null,
            'update_url_title' => null,
            'hasusage' => false,
            'message' => null,
            'sesskey' => sesskey()
        ];

        if (0 < $totalusage) {
            $data['hasusage'] = true;
            $data['message'] = get_string('confirmdeletewithusage', 'totara_topic', $totalusage);
        }

        $widget = new static($data);

        $widget->set_delete_url($deleteurl);
        $widget->set_update_url($updateurl);

        return $widget;
    }

    /**
     * @param moodle_url|null $delete_url
     * @return void
     */
    public function set_delete_url(?moodle_url $delete_url): void {
        if (null === $delete_url) {
            $this->data['deleteurl'] = null;
            return;
        }

        $this->data['deleteurl'] = $delete_url->out(false);
    }

    /**
     * @param moodle_url|null $update_url
     * @return void
     */
    public function set_update_url(?moodle_url $update_url): void {
        if (null === $update_url) {
            $this->data['updateurl'] = null;
            return;
        }

        $this->data['updateurl'] = $update_url->out(false);
    }

    /**
     * @param string $title
     * @return void
     */
    public function set_delete_url_title(string $title): void {
        $this->data['delete_url_title'] = $title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function set_update_url_title(string $title): void {
        $this->data['update_url_title'] = $title;
    }
}