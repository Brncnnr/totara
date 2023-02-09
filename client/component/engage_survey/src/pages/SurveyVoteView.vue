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
  <Layout class="tui-engageSurveyVoteView">
    <template v-if="backButton || navigationButtons" v-slot:header>
      <ResourceNavigationBar
        :back-button="backButton"
        :navigation-buttons="navigationButtons"
        class="tui-engageSurveyVoteView__backButton"
      />
    </template>
    <template v-slot:column>
      <Loader :loading="$apollo.loading" :fullpage="true" />
      <div v-if="!$apollo.loading" class="tui-engageSurveyVoteView__layout">
        <div class="tui-engageSurveyVoteView__content">
          <SurveyVoteTitle
            :title="firstQuestion.value"
            :bookmarked="bookmarked"
            :owned="survey.owned"
            :show-bookmark-button="interactor.can_bookmark"
            class="tui-engageSurveyVoteView__title"
            @bookmark="updateBookmark"
          />
          <SurveyVoteContent
            v-if="!survey.voted && !survey.owned"
            :answer-type="firstQuestion.answertype"
            :options="firstQuestion.options"
            :question-id="firstQuestion.id"
            :resource-id="resourceId"
            :label="firstQuestion.value"
            :disabled="!interactor.can_react"
          />
          <SurveyResultContent v-else :resource-id="resourceId" />
        </div>
      </div>
    </template>

    <template v-slot:sidepanel>
      <SurveySidePanel :resource-id="resourceId" :interactor="interactor" />
    </template>
  </Layout>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import Layout from 'totara_engage/components/page/LayoutOneColumnContentWithSidePanel';
import SurveySidePanel from 'engage_survey/components/sidepanel/SurveySidePanel';
import ResourceNavigationBar from 'totara_engage/components/header/ResourceNavigationBar';
import SurveyVoteTitle from 'engage_survey/components/content/SurveyVoteTitle';
import SurveyVoteContent from 'engage_survey/components/content/SurveyVoteContent';
import SurveyResultContent from 'engage_survey/components/content/SurveyResultContent';
import { surveyPageMixin } from 'engage_survey/index';

export default {
  components: {
    SurveySidePanel,
    ResourceNavigationBar,
    Loader,
    SurveyVoteTitle,
    SurveyVoteContent,
    SurveyResultContent,
    Layout,
  },
  mixins: [surveyPageMixin],
};
</script>

<style lang="scss">
.tui-engageSurveyVoteView {
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
    width: 100%;
    margin: 0 auto;
    padding: 0 0 var(--gap-12) 0;
    @media (max-width: $tui-screen-sm) {
      width: 70vw;
      margin-left: var(--gap-8);
    }
  }

  &__title {
    margin-bottom: var(--gap-4);
  }
}
</style>
