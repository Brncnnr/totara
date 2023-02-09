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
 * @package totara_core
 */
namespace totara_core\content;

use totara_core\content\processor\hashtag_processor;
use totara_core\content\processor\mention_processor;

final class content_handler {
    /**
     * @var processor[]
     */
    private $processors;

    /**
     * content_handler constructor.
     */
    private function __construct() {
        $this->processors = [];
    }

    /**
     * @return content_handler
     */
    public static function create(): content_handler {
        $handler = new static();
        $handler->load_processors();

        return $handler;
    }

    /**
     * @return void
     */
    public function load_processors(): void {
        if (!empty($this->processors)) {
            return;
        }

        // Default to have mention and hashtag processors
        $this->processors = [
            new mention_processor(),
            new hashtag_processor()
        ];

        $classes = \core_component::get_namespace_classes('totara_core\\content', processor::class);
        foreach ($classes as $cls) {
            $this->processors[] = new $cls();
        }
    }

    /**
     * @param content $item

     */
    public function handle(content $item): void {
        $format = $item->get_contentformat();
        if (null === $format) {
            $format = FORMAT_MOODLE;
        } else {
            $valids = [
                FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_HTML, FORMAT_JSON_EDITOR
            ];

            if (!in_array($format, $valids)) {
                throw new \coding_exception("Invalid format value '{$format}'");
            }
        }

        $this->load_processors();

        foreach ($this->processors as $processor) {
            switch ($format) {
                case FORMAT_PLAIN:
                    $processor->process_format_text($item);
                    break;

                case FORMAT_HTML:
                    $processor->process_format_html($item);
                    break;

                case FORMAT_JSON_EDITOR:
                    $processor->process_format_json_editor($item);
                    break;

                case FORMAT_MOODLE:
                default:
                    $processor->process_format_moodle($item);
                    break;
            }
        }
    }

    /**
     * This function will try to handle the content by the content's paramteter.
     * Internally, it will call to to {@see content_handler::handle()}
     *
     * @param string                    $title
     * @param string                    $content
     * @param int                       $contentformat
     * @param string                    $component
     * @param int|null                  $contextid
     * @param int|null                  $instanceid
     * @param string|null               $area
     * @param \moodle_url|string|null   $contexturl
     * @param int|null                  $user_id
     *
     * @return void
     */
    public function handle_with_params(string $title, string $content, int $contentformat, int $instanceid,
                                       string $component, string $area, ?int $contextid = null,
                                       $contexturl = null, ?int $user_id = null): void {
        $item = content::create(
            $title,
            $content,
            $contentformat,
            $instanceid,
            $component,
            $area,
            $contextid,
            $contexturl
        );

        if (!empty($user_id)) {
            $item->set_user_id($user_id);
        }

        $this->handle($item);
    }
}