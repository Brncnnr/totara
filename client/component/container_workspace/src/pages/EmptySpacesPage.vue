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
  <LayoutTwoColumn class="tui-emptySpacesPage">
    <SidePanel slot="left" :show-button-control="false" :initially-open="true">
      <WorkspaceMenu
        class="tui-emptySpacesPage__sideMenu"
        @create-workspace="navigateToWorkspace"
      />
    </SidePanel>

    <div slot="right" class="tui-emptySpacesPage__content">
      <EmptySpacesHeader
        :can-create="canCreate"
        class="tui-emptySpacesPage__header"
      />

      <RecommendSpaces
        v-if="showRecommended"
        class="tui-emptySpacesPage__recommendedSpaces"
        @join-workspace="joinWorkspace"
      />
    </div>
  </LayoutTwoColumn>
</template>

<script>
import LayoutTwoColumn from 'tui/components/layouts/LayoutTwoColumn';
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import EmptySpacesHeader from 'container_workspace/components/head/EmptySpacesHeader';
import RecommendSpaces from 'container_workspace/components/recommend/RecommendedSpaces';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import { notify } from 'tui/notifications';

// GraphQL queries
import notifications from 'container_workspace/graphql/notifications';
import getCategoryInteractor from 'container_workspace/graphql/workspace_category_interactor';

export default {
  components: {
    SidePanel,
    LayoutTwoColumn,
    WorkspaceMenu,
    EmptySpacesHeader,
    RecommendSpaces,
  },

  props: {
    showRecommended: {
      type: Boolean,
      required: true,
    },
  },

  apollo: {
    notifications: {
      query: notifications,
      update() {
        return [];
      },

      result({ data: { notifications } }) {
        Array.prototype.forEach.call(notifications, ({ message, type }) => {
          notify({
            message: message,
            type: type,
          });
        });
      },
    },

    categoryInteractor: {
      query: getCategoryInteractor,
      variables() {
        return {
          workspace_id: null,
        };
      },
      update({ category_interactor }) {
        return category_interactor;
      },
    },
  },

  data() {
    return {
      notifications: [],
      categoryInteractor: {},
    };
  },

  computed: {
    canCreate() {
      return this.categoryInteractor.can_create;
    },
  },

  methods: {
    /**
     *
     * @param {Number} id
     */
    navigateToWorkspace({ id }) {
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php',
        { id }
      );
    },

    /**
     *
     * @param {Number} workspaceId
     */
    joinWorkspace(workspaceId) {
      this.navigateToWorkspace({ id: workspaceId });
    },
  },
};
</script>

<style lang="scss">
.tui-emptySpacesPage {
  display: flex;
  flex-direction: column;

  // Overriding the height here.
  .tui-responsive {
    flex-grow: 1;
    width: 100%;
    height: 100%;
  }

  &__sideMenu {
    display: none;
  }

  &__content {
    padding-right: var(--gap-4);
    padding-left: var(--gap-4);
  }

  &__header {
    margin-top: calc(var(--gap-12) * 2);
    margin-bottom: var(--gap-12);
  }

  &__recommendedSpaces {
    margin-top: var(--gap-8);
  }

  @media screen and (min-width: $tui-screen-sm) {
    &__sideMenu {
      display: block;
    }

    &__content {
      padding-right: var(--gap-8);
      padding-left: 0;
    }
  }
}
</style>
