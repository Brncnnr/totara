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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use engage_article\totara_engage\resource\article;

/**
 * Resolver for article draft content.
 */
class draft_item extends type_resolver {
    /**
     * A hash maps of the article id and the draft file id.
     * @var array
     */
    private static $maps;

    /**
     * @param string            $field
     * @param article           $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $CFG;

        if (!($source instanceof article)) {
            throw new \coding_exception("Invalid parameter source");
        }

        if (!isset(static::$maps)) {
            static::$maps = [];
        }

        switch ($field) {
            case 'content':
                $content = $source->get_content();
                require_once("{$CFG->dirroot}/lib/filelib.php");

                $context = $source->get_context();
                $draftid = null;

                $resource_id = $source->get_id();
                $content = file_prepare_draft_area(
                    $draftid,
                    $context->id,
                    article::get_resource_type(),
                    article::CONTENT_AREA,
                    $resource_id,
                    null,
                    $content
                );

                static::$maps[$resource_id] = $draftid;

                return $content;

            case 'resourceid':
                return $source->get_id();

            case 'format':
                return $source->get_format();

            case 'file_item_id':
                $resource_id = $source->get_id();
                if (isset(static::$maps[$resource_id])) {
                    return static::$maps[$resource_id];
                }

                require_once("{$CFG->dirroot}/lib/filelib.php");

                $context = $source->get_content();
                $draft_id = null;

                file_prepare_draft_area(
                    $draft_id,
                    $context->id,
                    article::get_resource_type(),
                    article::CONTENT_AREA,
                    $resource_id
                );

                static::$maps[$resource_id] = $draft_id;
                return $draft_id;

            default:
                debugging("Invalid field '{$field}' that is not existing", DEBUG_DEVELOPER);
                return null;
        }
    }
}