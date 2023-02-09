<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module mod_approval
-->
<template>
  <div class="tui-mod_approval-sidePanel">
    <slot name="modal" />
    <slot name="user-profile" />

    <Tabs
      :selected="selectedTab"
      :small-tabs="true"
      class="tui-mod_approval-sidePanel__tabs"
      @input="event => $send({ type: $e.SWITCH_TAB, tab: event })"
    >
      <Tab
        id="actionsTab"
        class="tui-mod_approval-sidePanel__actionsBox"
        :name="$str('actions', 'mod_approval')"
      >
        <slot name="actions" />
      </Tab>

      <Tab
        id="commentsTab"
        class="tui-mod_approval-sidePanel__commentBox"
        :name="$str('comments', 'mod_approval')"
        :disabled="$matches('loading')"
      >
        <slot name="comments" />
      </Tab>

      <Tab
        id="activityTab"
        :always-render="true"
        class="tui-mod_approval-sidePanel__activity"
        :name="$str('activity', 'mod_approval')"
        :disabled="$matches('loading')"
      >
        <slot name="activity" />
      </Tab>
    </Tabs>
  </div>
</template>

<script>
import { MOD_APPROVAL__APPLICATION_VIEW } from 'mod_approval/constants';
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';

export default {
  components: {
    Tabs,
    Tab,
  },

  computed: {
    selectedTab() {
      if (this.$matches('sidePanel.actionsTab')) {
        return 'actionsTab';
      }

      if (this.$matches('sidePanel.commentsTab')) {
        return 'commentsTab';
      }

      if (this.$matches('sidePanel.activityTab')) {
        return 'activityTab';
      }

      return 'actionsTab';
    },
  },

  xState: {
    machineId: MOD_APPROVAL__APPLICATION_VIEW,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "actions",
    "activity",
    "comments"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-sidePanel {
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
    @media (min-width: $tui-screen-sm) {
      // Since the tab is already having a padding which it is '--gap-4'.
      // Therefore we just need another '--gap-4'.
      margin-top: var(--gap-4);
    }
  }

  &__actionsBox {
    // Since the tab is already having a padding which it is '--gap-4'.
    // Therefore we just need another '--gap-4'.
    margin-top: var(--gap-4);
  }

  &__stage {
    @include tui-font-body-x-small();
  }

  &__stageActivities {
    margin: 0;
    list-style-type: none;
  }

  &__stageActivity {
    padding: var(--gap-5) 0;
    border-top: 1px solid var(--color-neutral-4);
    &:first-child {
      border-top: none;
    }
    &:last-child {
      padding-bottom: 0;
    }
  }

  &__stageActivityDescription {
    margin-bottom: var(--gap-1);
  }

  &__stageActivityTimestamp {
    @include tui-font-body-xx-small();
  }
}
</style>
