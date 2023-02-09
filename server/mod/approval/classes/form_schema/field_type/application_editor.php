<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\form_schema\field_type;

use coding_exception;
use context;
use core\entity\user;
use core\format;
use core\orm\query\builder;
use core\webapi\formatter\field\text_field_formatter;
use core_config;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use stdClass;

/**
 * This class handles form schema field editor.
 * Modifies the value for rendering and storing.
 * Also handles permissions check of files being served from this file area.
 */
class application_editor {
    /**
     * Editor file component.
     *
     * @var string
     */
    public const FILE_COMPONENT = 'mod_approval';

    /**
     * Editor file area.
     *
     * @var string
     */
    public const FILE_AREA = 'application';

    /**
     * Editor variant.
     *
     * @var string
     */
    public const VARIANT = 'basic';

    /**
     * Form schema field type.
     *
     * @var string
     */
    public const FIELD_TYPE = 'editor';

    /**
     * Application interactor instance.
     *
     * @var application_interactor
     */
    private $application_interactor;

    /**
     * Global config.
     *
     * @var core_config
     */
    private $cfg;

    /**
     * application_editor constructor.
     *
     * @param application_interactor $application_interactor
     */
    public function __construct(application_interactor $application_interactor) {
        global $CFG;
        $this->application_interactor = $application_interactor;
        $this->cfg = $CFG;
    }

    /**
     * Load by application id.
     *
     * @param int $application_id
     * @param user $user
     * @return application_editor
     */
    public static function by_application_id(int $application_id, user $user): application_editor {
        $application_interactor = application_interactor::from_application_id($application_id, $user->id);

        return new self($application_interactor);
    }

    /**
     * Serve the files for the form_schema editor.
     *
     * @param stdClass $context the context
     * @param string $file_area the name of the file area
     * @param array $args extra arguments (file_path AND/OR file_name)
     * @param array $options additional options affecting the file serving
     *
     * @return false|void false if the file not found, just send the file otherwise and do not return anything
     */
    public function serve_file($context, string $file_area, array $args, array $options = array()) {
        if ($file_area !== self::FILE_AREA || !$this->application_interactor->can_view()) {
            return false;
        }
        $file_name = array_pop($args);
        $file_path = DIRECTORY_SEPARATOR;

        if ($args) {
            $file_path .= implode(DIRECTORY_SEPARATOR, $args). DIRECTORY_SEPARATOR;
        }

        $file = get_file_storage()->get_file(
            $context->id,
            self::FILE_COMPONENT,
            self::FILE_AREA,
            $this->application_interactor->get_application()->id,
            $file_path,
            $file_name
        );

        if (!$file) {
            return false;
        }

        send_stored_file($file, DAYSECS, 0, true, $options);
    }

    /**
     * Provides the value in different formats.
     *
     * @param string|null $field_value
     * @return array
     */
    public function set_value_formats(?string $field_value): array {
        $decoded_value = is_null($field_value) ? null : json_decode($field_value);
        $empty_field_value = empty($decoded_value) || empty($decoded_value->content);

        return [
            'editor' => $empty_field_value
                ? null
                : $this->convert_to_plugin_urls($decoded_value),
            'html' => $empty_field_value
                ? null
                : $this->format_html($decoded_value),
            'plain' => $empty_field_value
                ? null
                : $this->format_plain($decoded_value),
        ];
    }

    /**
     * Converts database saved placeholder @@PLUGINFILE@@ to right file handler.
     *
     * @param stdClass $value
     * @return string
     */
    private function convert_to_plugin_urls(stdClass $value): string {
        $value->content = file_rewrite_pluginfile_urls(
            $value->content,
            'pluginfile.php',
            $this->application_interactor->get_application()->context->id,
            self::FILE_COMPONENT,
            self::FILE_AREA,
            $this->application_interactor->get_application()->id
        );

        return json_encode($value);
    }

    /**
     * Formats the value to html. This would include attached files.
     *
     * @param stdClass $value
     * @return string
     */
    private function format_html(stdClass $value): string {
        $application = $this->application_interactor->get_application();

        return self::get_text_formatter($application->context, $application->id, format::FORMAT_HTML, $value->format)->format($value->content);
    }

    /**
     * Formats the value to plain text. This would not include attached files.
     *
     * @param stdClass $value {format, content}
     * @return string
     */
    private function format_plain(stdClass $value): string {
        $application = $this->application_interactor->get_application();

        return self::get_text_formatter($application->context, $application->id, format::FORMAT_PLAIN, $value->format)->format($value->content);
    }

    /**
     * Get an instance of the text formatter for a specified format.
     *
     * @param context $context
     * @param int $application_id
     * @param string $format
     * @param string|null $text_format
     * @return text_field_formatter
     */
    public static function get_text_formatter(context $context, int $application_id, string $format, ?string $text_format): text_field_formatter {
        return (new text_field_formatter($format, $context))
            ->set_additional_options(['formatter' => 'totara_tui'])
            ->set_text_format($text_format)
            ->set_pluginfile_url_options(
                $context,
                self::FILE_COMPONENT,
                self::FILE_AREA,
                $application_id
            );
    }

    /**
     * Moves files in the value to the application_editor component & field.
     * Expects value to be json_encoded result of:
     * [
     *     draft_id => xxx, draft_id used to upload files.
     *     content => yyy, content from the editor.
     *     format => zzz, the editor's content format.
     * ]
     *
     * @param string|null $value
     *
     * @return string|null json_encoded value of object {format, content}
     */
    public function move_files_to_application_area(?string $value): ?string {
        $decoded_value = json_decode($value);

        if (empty($decoded_value) || empty($decoded_value->draft_id)) {
            return $value;
        }

        require_once("{$this->cfg->dirroot}/lib/filelib.php");

        $processed_result = [
            'format' => $decoded_value->format,
        ];
        if (empty($decoded_value->content) || !is_string($decoded_value->content)) {
            throw new coding_exception("invalid editor content");
        }

        $processed_result['content'] = file_save_draft_area_files(
            $decoded_value->draft_id,
            $this->application_interactor->get_application()->context->id,
            self::FILE_COMPONENT,
            self::FILE_AREA,
            $this->application_interactor->get_application()->id,
            $this->get_area_options(),
            $decoded_value->content
        );

        return json_encode($processed_result);
    }

    /**
     * Copies saved files in one application to another.
     *
     * @param application $source
     * @param application $destination
     * @return void
     */
    public static function copy_files_to_application(application $source, application $destination): void {
        if ($source->id === $destination->id) {
            throw new coding_exception("Can not duplicate files for an application");
        }
        builder::get_db()->transaction(function () use ($destination, $source) {
            global $CFG;
            require_once("{$CFG->dirroot}/lib/filelib.php");

            $file_storage = get_file_storage();
            $application_files = $file_storage->get_area_files(
                $source->context->id,
                self::FILE_COMPONENT,
                self::FILE_AREA,
                $source->id
            );

            $file_record = [
                'context' => $destination->context->id,
                'component' => self::FILE_COMPONENT,
                'filearea' => self::FILE_AREA,
                'itemid' => $destination->id,
                'timemodified' => time(),
            ];
            foreach ($application_files as $application_file) {
                $file_record['source'] = $application_file->get_source();

                $repository_id = $application_file->get_repository_id();

                if (!empty($repository_id)) {
                    $file_record['repositoryid'] = $repository_id;
                    $file_record['reference'] = $application_file->get_reference();
                }
                $file_storage->create_file_from_storedfile($file_record, $application_file);
            }
        });
    }

    /**
     * @return array
     */
    private function get_area_options(): array {
        return [
            'subdirs' => false,
            'maxbytes' => $this->cfg->maxbytes,
            'maxfiles' => - 1,
            'context' => $this->application_interactor->get_application()->get_context(),
        ];
    }

    /**
     * Get editor meta.
     *
     * @return array
     */
    public function get_editor_meta(): array {
        $can_attach_file = $this->application_interactor->can_attach_file();

        $meta = [
            'usageIdentifier' => [
                'instanceId' => $this->application_interactor->get_application()->id,
                "component" => self::FILE_COMPONENT,
                "area" => self::FILE_AREA,
                'context' => $this->application_interactor->get_application()->context->id,
            ],
            'variant' => self::VARIANT,
            'extraExtensions' => $can_attach_file ? ['attachment'] : [],
        ];

        if ($can_attach_file) {
            $meta['fileItemId'] = $this->generate_draft_file_id();
        }

        return $meta;
    }

    /**
     * Get the draft file id. Used in editors that handle files.
     *
     * @return int
     */
    private function generate_draft_file_id(): int {
        $draft_id = 0;

        file_prepare_draft_area(
            $draft_id,
            $this->application_interactor->get_application()->context->id,
            self::FILE_COMPONENT,
            self::FILE_AREA,
            $this->application_interactor->get_application()->id
        );

        return $draft_id;
    }
}