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
 * @package engage_survey
 */
namespace engage_survey\totara_engage\card;

use moodle_url;
use theme_config;
use totara_engage\card\card;
use totara_engage\link\builder;
use totara_engage\question\question;
use totara_topic\provider\topic_provider;
use totara_tui\output\component;

/**
 * Metadata class for survey card.
 */
final class survey_card extends card {
    /**
     * @return component
     */
    public function get_tui_component(): component {
        return new component("engage_survey/components/card/SurveyCard");
    }

    /**
     * Overrding the get name attribute.
     * @return string|null
     */
    public function get_name(): ?string {
        $extra = $this->get_json_decoded_extra();

        if (empty($extra) || !isset($extra['questions']) || !is_array($extra['questions'])) {
            return null;
        }

        $question = reset($extra['questions']);

        if (null == $question || !isset($question['value'])) {
            return null;
        }

        return $question['value'];
    }

    /**
     * @param theme_config|null $theme_config
     * @return array
     */
    public function get_extra_data(?theme_config $theme_config = null): array {
        global $USER, $OUTPUT;
        $ownerid = $this->get_userid();

        // By default, the owner should not be able to vote his/her survey. Therefore, we can
        // mark this flag to true, for specific scenario.
        $voted = ($USER->id == $ownerid);
        $expired = false;
        $editable = false;

        $expiredtext = "";

        $extra = $this->get_json_decoded_extra();
        $questions = [];

        if (!empty($extra)) {
            if (isset($extra['questions'])) {
                // Since survey at this point only contain one question, but the relationship is still one to many.
                // Though we will just process on one question for now.
                $single = reset($extra['questions']);
                $question = question::from_id($single['id']);

                if ($USER->id != $ownerid) {
                    // Getting the current answers of this user in action, so that we would know if
                    // the user had voted for the survey or not.
                    // Note that we do not allow the owner/author to vote by himself.
                    $voted = $question->has_answer_of_user($USER->id);
                }

                if (isset($extra['timeexpired'])) {
                    $format = get_string('strftimedate', 'langconfig');
                    $time = userdate($extra['timeexpired'], $format);
                    $expiredtext = get_string('expiredat', 'engage_survey', $time);

                    $now = time();
                    $expired = $now > $extra['timeexpired'];
                }

                $answer_type = $question->get_answer_type();
                $options = $single['options'];

                $questions[] = [
                    'id' => $question->get_id(),
                    'votes' => $single['votes'] ?? 0,
                    'participants' => $single['participants'],
                    'options' => $options,
                    'answertype'=> $answer_type
                ];
            }

            if ($USER->id == $ownerid && isset($extra['editable'])) {
                // Same owner, therefore, start checking the extra cache data.
                $editable = (bool) $extra['editable'];
            }
        }

        if (empty($questions)) {
            throw new \coding_exception(
                "Cannot get the extra data of the survey card, where there is no questions at all"
            );
        }

        return [
            'questions' => $questions,
            'expired' => $expiredtext,
            'voted' => $voted,
            'isexpired' => $expired,
            'editable' => $editable,
            'image' => $OUTPUT->image_url("default", 'engage_survey')->out(),
            'image_rectangle' => $OUTPUT->image_url("default_rectangle", 'engage_survey')->out(),
        ];
    }

    /**
     * @return array
     */
    public function  get_topics(): array {
        $id = $this->get_instanceid();
        return topic_provider::get_for_item($id, $this->component, 'engage_resource');
    }

    /**
     * @param array $args
     * @return string
     */
    public function get_url(array $args): string {
        // For the survey, as the card may send us to any of the survey pages, we're going to send
        // them to the index instead & have that do the redirection.
        $url = builder::to($this->get_component(), ['id' => $this->get_instanceid()])
            ->set_attribute('page', 'redirect')
            ->url();

        // If we're provided with a source, use it
        if (!empty($args['source'])) {
            $url->param('source', $args['source']);
        }

        return $url->out(false);
    }

    /**
     * @since Totara 13.6 added parameter $theme_config
     *
     * @param string|null $preview_mode
     * @param theme_config|null $theme_config
     * @return moodle_url|null
     */
    public function get_card_image(?string $preview_mode = null, ?theme_config $theme_config = null): ?moodle_url {
        return null;
    }

    /**
     * @return component
     */
    public function get_card_image_component(): component {
        return new component('engage_survey/components/card/SurveyCardImage');
    }
}