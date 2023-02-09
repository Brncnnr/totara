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
namespace container_workspace\loader\file;

use container_workspace\discussion\discussion;
use container_workspace\file\file;
use container_workspace\query\file\query;
use container_workspace\query\file\sort;
use container_workspace\workspace;
use core\json_editor\document;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\order;
use container_workspace\entity\workspace_discussion;
use totara_comment\comment;
use core\json_editor\node\image;

/**
 * Loader class for files within a workspace
 */
final class loader {

    /**
     * Set explicit page limit for files
     */
    const PAGE_LIMIT = 40;

    /**
     * loader constructor.
     * Preventing this class from construction
     */
    private function __construct() {
    }

    /**
     * Constructing the base builder which the fields selected are all the fields from
     * "ttr_files" table. Note that this builder will also try to fetch all the files from
     * comment table usage as well.
     *
     * By default $include_alt_text will be set to false, in order to help us optimize the SQL to
     * not include unncessary fields. However the $query for files will always include this.
     *
     * @param int   $workspace_id
     * @param bool  $include_alt_text
     * @return builder
     */
    private static function get_base_builder(int $workspace_id, bool $include_alt_text = false): builder {
        // The SQL for fetching files will look something similar to:
        // SELECT *
        // FROM (
        //     SELECT f.*, wd.user_id as user_id
        //     FROM "ttr_files" f
        //              INNER JOIN "ttr_workspace_discussion" wd ON wd.id = f.itemid
        //         AND f.component = 'container_workspace'
        //         AND f.filearea = 'discussion'
        //     WHERE f.filename <> '.'
        //       AND f.mimetype IS NOT NULL
        //     UNION ALL
        //     SELECT f.*, tc.userid as user_id
        //     FROM "ttr_files" f
        //              INNER JOIN "ttr_totara_comment" tc ON tc.id = f.itemid
        //         AND f.component = 'totara_comment'
        //         AND (f.filearea = 'comment' OR f.filearea = 'reply')
        //              INNER JOIN "ttr_workspace_discussion" wd ON wd.id = tc.instanceid
        //         AND tc.component = 'container_workspace'
        //         AND tc.area = 'discussion'
        //     WHERE f.filename <> '.'
        //       AND f.mimetype IS NOT NULL
        // ) AS files
        $discussion_file_builder = builder::table('files', 'f');
        $discussion_file_builder->select([
            'f.*',
            'wd.user_id AS user_id',
        ]);

        $discussion_file_builder->join(
            [workspace_discussion::TABLE, 'wd'],
            function (builder $join) use ($workspace_id): void {
                $join->where_field('wd.id', 'f.itemid');
                $join->where('f.component', workspace::get_type());
                $join->where('f.filearea', discussion::AREA);
                $join->where('wd.course_id', $workspace_id);
            }
        );

        // This is a little bad, because we have to couple the component container_workspace to totara_comment
        // down to the database level, instead of API level.
        $comment_file_builder = builder::table('files', 'f');
        $comment_file_builder->select([
            'f.*',
            'tc.userid AS user_id'
        ]);

        $comment_file_builder->join(
            [comment::get_entity_table(), 'tc'],
            function (builder $join): void {
                $join->where_field('tc.id', 'f.itemid');
                $join->where('f.component', comment::get_component_name());
                $join->where_in('f.filearea', [comment::REPLY_AREA, comment::COMMENT_AREA]);
            }
        );

        $comment_file_builder->join(
            [workspace_discussion::TABLE, 'wd'],
            function (builder $join) use ($workspace_id): void {
                $join->where_field('wd.id', 'tc.instanceid');
                $join->where('tc.component', workspace::get_type());
                $join->where('tc.area', discussion::AREA);
                $join->where('wd.course_id', $workspace_id);
            }
        );

        if ($include_alt_text) {
            $discussion_file_builder->add_select([
                'wd.content AS content',
                'wd.content_format AS content_format'
            ]);

            $comment_file_builder->add_select([
                'tc.content AS content',
                'tc.format AS content_format'
            ]);
        }

        $discussion_file_builder->union_all($comment_file_builder);

        $builder = builder::table($discussion_file_builder, 'f');
        $builder->select('*');

        return $builder;
    }

    /**
     * @param query $query
     * @return offset_cursor_paginator
     */
    public static function get_files(query $query): offset_cursor_paginator {
        $workspace_id = $query->get_workspace_id();
        $incldue_alt_text = $query->is_including_alt_text();

        $builder = static::get_base_builder($workspace_id, $incldue_alt_text);
        $builder = $builder->join(['user', 'u'], 'u.id', 'f.user_id');

        // These fields are needed for the type resolver {@see core\webapi\resolver\type\user}.
        $builder->add_select([
            'u.email AS user_email',
            'u.imagealt AS user_imagealt',
            'u.picture AS user_picture'
        ]);

        $user_fields_sql = get_all_user_name_fields(true, 'u', null, 'user_');
        $builder->add_select_raw($user_fields_sql);
        $builder->results_as_arrays();

        $builder->map_to([static::class, 'create_file']);
        $builder->where('f.mimetype', '<>', NULL)
            ->where('f.filename', '<>', '.')
            ->where_not_null('f.mimetype');

        // Filter file format by extension.
        $extension = $query->get_extension();
        if (!empty($extension)) {
            $mimetype = static::get_mimetype_from_extension($extension);
            $builder->where('f.mimetype', $mimetype);

            // Look for the correct files based on extension to avoid this condition that when mimetype support two
            // extensions such as jpg and jpeg has same mimetype.
            $builder->where('f.filename', 'ilike_ends_with', $extension);
        }

        $sort = $query->get_sort();
        if (sort::is_recent($sort)) {
            $builder->order_by('f.timecreated', order::DIRECTION_DESC);

        } else if (sort::is_size($sort)) {
            $builder->order_by('f.filesize', order::DIRECTION_DESC);

        } else if (sort::is_name($sort)) {
            $builder->order_by('f.filename');
        }

        $cursor = $query->get_cursor();
        // set default items per page
        $cursor->set_limit(self::PAGE_LIMIT);
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Load the file extensions for query of extension.
     * @param int $workspace_id
     * @return array
     */
    public static function get_extensions(int $workspace_id): array {
        global $CFG;
        $builder = static::get_base_builder($workspace_id);
        $builder->select([
            'f.filename',
            'f.mimetype'
        ]);

        $builder->where_not_null('f.mimetype');
        $builder->where('f.filename', '<>', '.');

        require_once($CFG->dirroot . '/lib/filelib.php');

        $extensions = [];
        $records = $builder->fetch();

        foreach ($records as $record) {
            $type_group = file_get_typegroup('extension', $record->mimetype);

            // Check file extension in the mimetype.
            if (file_extension_in_typegroup($record->filename, $type_group)) {
                // Get file extension.
                $ext = pathinfo($record->filename, PATHINFO_EXTENSION);
                $key = strtolower($ext);

                if (!isset($extensions[$key])) {
                    $extensions[$key] = $ext;
                }
            }
        }

        ksort($extensions);
        return $extensions;
    }

    /**
     * Get specific mimetype by extension.
     * @param string $extension
     * @return string
     */
    private static function get_mimetype_from_extension(string $extension): string {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');

        $extension = strtolower($extension);
        $mimetypes = get_mimetypes_array();

        if (!in_array($extension, array_keys($mimetypes))) {
            throw new \coding_exception("The extension is not found within the system: '{$ext}'");
        }

        $index = array_search($extension, array_keys($mimetypes));
        return array_values($mimetypes)[$index]['type'];
    }

    /**
     * @internal
     *
     * @param array $record
     * @return file
     */
    public static function create_file(array $record): file {
        global $DB;

        // Constructing user -> which is the author who uploaded the files.
        $user_fields = get_all_user_name_fields(false, 'u', 'user_');
        $user_fields['email'] = 'user_email';
        $user_fields['id'] = 'user_id';
        $user_fields['imagealt'] = 'user_imagealt';
        $user_fields['picture'] = 'user_picture';

        $user = [];
        foreach ($user_fields as $field => $sql_field) {
            if (!array_key_exists($sql_field, $record)) {
                debugging("Cannot find field '{$sql_field}' from a row result of SQL", DEBUG_DEVELOPER);
                continue;
            }

            $user[$field] = $record[$sql_field];
        }

        // Constructing the file record.
        $file_columns = $DB->get_columns('files');
        $file_record = [];

        foreach ($file_columns as $column_name => $unused_info) {
            if (!array_key_exists($column_name, $record)) {
                debugging("Cannot find field '{$column_name}' from a row result of SQL", DEBUG_DEVELOPER);
                continue;
            }

            $file_record[$column_name] = $record[$column_name];
        }

        $fs = get_file_storage();

        $stored_file = $fs->get_file_instance((object) $file_record);
        $file = new file($stored_file, (object) $user);

        if (!array_key_exists('content_format', $record) || !array_key_exists('content', $record)) {
            // No content and content_format were included, so we will skip finding content.
            return $file;
        }

        if (FORMAT_JSON_EDITOR == $record['content_format']) {
            // If it is a json editor content then we can start extracting the alt_text from the content
            // and set it to file object.
            // Note that this can be really really slow if the content is huge with more like thousands of nodes.
            $document =  document::create($record['content']);
            $nodes = $document->find_nodes(image::get_type());
            $filename = $file->get_filename();

            /** @var image $node */
            foreach ($nodes as $node) {
                if ($node->get_filename() == $filename) {
                    // Found our match so we can break the loop and extract the alt_text value
                    // and set it into file.
                    $alt_text = $node->get_alt_text();
                    if (!empty($alt_text)) {
                        $file->set_alt_text($alt_text);
                    }

                    break;
                }
            }
        }

        return $file;
    }
}
