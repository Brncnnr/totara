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
  <div class="tui-engageSurveyCard">
    <CoreCard
      :clickable="true"
      class="tui-engageSurveyCard__cardContent"
      :class="{
        'tui-engageSurveyCard__cardContent--calcHeight': showFootnotes,
        'tui-engageSurveyCard__cardContent--height': !showFootnotes,
      }"
      @click="surveyEvent"
    >
      <div class="tui-engageSurveyCard__inner">
        <div class="tui-engageSurveyCard__header">
          <section class="tui-engageSurveyCard__header-image">
            <SurveyIcon />
            <h3 class="tui-engageSurveyCard__header-title">
              {{ $str('survey', 'engage_survey') }}
            </h3>
          </section>
          <BookmarkButton
            v-if="showBookmark && !editAble"
            size="300"
            :bookmarked="innerBookMarked"
            :primary="false"
            :circle="false"
            :small="true"
            :transparent="true"
            class="tui-engageSurveyCard__header-bookmark"
            @click="updateBookmark"
          />
        </div>
        <template v-if="voted && !editAble">
          <SurveyResultBody
            :name="name"
            :label-id="labelId"
            :questions="questions"
            :access="access"
            :resource-id="instanceId"
            :url="url"
            @open-result="show.result = true"
          />
        </template>
        <template v-else>
          <SurveyCardBody
            :name="name"
            :questions="questions"
            :resource-id="instanceId"
            :bookmarked="innerBookMarked"
            :voted="voted"
            :topics="topics"
            :access="access"
            :owned="owned"
            :edit-able="editAble"
            :label-id="labelId"
            :url="url"
            @voted="handleVoted"
          />
        </template>
      </div>
    </CoreCard>
    <Footnotes v-if="showFootnotes" :footnotes="footnotes" />
  </div>
</template>

<script>
import CoreCard from 'tui/components/card/Card';
import { cardMixin } from 'totara_engage/index';
import SurveyCardBody from 'engage_survey/components/card/SurveyCardBody';
import SurveyResultBody from 'engage_survey/components/card/SurveyResultBody';
import SurveyIcon from 'engage_survey/components/icon/SurveyIcon';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';

// GraphQL
import voteResult from 'engage_survey/graphql/vote_result';
import updateBookmark from 'totara_engage/graphql/update_bookmark';
import Footnotes from 'totara_engage/components/card/Footnotes';

export default {
  components: {
    CoreCard,
    SurveyCardBody,
    SurveyResultBody,
    SurveyIcon,
    BookmarkButton,
    Footnotes,
  },

  mixins: [cardMixin],

  data() {
    let extraData = {},
      questions = [];

    if (this.extra) {
      extraData = JSON.parse(this.extra);
    }

    if (extraData.questions) {
      questions = Array.prototype.slice.call(extraData.questions);
    }

    return {
      show: {
        result: false,
        editModal: false,
      },

      innerBookMarked: this.bookmarked,
      questions: questions,
      voted: extraData.voted || false,
      extraData: JSON.parse(this.extra),
    };
  },

  computed: {
    editAble() {
      const extra = this.extraData;
      return extra.editable || false;
    },
  },

  methods: {
    /**
     * Updating the questions of this cards.
     */
    handleVoted() {
      this.$apollo
        .query({
          query: voteResult,
          variables: {
            resourceid: this.instanceId,
          },
        })
        .then(({ data: { questions } }) => {
          this.questions = questions;
          this.voted = true;

          // Showing the result afterward.
          this.show.result = true;
        });
    },

    $_hideModals() {
      this.show.editModal = false;
      this.show.result = false;
    },

    deleted() {
      this.$_hideModals();

      // Sent to up-stream to remove this very card from very
      this.emitDeleted();
    },

    updated() {
      this.$_hideModals();

      // Sent to up-stream to update this very card.
      this.emitUpdated();
    },

    updateBookmark() {
      this.innerBookMarked = !this.innerBookMarked;
      this.$apollo.mutate({
        mutation: updateBookmark,
        refetchAll: false,
        refetchQueries: ['totara_engage_contribution_cards'],
        variables: {
          itemid: this.instanceId,
          component: 'engage_survey',
          bookmarked: this.innerBookMarked,
        },
      });
    },

    surveyEvent() {
      if (!this.voted) {
        window.location.href = this.$url(this.url, { page: 'vote' });
      } else if (this.owned && this.editAble) {
        window.location.href = this.$url(this.url, { page: 'edit' });
      }
    },
  },
};
</script>
<lang-strings>
  {
    "engage_survey": [
      "survey"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSurveyCard {
  display: flex;
  flex-direction: column;
  width: 100%;

  &__cardContent {
    width: 100%;

    &--calcHeight {
      height: calc(var(---engage-card-height) - 22px);
    }

    &--height {
      height: var(---engage-card-height);
    }

    &:hover {
      box-shadow: var(--shadow-2);
    }
  }

  &__inner {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    border: var(--border-width-normal) solid var(--color-primary);
    border-radius: calc(var(--card-border-radius) - 1px);
  }

  &__header {
    display: flex;
    flex-direction: row;
    justify-content: space-between;

    &-image {
      display: flex;
      flex-direction: row;
      align-items: center;
      padding-top: var(--gap-2);
      padding-left: var(--gap-4);
    }

    &-title {
      @include tui-font-heading-label-small();
      margin-top: 0;
      margin-bottom: 0;
      margin-left: var(--gap-2);
      color: var(--color-neutral-6);
    }

    &-bookmark {
      align-self: flex-start;
      // Negative margin here to neutralise the default redundant edges of icon.
      margin-top: -2px;
    }
  }
}
</style>
