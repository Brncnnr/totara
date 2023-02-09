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
 * @package engage_article
 */
namespace engage_article\webapi\resolver\mutation;

use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use engage_article\totara_engage\resource\article;
use totara_engage\access\access;
use totara_engage\exception\resource_exception;
use totara_engage\timeview\time_view;
use core\webapi\middleware\clean_editor_content;
use totara_engage\webapi\middleware\require_valid_recipients;

/**
 * Mutation resolver for engage_article_create
 */
class create extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return article
     */
    public static function resolve(array $args, execution_context $ec): article {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $article_data = [
            'name' => $args['name'],
            'topics' => $args['topics'] ?? [],
            'shares' => $args['shares'] ?? [],
            'draft_id' => $args['draft_id'] ?? null
        ];

        if (isset($args['access']) && !is_numeric($args['access']) && is_string($args['access'])) {
            // Format the string access into a proper value that machine can understand.
            $access = access::get_value($args['access']);
            $article_data['access'] = $access;
            $args['access'] = $access;
        }

        // By default for creation from front-end to here, it should be using JSON_EDITOR format.
        $article_data['format'] = FORMAT_JSON_EDITOR;
        if (isset($args['format'])) {
            $article_data['format'] = $args['format'];
        }

        if (isset($args['timeview'])) {
            $timeview = time_view::get_value($args['timeview']);
            $article_data['timeview'] = $timeview;
        }

        if (isset($args['content'])) {
            $content = $args['content'];
            $format = $article_data['format'];

            // Note: We don't want to put this kind of logic check within article's lower API because
            // we want to create an article with the very least content requirement in the lower API, so that it
            // it is easier to set up the environment when we write test.
            if ((FORMAT_JSON_EDITOR == $format && document_helper::is_document_empty($content)) || empty($content)) {
                throw resource_exception::create(
                    'create',
                    article::get_resource_type(),
                    null,
                    "The resource content is empty"
                );
            }

            $article_data['content'] = $args['content'];
        }

        /** @var article $resource */
        $resource = article::create($article_data, $USER->id);
        return $resource;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
            new require_valid_recipients('shares'),
            new clean_editor_content('content', 'format'),
            new clean_content_format('format', FORMAT_JSON_EDITOR, [FORMAT_JSON_EDITOR])
        ];
    }
}