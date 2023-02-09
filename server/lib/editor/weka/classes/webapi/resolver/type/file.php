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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use editor_weka\local\media;
use moodle_url;
use stored_file;

/**
 * Type resolver for editor's files.
 */
class file extends type_resolver {
    /**
     * @param string            $field
     * @param stored_file      $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        if (!($source) instanceof stored_file) {
            throw new coding_exception("Invalid stored file");
        }

        switch ($field) {
            case 'filename':
                return $source->get_filename();

            case 'file_size':
                return $source->get_filesize();

            case 'mime_type':
                return $source->get_mimetype();

            case 'url':
                $force_download = false;
                if (isset($args['force_download'])) {
                    $force_download = $args['force_download'];
                }

                $component = $source->get_component();
                $area = $source->get_filearea();

                if ('user' === $component && 'draft' === $area) {
                    // Draft area files. Time to give draft url.
                    $moodle_url = moodle_url::make_draftfile_url(
                        $source->get_itemid(),
                        '/',
                        $source->get_filename(),
                        $force_download
                    );

                    return $moodle_url->out();
                }

                $moodle_url = moodle_url::make_pluginfile_url(
                    $source->get_contextid(),
                    $component,
                    $area,
                    $source->get_itemid(),
                    '/',
                    $source->get_filename(),
                    $force_download
                );

                return $moodle_url->out();

            case 'media_type':
                $mime_type = $source->get_mimetype();
                return media::get_media_type($mime_type);

            case 'item_id':
                return $source->get_itemid();

            default:
                debugging("The field '{$field}' is not yet supported", DEBUG_DEVELOPER);
                return null;
        }
    }
}