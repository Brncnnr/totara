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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

function editor_weka_add_weka_to_texteditors(): void {
    global $CFG;

    // Adding weka editor to the list of text editors.
    if (!empty($CFG->texteditors)) {
        $current_editors = explode(',', $CFG->texteditors);

        if (!in_array('weka', $current_editors)) {
            $atto_editor_key = array_search('atto', $current_editors);
            $textarea_editor_key = array_search('textarea', $current_editors);

            if (false !== $atto_editor_key) {
                // This is including the atto editor as well.
                $before_atto_editors = array_slice($current_editors, 0, $atto_editor_key + 1);
                $after_atto_editors = array_slice($current_editors, $atto_editor_key + 1, count($current_editors));

                // Adding 'weka' after atto editor.
                $new_editors = array_merge($before_atto_editors, ['weka'], $after_atto_editors);
            } else if (false !== $textarea_editor_key) {
                // This is to exclude the text area, as we would want to add the weka editor before the text area.
                $before_textarea_editors = array_slice($current_editors, 0, $textarea_editor_key);
                $after_atto_editors = array_slice($current_editors, $textarea_editor_key, count($current_editors));

                // Adding 'weka' before the textarea.
                $new_editors = array_merge($before_textarea_editors, ['weka'], $after_atto_editors);
            } else {
                // Otherwise just add it to after all.
                $new_editors = $current_editors;
                $new_editors[] = 'weka';
            }

            set_config('texteditors', implode(',', $new_editors));
        }
    }
}

/**
 * Fix empty url in attachments in the weka document.
 *
 * @param string $weka_doc_json the json encoded weka string
 * @return null|string returns null if nothing changed or the updated json string
 */
function editor_weka_fix_attachments_with_empty_url(string $weka_doc_json): ?string {
    $weka_doc = json_decode($weka_doc_json, true);

    $updated = editor_weka_fix_attachments_with_empty_url_recursively($weka_doc);
    if ($updated) {
        return json_encode($weka_doc, JSON_UNESCAPED_SLASHES);
    }

    return null;
}

/**
 * This will go through the weka doc structure recursively, change the original document
 * and will return true if anything got changed.
 *
 * @param array|null $weka_doc
 * @return bool
 */
function editor_weka_fix_attachments_with_empty_url_recursively(?array & $weka_doc): bool {
    if (!$weka_doc || !isset($weka_doc['content'])) {
        return false;
    }

    $updated = false;

    foreach ($weka_doc['content'] as & $content) {
        if ($content['type'] === 'attachments') {
            foreach ($content['content'] as & $attachment) {
                if ($attachment['type'] !== 'attachment') {
                    // Not an attachment, skipping
                    continue;
                }

                if ($attachment['attrs']['url'] !== null) {
                    // All looks good here, skipping
                    continue;
                }

                $updated = true;
                $filename = $attachment['attrs']['filename'];
                $attachment['attrs']['url'] = sprintf(
                    '@@PLUGINFILE@@/%s?forcedownload=1',
                    rawurlencode($filename)
                );
            }
        } else if (isset($content['content'])) {
            foreach ($content['content'] as & $content_recursive) {
                $updated_recursively = editor_weka_fix_attachments_with_empty_url_recursively($content_recursive);
                if ($updated || $updated_recursively) {
                    $updated = true;
                }
            }
        }
    }

    return $updated;
}