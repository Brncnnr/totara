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
  <div class="tui-engageSurveyVoteTitle">
    <div class="tui-engageSurveyVoteTitle__head">
      <h3 class="tui-engageSurveyVoteTitle__title">
        {{ title }}
      </h3>
      <BookmarkButton
        v-show="showBookmarkButton"
        :primary="false"
        :circle="true"
        :bookmarked="bookmarked"
        size="300"
        @click="$emit('bookmark', $event)"
      />
    </div>
  </div>
</template>

<script>
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';

export default {
  components: {
    BookmarkButton,
  },

  props: {
    title: {
      type: String,
      required: true,
    },

    bookmarked: {
      type: Boolean,
      default: false,
    },

    owned: {
      type: Boolean,
      required: true,
    },

    showBookmarkButton: {
      type: Boolean,
      default: true,
    },
  },
};
</script>

<style lang="scss">
:root {
  --survey-header-iconBtn-height: 3.2rem;
}

.tui-engageSurveyVoteTitle {
  display: flex;
  flex-direction: column;

  &__head {
    display: flex;
    justify-content: space-between;

    .tui-iconBtn--circle {
      height: var(--survey-header-iconBtn-height);
      .tui-iconBtn__icon {
        top: 0;
      }
    }

    > :last-child {
      margin-left: var(--gap-2);
    }
  }

  &__title {
    @include tui-font-heading-medium();
    width: 90%;
    margin: 0;
    @include tui-wordbreak--hyphens;

    @media (max-width: $tui-screen-sm) {
      width: 100%;
    }
  }
}
</style>
