<?php
/**
 * This file is part of Totara LMS
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
 * @package editor_weka
 */
namespace editor_weka\extension;

/**
 * Media extension includes web_image and video types.
 * @method static media create(array $options)
 */
final class media extends extension {
    /**
     * @return string
     */
    public function get_js_path(): string {
        return 'editor_weka/extensions/media';
    }

    /**
     * @return array
     */
    public function get_accepted_types(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        return file_get_typegroup('extension', ['web_image', 'web_video', 'web_audio']);
    }

    /**
     * @return array
     */
    public function get_js_parameters(): array {
        $params = parent::get_js_parameters();
        $params['accept_types'] = $this->get_accepted_types();

        return $params;
    }
}
