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
namespace editor_weka\local;

final class media {
    /**
     * @var string
     */
    public const TYPE_VIDEO = 'VIDEO';

    /**
     * @var string
     */
    public const TYPE_IMAGE = 'IMAGE';

    /**
     * @var string
     */
    public const TYPE_AUDIO = 'AUDIO';

    /**
     * media constructor.
     * Prevent the construction
     */
    private function __construct() {
    }

    /**
     * @param string $mimetype
     * @return string
     */
    public static function get_media_type(string $mimetype): ?string {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        if (file_mimetype_in_typegroup($mimetype, ['web_image'])) {
            return static::TYPE_IMAGE;
        } else if (file_mimetype_in_typegroup($mimetype, ['web_video'])) {
            return static::TYPE_VIDEO;
        } else if (file_mimetype_in_typegroup($mimetype, ['web_audio'])) {
            return static::TYPE_AUDIO;
        }

        // Invalid mimetype.
        return null;
    }
}