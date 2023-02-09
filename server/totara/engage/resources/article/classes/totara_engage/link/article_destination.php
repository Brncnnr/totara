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
 * @package engage_article
 */

namespace engage_article\totara_engage\link;

use core\format;
use engage_article\totara_engage\resource\article;
use moodle_url;
use totara_engage\formatter\resource_formatter;
use totara_engage\link\destination_generator;

/**
 * Build the link to the article page
 *
 * @package engage_article\totara_engage\link
 */
final class article_destination extends destination_generator {
    /**
     * @var array
     */
    protected $auto_populate = ['id'];

    /**
     * @return string
     */
    public function label(): string {
        $article = article::from_resource_id($this->attributes['id']);

        $resource_formatter = new resource_formatter($article);
        return get_string(
            'back_button',
            'engage_article',
            $resource_formatter->format('name', format::FORMAT_PLAIN)
        );
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        return new moodle_url('/totara/engage/resources/article/index.php');
    }
}