<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_resource
 */

namespace mod_resource\webapi\resolver\type;

use core\webapi\execution_context;
use mod_resource\webapi\formatter\resource_formatter;

/**
 * Basic resource (file) details type
 */
class resource extends \core\webapi\type_resolver {
    public static function resolve(string $field, $resource, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        // Note: mdl_resource record called moduleinfo so it doesn't get confused with a modinfo class.
        if (!isset($resource['moduleinfo']) || !$resource['moduleinfo'] instanceof \stdclass) {
            throw new \coding_exception('Resource file type resolver did not receive expected data');
        }
        $moduleinfo = $resource['moduleinfo'];

        if (!isset($resource['fileinfo']) || !$resource['fileinfo'] instanceof \stored_file) {
            throw new \coding_exception('Resource file type resolver did not receive expected data');
        }
        $fileinfo = $resource['fileinfo'];

        $context = $ec->get_relevant_context();
        $format = $args['format'] ?? null;

        if ($field == 'mimetype') {
            return $fileinfo->get_mimetype();
        }

        if ($field == 'size') {
            return $fileinfo->get_filesize();
        }

        if ($field == 'fileurl') {
            $revision = $moduleinfo->revision;
            $path = '/' . $fileinfo->get_contextid() . '/mod_resource/content/' . $revision . $fileinfo->get_filepath() . $fileinfo->get_filename();
            $moduleinfo->fileurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
        }

        $formatter = new resource_formatter($moduleinfo, $context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['fileurl'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }

        return $formatted;
    }
}
