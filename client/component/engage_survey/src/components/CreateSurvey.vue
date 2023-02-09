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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module engage_survey
-->

<template>
  <div class="tui-engageCreateSurvey">
    <SurveyForm
      v-show="stage === 0"
      :survey="survey"
      @next="next"
      @cancel="$emit('cancel')"
      @unsaved-changes="$emit('unsaved-changes')"
    />
    <AccessForm
      v-show="stage === 1"
      item-id="0"
      component="engage_survey"
      :show-back="true"
      :submitting="submitting"
      :selected-access="containerValues.access || defaultAccess"
      :private-disabled="privateDisabled"
      :restricted-disabled="restrictedDisabled"
      :container="container"
      :can-share="canShare"
      @done="done"
      @back="back"
      @cancel="$emit('cancel')"
    />
  </div>
</template>

<script>
import SurveyForm from 'engage_survey/components/form/SurveyForm';
import AccessForm from 'totara_engage/components/form/AccessForm';
import createSurvey from 'engage_survey/graphql/create_survey';
import { AccessManager } from 'totara_engage/index';
import { notify } from 'tui/notifications';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';

export default {
  components: {
    SurveyForm,
    AccessForm,
  },

  mixins: [ContainerMixin],

  props: {
    showNotification: {
      type: Boolean,
    },
    canShare: Boolean,
  },

  data() {
    return {
      stage: 0,
      maxStage: 1,
      survey: {
        question: '',
        type: '',
        options: [],
      },
      submitting: false,
      defaultAccess: 'PRIVATE',
    };
  },

  computed: {
    privateDisabled() {
      return this.containerValues.access
        ? !AccessManager.isPrivate(this.containerValues.access)
        : false;
    },
    restrictedDisabled() {
      return this.containerValues.access
        ? AccessManager.isPublic(this.containerValues.access)
        : false;
    },
  },

  methods: {
    /**
     * @param {String}          question
     * @param {Number|String}   type
     * @param {Array}           options
     */
    next({ question, type, options }) {
      if (this.stage < this.maxStage) {
        this.stage += 1;
      }

      this.survey.question = question;
      this.survey.type = type;
      this.survey.options = options;

      this.$emit('change-title', this.stage);
    },

    back() {
      if (this.stage > 0) {
        this.stage -= 1;
      }

      this.$emit('change-title', this.stage);
    },

    /**
     * @param {String} access
     * @param {Array} topics
     * @param {Array} shares
     */
    done({ access, topics, shares }) {
      if (this.submitting) {
        return;
      }
      this.submitting = true;

      this.$apollo
        .mutate({
          mutation: createSurvey,
          refetchQueries: [
            'totara_engage_contribution_cards',
            'container_workspace_contribution_cards',
            'container_workspace_shared_cards',
          ],
          variables: {
            // TODO: replace timeexpired with the time selected from the date component
            timeexpired: null,
            questions: [
              {
                value: this.survey.question,
                answertype: this.survey.type,
                options: this.survey.options.map(option => option.text),
              },
            ],
            access: access,
            topics: topics.map(topic => topic.id),
            shares: shares,
          },
          update: (cache, { data: { survey } }) => {
            this.$emit('done', { resourceId: survey.resource.id });
          },
        })
        .then(({ data: { survey } }) => {
          if (survey && this.showNotification && !this.container) {
            notify({
              message: this.$str('created', 'engage_survey'),
              type: 'success',
            });
          }
          this.$emit('cancel');
        })
        .finally(() => (this.submitting = false));
    },
  },
};
</script>

<lang-strings>
{
  "engage_survey": [
    "created"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageCreateSurvey {
  display: flex;
  flex: 1;
  flex-direction: column;
}
</style>
