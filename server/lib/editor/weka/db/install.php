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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package editor_weka
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Editor weka installation.
 */
function xmldb_editor_weka_install() {
    global $DB, $CFG;
    require_once("{$CFG->dirroot}/lib/editor/weka/db/upgradelib.php");

    // ☺️
    $entity = new \stdClass();
    $entity->name = 'smiling_face_with_smiling_eyes';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-3|:3';
    $entity->shortcode = '1F60A';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🙂
    $entity = new \stdClass();
    $entity->name = 'slightly_smiling_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':\\)';
    $entity->shortcode = '1F642';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😁
    $entity = new \stdClass();
    $entity->name = 'beaming_face_with_smiling_eyes';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '=\\)';
    $entity->shortcode = '1F601';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);


    // 😃
    $entity = new \stdClass();
    $entity->name = 'grinning_face_with_big_eyes';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-D|:D';
    $entity->shortcode = '1F603';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😍
    $entity = new \stdClass();
    $entity->name = 'smiling_face_with_heart_eyes';
    $entity->category = 'smileys_emotion';
    $entity->pattern = 'B\\^D';
    $entity->shortcode = '1F60D';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // ☹️
    $entity = new \stdClass();
    $entity->name = 'frowning_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-\\(|:\\(';
    $entity->shortcode = '2639';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🙁
    $entity = new \stdClass();
    $entity->name = 'slightly_frowning_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-\\[|:\\[';
    $entity->shortcode = '1F641';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😠
    $entity = new \stdClass();
    $entity->name = 'angry_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '\\>:\\(';
    $entity->shortcode = '1F620';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😢
    $entity = new \stdClass();
    $entity->name = 'crying_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':\'\\(';
    $entity->shortcode = '1F622';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😭
    $entity = new \stdClass();
    $entity->name = 'loudly_crying_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':\'-\\(';
    $entity->shortcode = '1F62D';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😂
    $entity = new \stdClass();
    $entity->name = 'face_with_tears_of_joy';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':\'-\\)|:\'\\)';
    $entity->shortcode = '1F602';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😨
    $entity = new \stdClass();
    $entity->name = 'fearful_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = 'D-\':';
    $entity->shortcode = '1F628';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😱
    $entity = new \stdClass();
    $entity->name = 'face_screaming_in_fear';
    $entity->category = 'smileys_emotion';
    $entity->pattern = 'D:\\<';
    $entity->shortcode = '1F631';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😮
    $entity = new \stdClass();
    $entity->name = 'face_with_open_mouth';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-O|:O';
    $entity->shortcode = '1F62E';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😚
    $entity = new \stdClass();
    $entity->name = 'kissing_face_with_closed_eyes';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':-\\*|:\\*';
    $entity->shortcode = '1F61A';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😉
    $entity = new \stdClass();
    $entity->name = 'winking_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ';-\\)|;\\)';
    $entity->shortcode = '1F609';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😛
    $entity = new \stdClass();
    $entity->name = 'face_with_tongue';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑P|:P';
    $entity->shortcode = '1F61B';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🤔
    $entity = new \stdClass();
    $entity->name = 'thinking_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑\\/|:\\/';
    $entity->shortcode = '1F914';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😐
    $entity = new \stdClass();
    $entity->name = 'neutral_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑\\||:\\|';
    $entity->shortcode = '1F610';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😳
    $entity = new \stdClass();
    $entity->name = 'flushed_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':\\$';
    $entity->shortcode = '1F633';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🤐
    $entity = new \stdClass();
    $entity->name = 'zipper_mouth_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑X|:X';
    $entity->shortcode = '1F910';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😇
    $entity = new \stdClass();
    $entity->name = 'smiling_face_with_halo';
    $entity->category = 'smileys_emotion';
    $entity->pattern = 'O:‑\\)|O:\\)';
    $entity->shortcode = '1F607';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😈
    $entity = new \stdClass();
    $entity->name = 'smiling_face_with_horns';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '\\>:‑\\)|\\>:\\)';
    $entity->shortcode = '1F608';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😎
    $entity = new \stdClass();
    $entity->name = 'smiling_face_with_sunglasses';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '\\|;‑\\)';
    $entity->shortcode = '1F60E';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😏
    $entity = new \stdClass();
    $entity->name = 'smirking_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑J';
    $entity->shortcode = '1F60F';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 😵
    $entity = new \stdClass();
    $entity->name = 'dizzy_face';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '\\%‑\\)|\\%\\)';
    $entity->shortcode = '1F635';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🤒
    $entity = new \stdClass();
    $entity->name = 'face_with_thermometer';
    $entity->category = 'smileys_emotion';
    $entity->pattern = ':‑\\#\\#\\#.';
    $entity->shortcode = '1F912';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 👍
    $entity = new \stdClass();
    $entity->name = 'thumbs_up';
    $entity->category = 'people_body';
    $entity->pattern = '\\+1';
    $entity->shortcode = '1F44D';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 👎
    $entity = new \stdClass();
    $entity->name = 'thumbs_down';
    $entity->category = 'people_body';
    $entity->pattern = '-1';
    $entity->shortcode = '1F44E';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 👏
    $entity = new \stdClass();
    $entity->name = 'clapping_hands';
    $entity->category = 'people_body';
    $entity->pattern = '';
    $entity->shortcode = '1F44F';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 👻
    $entity = new \stdClass();
    $entity->name = 'ghost';
    $entity->category = 'smileys_emotion';
    $entity->pattern = '';
    $entity->shortcode = '1F47B';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // 🦄
    $entity = new \stdClass();
    $entity->name = 'unicorn';
    $entity->category = 'animals_nature';
    $entity->pattern = '';
    $entity->shortcode = '1F984';
    $entity->active = 1;
    $DB->insert_record('editor_weka_emojis', $entity);

    // Conditionally add weka editor to enabled text editor.
    editor_weka_add_weka_to_texteditors();
}