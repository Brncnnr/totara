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
  <div class="tui-engageSurveyResultBody" @click="navigateTo">
    <a :id="labelId" :href="voteUrl" class="tui-engageSurveyResultBody__title">
      <span aria-hidden="true" :title="name">{{ displayName }}</span>
      <span class="sr-only">{{ name }}</span>
    </a>
    <div class="tui-engageSurveyResultBody__progress">
      <SurveyQuestionResult
        v-for="({ votes, id, options, answertype }, index) in questions"
        :key="index"
        :options="options"
        :question-id="id"
        :total-votes="votes"
        :answer-type="answertype"
      />
    </div>
    <div class="tui-engageSurveyResultBody__footer">
      <div class="tui-engageSurveyResultBody__container">
        <p class="tui-engageSurveyResultBody__text">{{ voteMessage }}</p>
        <div class="tui-engageSurveyResultBody__icon">
          <AccessIcon :access="access" size="300" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import SurveyQuestionResult from 'engage_survey/components/card/result/SurveyQuestionResult';

export default {
  components: {
    AccessIcon,
    SurveyQuestionResult,
  },

  props: {
    name: {
      required: true,
      type: String,
      default: '',
    },

    questions: {
      type: [Object, Array],
      required: true,
    },

    access: {
      required: true,
      type: String,
    },

    labelId: {
      type: String,
      default: '',
    },

    resourceId: {
      required: true,
      type: String,
    },

    url: {
      required: true,
      type: String,
    },
  },

  computed: {
    voteMessage() {
      const questions = Array.prototype.slice.call(this.questions).shift();

      return this.$str('votemessage', 'engage_survey', {
        options: questions.options.length >= 3 ? 3 : 2,
        questions: questions.options.length,
      });
    },

    voteUrl() {
      return this.$url(this.url, {
        page: 'vote',
      });
    },

    /**
     * Gets what a visual user will see
     */
    displayName() {
      if (this.name.length < 40) {
        return this.name;
      } else {
        return this.name.substring(0, 37) + String.fromCharCode(8230);
      }
    },
  },

  methods: {
    navigateTo() {
      window.location.href = this.voteUrl;
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "votemessage"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyResultBody {
  display: flex;
  flex: 1;
  flex-direction: column;
  width: 100%;
  padding: var(--gap-2) var(--gap-4);

  &__title {
    @include tui-font-heading-x-small();
    margin-bottom: var(--gap-6);
    color: var(--color-text);
    @include tui-wordbreak--hyphens;
  }

  &__progress {
    flex-basis: 90%;
  }

  &__footer {
    display: flex;
    flex-basis: 10%;
    flex-direction: column;
    justify-content: flex-end;
  }

  &__container {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
  }

  &__text {
    @include tui-font-body-small();
  }

  &__icon {
    align-self: flex-end;
    margin-right: -5px;
  }
}
</style>
