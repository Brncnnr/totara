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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-workspaceControlMenu">
    <div class="tui-workspaceControlMenu__head">
      <div class="tui-workspaceControlMenu__control">
        <h2 class="tui-workspaceControlMenu__title">
          {{ workspaceName }}
        </h2>

        <ButtonIcon
          v-if="showNavigation"
          :aria-label="buttonMenuAriaLabel"
          :styleclass="{ transparentNoPadding: true, small: true }"
          class="tui-workspaceControlMenu__menuButton"
          @click.prevent="showMenu = !showMenu"
        >
          <ShowIcon />
        </ButtonIcon>
      </div>
      <WorkspaceMenu
        v-show="showMenu && showNavigation"
        :selected-workspace-id="workspaceId"
        class="tui-workspaceControlMenu__menu"
      />
    </div>
  </div>
</template>

<script>
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ShowIcon from 'tui/components/icons/Show';

export default {
  components: {
    WorkspaceMenu,
    ButtonIcon,
    ShowIcon,
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },

    workspaceName: {
      type: String,
      required: true,
    },

    showNavigation: Boolean,
  },

  data() {
    return {
      showMenu: false,
    };
  },

  computed: {
    /**
     * Returning the aria label for button show  navigation.
     *
     * @returns {String}
     */
    buttonMenuAriaLabel() {
      if (this.showMenu) {
        return this.$str('collapse_nav', 'container_workspace');
      }

      return this.$str('expand_nav', 'container_workspace');
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "collapse_nav",
      "expand_nav"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceControlMenu {
  display: flex;
  width: 100%;

  &__head {
    display: flex;
    flex-direction: column;
    width: 100%;
  }

  &__control {
    display: flex;
    align-items: center;
    padding: var(--gap-4) var(--gap-4) 0 var(--gap-4);
  }

  &__title {
    @include tui-font-heading-small();
    margin: 0;
  }

  &__menuButton {
    margin-left: var(--gap-2);
  }

  &__menu {
    padding: var(--gap-8) 0;
    background-color: var(--color-neutral-3);
    border: var(--border-width-thin) solid var(--color-neutral-5);
  }
}
</style>
