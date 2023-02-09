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
namespace engage_article\formatter;

use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use engage_article\totara_engage\resource\article;
use totara_engage\formatter\field\date_field_formatter;

/**
 * Formatter for article
 */
final class article_formatter extends formatter {
    /**
     * article_formatter constructor.
     * @param article $article
     */
    public function __construct(article $article) {
        $record = new \stdClass();

        // Id here is the article's instanceid.
        $record->id = $article->get_instanceid();

        $record->content = $article->get_content();
        $record->format = $article->get_format();
        $record->resourceid = $article->get_id();
        $record->timecreated = $article->get_timecreated();
        $record->timemodified = $article->get_timemodified();
        parent::__construct($record, $article->get_context());
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('timedescription' === $field) {
            return parent::get_field('timecreated');
        }

        return parent::get_field($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('timedescription' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;

        return [
            'id' => null,
            'format' => null,
            'content' => function (?string $value, text_field_formatter $formatter) use($that): string {
                $textformat = $that->object->format;

                $formatter->set_additional_options(['formatter' => 'totara_tui']);
                $formatter->set_pluginfile_url_options(
                    $that->context,
                    'engage_article',
                    'content',
                    $that->object->resourceid
                );

                $formatter->set_text_format($textformat);
                return $formatter->format($value);
            },
            'timedescription' => function (int $value, date_field_formatter $formatter) use ($that): string {
                if (null !== $that->object->timemodified && 0 !== $that->object->timemodified) {
                    $formatter->set_timemodified($that->object->timemodified);
                }

                return $formatter->format($value);
            }
        ];
    }
}