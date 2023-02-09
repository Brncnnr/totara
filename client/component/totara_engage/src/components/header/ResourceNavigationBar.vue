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

  @author Cody Finegan <cody.finegan@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-resourceNavigationBar">
    <a
      v-if="backButton"
      class="tui-resourceNavigationBar__backLink"
      :href="backButton.url"
      :title="backButton.label"
    >
      <BackArrow class="tui-resourceNavigationBar__backIcon" size="200" />
      <span> {{ backButton.label }} </span>
    </a>

    <div
      v-if="navigationButtons"
      class="tui-resourceNavigationBar__nextPrevious"
    >
      <a
        v-if="navigationButtons.previous"
        class="tui-resourceNavigationBar__previousLink"
        :href="navigationButtons.previous"
        :title="$str('resourcenavprevious', 'totara_engage')"
      >
        <BackArrow size="100" />
      </a>
      <span
        v-else
        class="tui-resourceNavigationBar__previousLink tui-resourceNavigationBar--disabled"
      >
        <BackArrow size="100" />
      </span>

      <span>{{ navigationButtons.label }}</span>

      <a
        v-if="navigationButtons.next"
        class="tui-resourceNavigationBar__nextLink"
        :href="navigationButtons.next"
        :title="$str('resourcenavnext', 'totara_engage')"
      >
        <ForwardArrow size="100" />
      </a>
      <span
        v-else
        class="tui-resourceNavigationBar__nextLink tui-resourceNavigationBar--disabled"
      >
        <ForwardArrow size="100" />
      </span>
    </div>
  </div>
</template>

<script>
import ForwardArrow from 'tui/components/icons/ForwardArrow';
import BackArrow from 'tui/components/icons/BackArrow';

export default {
  components: {
    ForwardArrow,
    BackArrow,
  },
  props: {
    backButton: {
      type: Object,
      required: false,
    },

    navigationButtons: {
      type: Object,
      required: false,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "resourcenavnext",
      "resourcenavprevious"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-resourceNavigationBar {
  display: flex;

  @media (max-width: $tui-screen-sm) {
    padding: var(--gap-4);
  }

  &__backLink {
    display: flex;
    flex: 1;
    align-items: center;
    padding-right: var(--gap-12);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    &:hover {
      text-decoration: none;
    }
  }

  &__backIcon {
    margin-right: var(--gap-2);
  }

  &--disabled {
    color: var(--color-state-disabled);
  }

  &__previousLink {
    margin-right: var(--gap-1);
  }

  &__nextLink {
    margin-left: var(--gap-1);
  }
}
</style>
