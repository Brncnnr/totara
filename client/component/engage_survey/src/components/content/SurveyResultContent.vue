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
  <div v-if="!$apollo.loading" class="tui-engageSurveyResultContent">
    <SurveyQuestionResult
      v-for="({ id, votes, options, answertype }, index) in questions"
      :key="index"
      :question-id="id"
      :total-votes="votes"
      :answer-type="answertype"
      :options="options"
      :result-content="true"
    />
    <template v-if="isMultiChoice">
      <div class="tui-engageSurveyResultContent__participant">
        <span class="tui-engageSurveyResultContent__participantnumber">
          {{ showNumberOfParticipant }}
        </span>
        {{ showParticipants }}
      </div>
    </template>
  </div>
</template>

<script>
import SurveyQuestionResult from 'engage_survey/components/card/result/SurveyQuestionResult';
import { AnswerType } from 'totara_engage/index';

// GraphQL queries
import getSurvey from 'engage_survey/graphql/get_survey';

export default {
  components: {
    SurveyQuestionResult,
  },

  props: {
    resourceId: {
      required: true,
      type: [Number, String],
    },
  },

  apollo: {
    survey: {
      query: getSurvey,
      variables() {
        return {
          resourceid: this.resourceId,
        };
      },
    },
  },

  data() {
    return {
      survey: {},
    };
  },

  computed: {
    showParticipants() {
      const questions = Array.prototype.slice.call(this.questions),
        { participants } = questions.shift();

      if (participants === 1) {
        return this.$str('participant', 'engage_survey');
      }

      return this.$str('participants', 'engage_survey');
    },

    showNumberOfParticipant() {
      const { participants } = Array.prototype.slice
        .call(this.questions)
        .shift();

      return participants;
    },

    /**
     * @returns {Boolean}
     */
    isMultiChoice() {
      return AnswerType.isMultiChoice(this.questions[0].answertype);
    },

    questions() {
      const { questionresults } = this.survey;
      return Array.prototype.slice.call(questionresults);
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "close",
      "participant",
      "participants"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyResultContent {
  &__participant {
    @include tui-font-body();
    @include tui-font-heavy();
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-2);
  }

  &__participantnumber {
    @include tui-font-body();
    @include tui-font-heavy();
    padding-right: var(--gap-1);
  }
}
</style>
