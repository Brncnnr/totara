<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Qingyang Liu <Qingyang.liu@totaralearning.com>
  @module engage_survey
-->
<template>
  <div class="tui-engageSurveyVoteContent">
    <Form class="tui-engageSurveyVoteContent__form" :vertical="true">
      <template v-if="isSingleChoice">
        <RadioBox v-model="answer" :options="options" :label="label" />
      </template>
      <template v-else-if="isMultiChoice">
        <SquareBox v-model="answer" :options="options" :label="label" />
      </template>
      <Button
        :disabled="null == answer || disabled"
        :styleclass="{ primary: true }"
        :text="$str('vote', 'engage_survey')"
        :aria-label="$str('vote', 'engage_survey')"
        class="tui-engageSurveyVoteContent__button"
        @click="vote"
      />
    </Form>
  </div>
</template>
<script>
import RadioBox from 'engage_survey/components/box/RadioBox';
import SquareBox from 'engage_survey/components/box/SquareBox';
import { AnswerType } from 'totara_engage/index';
import Button from 'tui/components/buttons/Button';
import Form from 'tui/components/form/Form';

// GraphQL queries
import createAnswer from 'engage_survey/graphql/create_answer';
import getSurvey from 'engage_survey/graphql/get_survey';

export default {
  components: {
    SquareBox,
    RadioBox,
    Button,
    Form,
  },

  props: {
    options: {
      type: [Array, Object],
      required: true,
    },

    answerType: {
      type: [Number, String],
      required: true,
    },

    resourceId: {
      required: true,
      type: [Number, String],
    },

    questionId: {
      required: true,
      type: [Number, String],
    },

    disabled: {
      type: Boolean,
      default: false,
    },

    label: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      questions: [],
      answer: null,
    };
  },

  computed: {
    /**
     * @returns {Boolean}
     */
    isMultiChoice() {
      return AnswerType.isMultiChoice(this.answerType);
    },

    /**
     *
     * @returns {Boolean}
     */
    isSingleChoice() {
      return AnswerType.isSingleChoice(this.answerType);
    },
  },

  methods: {
    vote() {
      if (null == this.answer) {
        return;
      }

      let answers;

      if (!Array.isArray(this.answer)) {
        answers = [this.answer];
      } else {
        answers = this.answer;
      }

      this.$apollo.mutate({
        mutation: createAnswer,
        refetchQueries: [
          {
            query: getSurvey,
            variables: {
              resourceid: this.resourceId,
            },
          },
        ],
        variables: {
          resourceid: this.resourceId,
          options: answers,
          questionid: this.questionId,
        },
      });
    },
  },
};
</script>
<lang-strings>
  {
    "engage_survey": [
      "vote"
    ]
  }
</lang-strings>
