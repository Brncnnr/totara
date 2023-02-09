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
  @module container_workspace
-->
<template>
  <div class="tui-workspacePageHeader">
    <div
      :title="$str('workspace_image_alt', 'container_workspace')"
      role="img"
      class="tui-workspacePageHeader__img"
      :aria-label="$str('workspace_image_alt', 'container_workspace')"
      :style="{
        'background-image': `url('${workspaceImage}')`,
      }"
    />

    <div class="tui-workspacePageHeader__content">
      <div class="tui-workspacePageHeader__head">
        <div class="tui-workspacePageHeader__title">
          <h2 class="tui-workspacePageHeader__title-text">
            {{ workspaceName }}
          </h2>

          <ButtonIcon
            v-if="showNavigation"
            :aria-label="buttonMenuAriaLabel"
            :styleclass="{
              small: true,
              transparentNoPadding: true,
            }"
            class="tui-workspacePageHeader__title-buttonIcon"
            @click.prevent="showMenu = !showMenu"
          >
            <ShowIcon />
          </ButtonIcon>
        </div>

        <div class="tui-workspacePageHeader__subTitle">
          <p class="tui-workspacePageHeader__subTitle-text">
            {{ subTitle }}
          </p>

          <WorkspaceMuteButton
            v-if="showMuteButton"
            class="tui-workspacePageHeader__subTitle-button"
            :muted="workspaceMuted"
            @update="updateMuteStatus"
          />
        </div>
      </div>

      <WorkspaceMenu
        v-if="showMenu && showNavigation"
        :selected-workspace-id="workspaceId"
        class="tui-workspacePageHeader__menu"
      />
    </div>
  </div>
</template>

<script>
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ShowIcon from 'tui/components/icons/Show';
import { PUBLIC, PRIVATE, HIDDEN } from 'container_workspace/access';
import WorkspaceMuteButton from 'container_workspace/components/action/WorkspaceMuteButton';

// GraphQL queries
import muteWorkspace from 'container_workspace/graphql/mute_workspace';
import unmuteWorkspace from 'container_workspace/graphql/unmute_workspace';

export default {
  components: {
    WorkspaceMenu,
    ButtonIcon,
    ShowIcon,
    WorkspaceMuteButton,
  },

  props: {
    workspaceImage: {
      type: String,
      required: true,
    },

    workspaceId: {
      type: [Number, String],
      required: true,
    },

    workspaceAccess: {
      type: String,
      required: true,
      validator(prop) {
        return [PUBLIC, HIDDEN, PRIVATE].includes(prop);
      },
    },

    workspaceName: {
      type: String,
      required: true,
    },

    showMuteButton: Boolean,
    workspaceMuted: Boolean,
    showNavigation: Boolean,
  },

  data() {
    return {
      showMenu: false,
      submitting: false,
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

    subTitle() {
      if (this.workspaceAccess === PUBLIC) {
        return this.$str('public_workspace', 'container_workspace');
      } else if (this.workspaceAccess === PRIVATE) {
        return this.$str('private_workspace', 'container_workspace');
      }

      return this.$str('hidden_workspace', 'container_workspace');
    },
  },

  methods: {
    /**
     *
     * @param {Boolean} status
     * @return {Promise<void>}
     */
    async updateMuteStatus(status) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      const variables = {
        workspace_id: this.workspaceId,
      };

      try {
        let mutation = status ? muteWorkspace : unmuteWorkspace;
        await this.$apollo.mutate({
          mutation: mutation,
          variables: variables,
          // Update workspace interactor just in case.
          refetchQueries: ['container_workspace_workspace_interactor'],
        });

        this.$emit('update-mute-status', status);
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "workspace_image_alt",
      "collapse_nav",
      "expand_nav",
      "workspace_image_alt",
      "public_workspace",
      "private_workspace",
      "hidden_workspace"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --workspace-header-small-height: 55px;
  --workspace-header-medium-height: 88px;

  // Large screen width and height
  --workspace-header-large-height: 135px;
  --workspace-header-large-width: 135px;
}
.tui-workspacePageHeader {
  display: flex;
  flex-direction: column;
  width: 100%;

  @media (min-width: $tui-screen-sm) {
    flex-direction: row;
    align-items: center;
  }

  &__img {
    width: 100%;
    height: var(--workspace-header-small-height);
    margin-bottom: var(--gap-2);
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;

    @media (min-width: $tui-screen-xs) {
      height: var(--workspace-header-medium-height);
      margin-bottom: var(--gap-6);
    }

    @media (min-width: $tui-screen-sm) {
      width: 50%;
      max-width: var(--workspace-header-large-width);
      height: var(--workspace-header-large-height);
      margin-right: var(--gap-4);
      margin-bottom: 0;
      border-radius: var(--border-radius-normal);
    }
  }

  &__content {
    display: flex;
    flex-direction: column;
    width: 100%;
  }

  &__head {
    padding: 0 var(--gap-4);

    @media (min-width: $tui-screen-sm) {
      padding: 0;
    }
  }

  &__title {
    display: flex;
    align-items: center;

    &-text {
      width: 100%;
      margin: 0;

      @include tui-wordbreak--hard;
      @include tui-font-heading-medium;

      @media (min-width: $tui-screen-xs) {
        @include tui-font-heading-page-title;
      }
    }

    &-buttonIcon {
      margin-left: var(--gap-2);
    }
  }

  &__subTitle {
    display: flex;
    align-items: center;

    &-text {
      @include tui-font-body();
      margin-top: var(--gap-2);
      color: var(--color-neutral-6);
    }

    &-button {
      margin-left: var(--gap-4);
    }
  }

  &__menu {
    padding: var(--gap-8) 0;
    background-color: var(--color-neutral-3);
    border: var(--border-width-thin) solid var(--color-neutral-5);
  }
}
</style>
