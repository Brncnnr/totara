<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

namespace mod_approval\rb\display;

use core\format;
use core\orm\query\order;
use mod_approval\form_schema\field_type\application_editor;
use mod_approval\form_schema\form_schema;
use mod_approval\form_schema\form_schema_field;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use rb_column;
use rb_column_option;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;

/**
 * Display function class for application form response
 */
class application_form_response extends base {

    /**
     * Application's form data.
     *
     * @var form_data $form_data
     */
    private static $form_data;

    /**
     * Form schema the application was submitted with.
     *
     * @var form_schema $form_schema
     */
    private static $form_schema;

    /**
     * Id of application being processed.
     *
     * @var application $application
     */
    private static $application;

    /**
     * @inheritDoc
     */
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        /** @var form_schema $form_schema */
        /** @var form_data $form_data */
        [$form_schema, $form_data] = self::get_application_form_schema_and_data($value);
        $field = $form_schema->get_field($column->value);
        $response = $form_data->get_value($column->value) ?? '';

        // Field no longer exists in form_schema
        if (is_null($field)) {
            return $response;
        }

        return self::parse_field_response($response, $field, $format);
    }

    /**
     * Get the application's form schema and data
     *
     * @param int $id
     * @return array
     */
    private static function get_application_form_schema_and_data(int $id): array {
        if (empty(self::$application) || self::$application->id !== $id) {
            $application = application::load_by_id($id);

            /** @var application_submission[] $submissions*/
            $submissions = $application->submissions->sort('created', order::DIRECTION_ASC)->all(true);
            $form_data = form_data::create_empty();

            // Concatenate other form submissions.
            foreach ($submissions as $submission) {
                $form_data = $form_data->concat($submission->form_data_parsed);
            }

            self::$form_data = $form_data;
            self::$form_schema = form_schema::from_form_version($application->form_version);
            self::$application = $application;
        }

        return [self::$form_schema, self::$form_data];
    }

    /**
     * Parse the field response.
     *
     * @param string $response
     * @param form_schema_field $schema_field
     * @param string $format
     * @return string
     */
    private static function parse_field_response(string $response, form_schema_field $schema_field, string $format): string {
        switch ($schema_field->type) {
            case application_editor::FIELD_TYPE:
                if (empty($response)) {
                    return '';
                }
                $value = json_decode($response);
                $text_format = $format === 'html'
                    ? format::FORMAT_HTML
                    : format::FORMAT_PLAIN;

                $text_formatter = application_editor::get_text_formatter(
                    self::$application->context,
                    self::$application->id,
                    $text_format,
                    $value->format
                );

                return $text_formatter->format($value->content);
            default:
                $value = format_string($response, true, array('context' => \context_system::instance()));
                if ($format === 'html') {
                    return $value;
                }
                return \core_text::entities_to_utf8($value);
        }
    }

    /**
     * @inheritDoc
     */
    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report): bool {
        return false;
    }

    /**
     * Reset static cache.
     */
    public static function reset() {
        self::$application = null;
    }
}
