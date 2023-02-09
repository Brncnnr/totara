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

namespace mod_resource\webapi\resolver\query;

use core\webapi\query_resolver;
use core\webapi\middleware\require_login_course_via_module_instance;
use core\webapi\execution_context;
use coding_exception;

class resource extends query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        // Check that resourceid has been handed through and that the middleware has provided course info.
        if (empty($args['cm']) || empty($args['course']) || empty($args['resourceid'])) {
            throw new \coding_exception('Invalid resource (file) request');
        }

        // Get course module and course (provided by middleware)
        $cm = $args['cm'];
        $course = $args['course'];
        $context = \context_module::instance($cm->id);
        $ec->set_relevant_context($context);

        $resource = [];
        $resource['moduleinfo'] = $DB->get_record('resource', ['id' => $cm->instance]);

        // Note: This isn't very efficient so it's best to do once here in the query rather than once per related field in the type resolver.
        //       If there are multiple files, this should retrieve the same one you would see by visiting the view.php
        //       The real solution would be to stop people uploading multiple files to a single file resource, they should probably use a folder.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return null;
        } else {
            $resource['fileinfo'] = reset($files);
            unset($files);
        }

        return $resource;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course_via_module_instance('resource', 'resourceid')
        ];
    }
}
