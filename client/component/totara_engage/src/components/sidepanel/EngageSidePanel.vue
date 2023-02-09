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
  @module totara_engage
-->
<template>
  <div class="tui-engageSidePanel">
    <slot name="modal">
      <!-- This is where all the modal should be. -->
    </slot>
    <slot name="author-profile" />

    <Tabs
      v-model="selectedTab"
      :small-tabs="true"
      class="tui-engageSidePanel__tabs"
    >
      <Tab
        id="overview"
        class="tui-engageSidePanel__overviewBox"
        :name="$str('overview', 'totara_engage')"
      >
        <slot name="overview" />
      </Tab>

      <Tab
        id="comments"
        class="tui-engageSidePanel__commentBox"
        :name="$str('comments', 'totara_engage')"
      >
        <slot name="comments" />
      </Tab>

      <Tab
        v-if="showRelated"
        id="related"
        class="tui-engageSidePanel__related"
        :name="$str('related', 'totara_engage')"
      >
        <slot name="related" />
      </Tab>
    </Tabs>
  </div>
</template>

<script>
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';

export default {
  components: {
    Tabs,
    Tab,
  },

  props: {
    showRelated: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      selectedTab: 'overview',
    };
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "comments",
      "overview",
      "related"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSidePanel {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  padding: var(--gap-4);

  @media (min-width: $tui-screen-sm) {
    padding: var(--gap-8);
  }

  &__tabs {
    display: flex;
    flex: 1 0 1px;
    flex-direction: column;
    padding-top: var(--gap-6);

    @media (min-width: $tui-screen-sm) {
      padding-top: var(--gap-8);
      overflow: auto;
    }

    .tui-tabs__panels {
      flex-basis: 0;
      flex-grow: 1;
      flex-shrink: 0;

      @media (min-width: $tui-screen-sm) {
        min-height: 0;
      }
    }

    // Overriding the fallback select list when there isn't enough space
    .tui-formRow__action {
      width: 200px;
    }
  }

  &__commentBox {
    height: 100%;

    @media (min-width: $tui-screen-sm) {
      // Since the tab is already having a padding which it is '--gap-4'.
      // Therefore we just need another '--gap-4'.
      height: calc(100% - var(--gap-4));
      margin-top: var(--gap-4);
    }
  }

  &__overviewBox {
    // Since the tab is already having a padding which it is '--gap-4'.
    // Therefore we just need another '--gap-4'.
    margin-top: var(--gap-4);
  }

  &__related {
    height: 100%;
    overflow-y: auto;
  }
}
</style>
