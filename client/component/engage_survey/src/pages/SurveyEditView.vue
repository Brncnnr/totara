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
  <Layout class="tui-engageSurveyEditView">
    <template v-if="backButton || navigationButtons" v-slot:header>
      <ResourceNavigationBar
        :back-button="backButton"
        :navigation-buttons="navigationButtons"
        class="tui-engageSurveyEditView__backButton"
      />
    </template>
    <template v-slot:column>
      <Loader :loading="$apollo.loading" :fullpage="true" />
      <div v-if="!$apollo.loading" class="tui-engageSurveyEditView__layout">
        <SurveyForm
          :survey="surveyInstance"
          :button-content="$str('save', 'engage_survey')"
          :submitting="submitting"
          :show-button-right="false"
          class="tui-engageSurveyEditView__content"
          @next="handleSave"
          @cancel="handleCancel"
        />
      </div>
    </template>
    <template v-slot:sidepanel>
      <SurveySidePanel :resource-id="resourceId" :interactor="interactor" />
    </template>
  </Layout>
</template>

<script>
import SurveySidePanel from 'engage_survey/components/sidepanel/SurveySidePanel';
import SurveyForm from 'engage_survey/components/form/SurveyForm';
import Loader from 'tui/components/loading/Loader';
import Layout from 'totara_engage/components/page/LayoutOneColumnContentWithSidePanel';
import ResourceNavigationBar from 'totara_engage/components/header/ResourceNavigationBar';
import { surveyPageMixin } from 'engage_survey/index';

// GraphQL
import getSurvey from 'engage_survey/graphql/get_survey';
import updateSurvey from 'engage_survey/graphql/update_survey';

export default {
  components: {
    SurveySidePanel,
    SurveyForm,
    Loader,
    Layout,
    ResourceNavigationBar,
  },
  mixins: [surveyPageMixin],

  data() {
    return {
      submitting: false,
    };
  },

  computed: {
    surveyInstance() {
      if (this.$apollo.loading) {
        return undefined;
      }

      let { questions } = this.survey;
      questions = Array.prototype.slice.call(questions);

      const question = questions.shift();
      let options = [];

      if (question.options && Array.isArray(question.options)) {
        options = question.options.map(({ id, value }) => {
          return {
            id: id,
            text: value,
          };
        });
      }

      return {
        questionId: question.id,
        question: question.value,
        type: question.answertype,
        options: options,
      };
    },
  },

  methods: {
    handleCancel() {
      window.location.href = this.$url(
        '/totara/engage/resources/survey/survey_view.php',
        { id: this.resourceId }
      );
    },
    handleSave({ question, questionId, type, options }) {
      if (this.submitting) {
        return;
      }
      this.submitting = true;

      this.$apollo
        .mutate({
          mutation: updateSurvey,
          refetchAll: false,
          variables: {
            resourceid: this.resourceId,
            questions: [
              {
                value: question,
                answertype: type,
                options: options.map(({ text }) => text),
                id: questionId,
              },
            ],
          },

          /**
           *
           * @param {DataProxy} proxy
           * @param {Object}    data
           */
          updateQuery: (proxy, data) => {
            proxy.writeQuery({
              query: getSurvey,
              variables: {
                resourceid: this.resourceId,
              },

              data: data,
            });
          },
        })
        .finally(() => {
          this.submitting = false;
          window.location.href = this.$url(
            '/totara/engage/resources/survey/survey_view.php',
            { id: this.resourceId }
          );
        });
    },
  },
};
</script>

<lang-strings>
  {
    "engage_survey": [
      "save"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyEditView {
  .tui-grid-item {
    min-height: var(--engageSurvey-min-height);
  }

  &__backButton {
    margin-bottom: var(--gap-12);
    padding: var(--gap-4) var(--gap-8);
  }

  &__navBar {
    margin-top: var(--gap-4);
    margin-left: var(--gap-4);
  }

  &__layout {
    display: flex;
  }

  &__content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin: 0 auto;
    padding: 0 0 var(--gap-12) 0;
    @media (max-width: $tui-screen-sm) {
      max-width: 80vw;
      margin-left: var(--gap-8);
    }
  }
}
</style>
