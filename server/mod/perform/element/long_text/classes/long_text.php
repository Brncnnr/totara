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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

namespace performelement_long_text;

use context_user;
use core\collection;
use core\json_editor\formatter\default_formatter;
use core\json_editor\helper\document_helper;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_response_has_files;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\response\section_element_response;

class long_text extends respondable_element_plugin implements element_response_has_files {

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        global $CFG;
        $element_data = $element->data ?? null;

        // We need to make sure that the @@PLUGINFILE@@ url had been converted properly in order to convert the
        // weka content to text.
        // Note that zero in this case is represent for nothing, because we just want to validate the text and
        // there is no way to get the element response id from here.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $encoded_response_data = file_rewrite_pluginfile_urls(
            $encoded_response_data,
            'pluginfile.php',
            $element->context_id,
            self::get_response_files_component_name(),
            self::get_response_files_filearea_name(),
            0
        );

        $answer_text = $this->decode_response($encoded_response_data, $element_data);
        $errors = new collection();

        $is_empty_answer = empty($answer_text) || $answer_text === 'null';
        if (!$is_empty_answer) {
            // Check that the actual format of the submitted data is correct.
            $parsed_weka_json = json_decode($answer_text, true);
            if (isset($parsed_weka_json['weka'])) {
                $parsed_weka_json = $parsed_weka_json['weka'];
            }

            $is_empty_answer = self::is_weka_response_empty($parsed_weka_json);
        }

        if ($this->fails_required_validation($is_empty_answer, $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        return $errors;
    }

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|null
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data): ?string {
        // The response data could be double encoded json, so decode it so it's only encoded once
        $decoded_response_data = is_null($encoded_response_data) ? null : json_decode($encoded_response_data, true);
        if (is_string($decoded_response_data)) {
            return $decoded_response_data;
        }

        // The data isn't double encoded, so just return the original single encoded data.
        // We must always return a JSON encoded string because response data is always decoded in the front end.
        return $encoded_response_data;
    }

    /**
     * @inheritDoc
     */
    public function get_participant_response_component(): string {
        return 'mod_perform/components/element/participant_form/HtmlResponseDisplay';
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array {
        // The response is displayed as HTML instead of individual lines, so nothing is returned here.
        $data = $this->decode_response($encoded_response_data, $encoded_element_data);
        $result = $data === null
            ? ''
            : $data;

        return [$result];
    }

    /**
     * Processes a long text element response and returns the processed response.
     *
     * Check if the user uploaded files, and if so save them to permanent storage for the specified response.
     *
     * @param int $element_response_id
     * @param element $element
     * @param string|null $response_data
     *
     * @return string|null
     */
    public static function process_weka_response(int $element_response_id, element $element, ?string $response_data): ?string {
        global $CFG, $TEXTAREA_OPTIONS, $USER;

        require_once($CFG->dirroot . '/lib/filelib.php');
        require_once($CFG->dirroot . '/lib/formslib.php');

        $context_id = $element->context_id;
        $data = json_decode($response_data, true);
        $draft_id = $data['draft_id'] ?? null;

        $weka_content = null;
        $response = null;
        if (isset($data['weka'])) {
            $response = document_helper::json_encode_document($data['weka']);
            $weka_content = $response;

            // Only works for logged-in users but not for external participants
            if ($USER->id > 0 && !empty($draft_id)) {
                $weka_content = file_rewrite_pluginfile_urls(
                    $response,
                    'draftfile.php',
                    context_user::instance($USER->id)->id,
                    'user',
                    'draft',
                    $draft_id
                );
            }
        }

        if (!$weka_content || self::is_weka_response_empty($weka_content)) {
            $response = null;
        } else if (!empty($draft_id)) {
            $response = file_save_draft_area_files(
                $draft_id,
                $context_id,
                self::get_response_files_component_name(),
                self::get_response_files_filearea_name(),
                $element_response_id,
                $TEXTAREA_OPTIONS,
                $response
            );
        }
        return $response;
    }

    /**
     * @param section_element_response $section_element_response
     *
     * @deprecated since Totara 14
     */
    public function post_response_submission(section_element_response $section_element_response): void {
        parent::post_response_submission($section_element_response);
    }

    /**
     * Does the response actually have any content?
     *
     * @param array|string|null $response
     * @return bool
     */
    private static function is_weka_response_empty($response): bool {
        if (document_helper::is_document_empty($response)) {
            return true;
        }

        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        $formatter = new default_formatter();
        $text = $formatter->to_text($response);
        return trim($text) === '';
    }

    /**
     * Get the component name for where response files are to be stored.
     *
     * @return string
     */
    public static function get_response_files_component_name(): string {
        return 'performelement_long_text';
    }

    /**
     * Get the file area name for where response files are to be stored.
     *
     * @return string
     */
    public static function get_response_files_filearea_name(): string {
        return 'response';
    }

}
