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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module engage_survey
-->
<template>
  <div class="tui-engageSurveyQuestionResult">
    <div
      v-for="({ votes, value }, index) in calulatedOptions"
      :key="index"
      class="tui-engageSurveyQuestionResult__progressBar"
    >
      <template v-if="resultContent">
        <div class="tui-engageSurveyQuestionResult__progress">
          <div class="tui-engageSurveyQuestionResult__bar">
            <Progress
              :small="true"
              :hide-value="true"
              :value="getValues(votes)"
              :max="totalVotes"
              :hide-background="isMultiChoice"
              :show-empty-state="isMultiChoice"
            />
          </div>
          <span class="tui-engageSurveyQuestionResult__count">
            {{ votes }}
          </span>
        </div>
      </template>
      <template v-else>
        <template v-if="isMultiChoice">
          <div class="tui-engageSurveyQuestionResult__cardProgress">
            <div class="tui-engageSurveyQuestionResult__bar">
              <Progress
                :small="true"
                :hide-value="true"
                :value="getValues(votes)"
                :max="totalVotes"
                :hide-background="isMultiChoice"
                :show-empty-state="isMultiChoice"
              />
            </div>
            <span class="tui-engageSurveyQuestionResult__count">
              {{ votes }}
            </span>
          </div>
        </template>
        <template v-else>
          <Progress
            :small="true"
            :hide-value="true"
            :value="votes"
            :max="totalVotes"
            :hide-background="isMultiChoice"
            :show-empty-state="isMultiChoice"
          />
        </template>
      </template>
      <div v-if="isSingleChoice" class="tui-engageSurveyQuestionResult__answer">
        <span class="tui-engageSurveyQuestionResult__answerPercent">
          {{ $str('percentage', 'engage_survey', percentage(votes)) }}
        </span>
        <span class="tui-engageSurveyQuestionResult__answerText">
          {{ value }}
        </span>
      </div>
      <div v-else class="tui-engageSurveyQuestionResult__answer">
        {{ value }}
      </div>
    </div>
    <template v-if="resultContent">
      <div class="tui-engageSurveyQuestionResult__votes">
        <span>Total votes: {{ totalVotes }}</span>
      </div>
    </template>
  </div>
</template>

<script>
import Progress from 'tui/components/progress/Progress';
import { AnswerType } from 'totara_engage/index';

export default {
  components: {
    Progress,
  },

  props: {
    questionId: {
      type: [Number, String],
      required: true,
    },

    options: {
      type: [Array, Object],
      required: true,
    },

    /**
     * Total number of user has voted the question.
     */
    totalVotes: {
      type: [Number, String],
      required: true,
    },

    displayOptions: {
      type: [Number, String],
      default: 3,
    },

    resultContent: {
      type: Boolean,
      default: false,
    },

    answerType: {
      type: [Number, String],
      required: true,
    },
  },

  computed: {
    calulatedOptions() {
      if (this.resultContent) return this.options;
      return Array.prototype.slice.call(this.options, 0, this.displayOptions);
    },

    highestVote() {
      if (this.isMultiChoice) {
        const sortArray = Array.prototype.slice
          .call(this.options)
          .sort((o1, o2) => o2.votes - o1.votes);
        return sortArray[0].votes;
      }
      return 0;
    },

    /**
     *
     * @returns {Boolean}
     */
    isSingleChoice() {
      return AnswerType.isSingleChoice(this.answerType);
    },

    /**
     * @returns {Boolean}
     */
    isMultiChoice() {
      return AnswerType.isMultiChoice(this.answerType);
    },
  },
  methods: {
    /**
     *
     * @param {Number} votes
     * @returns {number}
     */
    percentage(votes) {
      return Math.round((votes / this.totalVotes) * 100);
    },
    /**
     *
     * @param {Number} votes
     * @returns {number}
     */
    $_getVotes(votes) {
      if (this.isMultiChoice) {
        return (votes / this.highestVote) * this.totalVotes;
      }
      return votes;
    },
    /**
     *
     * @param {Number} votes
     * @returns {number}
     */
    getValues(votes) {
      if (this.isSingleChoice) {
        return votes;
      }

      return this.highestVote === 0 && this.highestVote === votes
        ? this.totalVotes
        : this.$_getVotes(votes);
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "percentage"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyQuestionResult {
  &__progressBar {
    margin-bottom: var(--gap-2);
  }

  &__progress {
    display: flex;
    align-items: center;
    margin-top: var(--gap-4);
  }

  &__cardProgress {
    display: flex;
    align-items: center;
  }

  &__bar {
    width: 100%;
  }

  &__count {
    @include tui-font-body();
    margin-left: var(--gap-2);
    text-align: end;
  }

  &__answer {
    @include tui-font-body();
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;

    &Percent {
      margin-right: var(--gap-2);
      color: var(--color-secondary);
      font-weight: bold;
    }
  }

  &__votes {
    @include tui-font-heading-label();
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-4);
  }

  &__participant {
    @include tui-font-heavy();
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-2);
  }
}
</style>
