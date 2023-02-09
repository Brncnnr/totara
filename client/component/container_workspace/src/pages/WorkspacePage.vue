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
  <Layout class="tui-workspacePage">
    <template v-slot:left="{ direction }">
      <SidePanel
        v-if="direction === 'horizontal'"
        :show-button-control="false"
        :sticky="false"
        :initially-open="true"
      >
        <WorkspaceMenu
          :selected-workspace-id="workspaceId"
          @create-workspace="redirectToWorkspace"
        />
      </SidePanel>
    </template>

    <template v-slot:right="{ units, direction }">
      <div v-if="workspace" class="tui-workspacePage__mainContent">
        <WorkspacePageHeader
          :show-navigation="direction === 'vertical'"
          :workspace-id="workspace.id"
          :workspace-image="workspace.image"
          :workspace-name="workspace.name"
          :workspace-access="workspace.access"
          :workspace-muted="workspace.interactor.muted"
          :show-mute-button="workspace.interactor.joined"
          class="tui-workspacePage__head"
          @update-mute-status="updateMuteStatus"
        />

        <div class="tui-workspacePage__primaryAction">
          <WorkspacePrimaryAction
            :workspace-id="workspace.id"
            :workspace-name="workspace.name"
            :workspace-access="workspace.access"
            :workspace-context-id="workspace.context_id"
            :workspace-interactor="workspace.interactor"
            class="tui-workspacePage__primaryAction-action"
            @update-workspace="updateWorkspace"
            @add-audience="handleAddAudiencesClick"
            @request-to-join-workspace="reloadWorkspace"
            @cancel-request-to-join-workspace="reloadWorkspace"
            @added-member="reloadWorkspace"
            @deleted-workspace="redirectToSpacePage"
            @update-mute-status="updateMuteStatus"
          />
        </div>

        <Tabs
          v-model="innerSelectedTab"
          direction="horizontal"
          class="tui-workspacePage__tabs"
        >
          <Tab
            id="discussion"
            :name="$str('discuss_tab_label', 'container_workspace')"
          >
            <WorkspaceContentLayout
              :max-units="units"
              :grid-direction="direction"
              class="tui-workspacePage__tabs-discussionTab"
            >
              <template v-slot:content>
                <WorkspaceDiscussionTab
                  v-if="workspace.interactor.can_view_discussions"
                  :workspace-total-discussions="workspace.total_discussions"
                  :selected-sort="discussionSortOption"
                  :workspace-id="workspaceId"
                  :workspace-context-id="workspace.context_id"
                  @add-discussion="addDiscussion"
                />

                <p v-else class="tui-workspacePage__tabs-text">
                  {{ $str('visibility_help', 'container_workspace') }}
                </p>
              </template>

              <WorkspaceDescription
                v-if="workspace.description"
                slot="side"
                :time-description="workspace.time_description"
                :description="workspace.description"
              />
            </WorkspaceContentLayout>
          </Tab>

          <Tab
            v-if="showLibraryTab"
            id="library"
            :disabled="!workspace.interactor.can_view_library"
            :name="$str('library_tab_label', 'container_workspace')"
          >
            <WorkspaceLibraryTab
              :workspace-id="workspaceId"
              :units="units"
              :grid-direction="direction"
            />
          </Tab>

          <Tab
            id="members"
            :disabled="!workspace.interactor.can_view_members"
            :name="
              $str(
                'member_tab_label',
                'container_workspace',
                workspace.total_members
              )
            "
          >
            <WorkspaceContentLayout
              :grid-direction="direction"
              :max-units="units"
            >
              <WorkspaceMembersTab
                slot="content"
                :workspace-id="workspaceId"
                :can-view-member-requests="
                  workspace.interactor.can_view_member_requests
                "
                :total-member-requests="workspace.total_member_requests"
                :total-members="workspace.total_members"
                :selected-sort="memberSortOption"
              />
            </WorkspaceContentLayout>
          </Tab>
          <Tab
            v-if="workspace.interactor.can_add_audiences"
            id="audiences"
            :name="
              $str(
                'audiences_tab_label',
                'container_workspace',
                workspace.total_audiences
              )
            "
          >
            <WorkspaceAudiencesTab
              :workspace="workspace"
              :grid-direction="direction"
              :max-units="units"
              @add-audience="handleAddAudiencesClick"
            />
          </Tab>
        </Tabs>

        <AudienceAdder
          :open="audienceAdder.showing"
          :context-id="workspace.context_id"
          :force-results-loading="audienceAdder.preparing"
          :show-loading-btn="audienceAdder.adding"
          :existing-items="audienceAdder.currentAudienceIds"
          :tenant-scope="true"
          @add-button-clicked="handleAudienceAdderBegin"
          @added="handleAudienceAdderResult"
          @cancel="handleAudienceAdderCancel"
        >
          <template v-slot:notices>
            <NotificationBanner
              :message="
                $str('add_audiences_background_notice', 'container_workspace')
              "
            />
          </template>
        </AudienceAdder>
      </div>
    </template>
  </Layout>
</template>

<script>
import AudienceAdder from 'tui/components/adder/AudienceAdder';
import Layout from 'tui/components/layouts/LayoutTwoColumn';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';
import WorkspaceMembersTab from 'container_workspace/components/content/tabs/WorkspaceMembersTab';
import WorkspaceAudiencesTab from 'container_workspace/components/content/tabs/WorkspaceAudiencesTab';
import WorkspaceDiscussionTab from 'container_workspace/components/content/tabs/WorkspaceDiscussionTab';
import WorkspaceLibraryTab from 'container_workspace/components/content/tabs/WorkspaceLibraryTab';
import WorkspacePageHeader from 'container_workspace/components/head/WorkspacePageHeader';
import { notify } from 'tui/notifications';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import WorkspaceDescription from 'container_workspace/components/sidepanel/WorkspaceDescription';
import WorkspaceContentLayout from 'container_workspace/components/content/WorkspaceContentLayout';
import WorkspacePrimaryAction from 'container_workspace/components/action/WorkspacePrimaryAction';
import apolloClient from 'tui/apollo_client';
import { config } from 'tui/config';
import { parseQueryString, url } from 'tui/util';

// GraphQL queries
import addAudiences from 'container_workspace/graphql/add_audiences';
import audienceIdsQuery from 'container_workspace/graphql/audience_ids';
import getWorkspace from 'container_workspace/graphql/get_workspace';
import notifications from 'container_workspace/graphql/notifications';

export default {
  components: {
    AudienceAdder,
    NotificationBanner,
    WorkspaceAudiencesTab,
    WorkspacePrimaryAction,
    Layout,
    SidePanel,
    WorkspaceMenu,
    Tabs,
    Tab,
    WorkspaceMembersTab,
    WorkspaceDiscussionTab,
    WorkspaceLibraryTab,
    WorkspacePageHeader,
    WorkspaceDescription,
    WorkspaceContentLayout,
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },

    memberSortOption: {
      type: String,
      required: true,
    },

    discussionSortOption: {
      type: String,
      required: true,
    },

    selectedTab: {
      type: String,
      default: 'discussion',
      validator(prop) {
        return ['discussion', 'members', 'library', 'audiences'].includes(prop);
      },
    },

    showLibraryTab: {
      type: Boolean,
      required: true,
    },
  },

  apollo: {
    workspace: {
      query: getWorkspace,
      context: { batch: true },
      variables() {
        return {
          id: this.workspaceId,
          theme: config.theme.name,
        };
      },
    },

    notifications: {
      query: notifications,
      context: { batch: true },
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
  },

  data() {
    return {
      workspace: null,
      innerSelectedTab: this.selectedTab,
      audienceAdder: {
        showing: false,
        adding: false,
        preparing: false,
        currentAudienceIds: [],
      },
    };
  },

  watch: {
    innerSelectedTab(newTab) {
      if (
        newTab &&
        ['discussion', 'members', 'library', 'audiences'].includes(newTab)
      ) {
        const params = parseQueryString(window.location.search);
        if (params.tab !== newTab) {
          params.tab = newTab;
          window.history.pushState(
            null,
            null,
            url(window.location.pathname, params)
          );
        }

        if (newTab === 'members') {
          this.reloadWorkspace();
        }
      }
    },
  },

  methods: {
    /**
     * Update workspace cache data.
     * @param {Object} workspace
     */
    updateWorkspace(workspace) {
      apolloClient.writeQuery({
        query: getWorkspace,
        variables: {
          id: this.workspaceId,
          theme: config.theme.name,
        },
        data: {
          workspace: workspace,
        },
      });
    },

    reloadWorkspace() {
      this.$apollo.queries.workspace.refetch();
    },

    /**
     * Redirect to index page and let the index page resolve the
     * next workspace where user should be redirect to.
     */
    redirectToSpacePage() {
      document.location.href = this.$url(
        '/container/type/workspace/index.php',
        { hold_notification: 1 }
      );
    },

    addDiscussion() {
      let { workspace } = apolloClient.readQuery({
        query: getWorkspace,
        variables: {
          id: this.workspaceId,
          theme: config.theme.name,
        },
      });

      workspace = Object.assign({}, workspace, {
        total_discussions: workspace.total_discussions + 1,
      });

      apolloClient.writeQuery({
        query: getWorkspace,
        variables: {
          id: this.workspaceId,
          theme: config.theme.name,
        },
        data: { workspace },
      });
    },

    /**
     * Redirect to a newly created workspace.
     * @param {Number} id
     */
    redirectToWorkspace({ id }) {
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php',
        { id: id }
      );
    },

    /**
     *
     * @param {Boolean} status
     */
    updateMuteStatus(status) {
      let { workspace } = apolloClient.readQuery({
        query: getWorkspace,
        variables: {
          id: this.workspaceId,
          theme: config.theme.name,
        },
      });

      workspace = Object.assign({}, workspace);
      workspace.interactor = Object.assign({}, workspace.interactor);

      workspace.interactor.muted = status;
      apolloClient.writeQuery({
        query: getWorkspace,
        variables: {
          id: this.workspaceId,
          theme: config.theme.name,
        },

        data: { workspace },
      });
    },

    /**
     * Show add audiences UI
     */
    async handleAddAudiencesClick() {
      this.audienceAdder.showing = true;
      this.audienceAdder.preparing = true;
      this.audienceAdder.adding = false;

      this.audienceAdder.currentAudienceIds = await this.getCurrentAudienceIds();
      this.audienceAdder.preparing = false;
    },

    /**
     * Handle event where audience adder is getting ready to give us the results back.
     */
    handleAudienceAdderBegin() {
      this.audienceAdder.adding = true;
    },

    /**
     * Handle result from audience adder.
     *
     * @param {{ ids: string[] }} audiences
     */
    async handleAudienceAdderResult(audiences) {
      if (audiences.length === 0) {
        return;
      }
      try {
        await this.$apollo.mutate({
          mutation: addAudiences,
          variables: {
            input: {
              workspace_id: this.workspaceId,
              audience_ids: audiences.ids,
            },
          },
          refetchQueries: [
            'container_workspace_get_workspace',
            'container_workspace_audiences',
          ],
          awaitRefetchQueries: true,
        });
      } finally {
        this.audienceAdder.showing = false;
      }

      notify({
        message: this.$str(
          'add_audiences_started_message',
          'container_workspace'
        ),
      });
    },

    /**
     * Handle the event when the cancel button in the audience adder is clicked.
     */
    handleAudienceAdderCancel() {
      this.audienceAdder.showing = false;
    },

    /**
     * Get audience IDs in workspace.
     *
     * @returns {number[]}
     */
    async getCurrentAudienceIds() {
      const result = await this.$apollo.query({
        query: audienceIdsQuery,
        variables: { input: { workspace_id: this.workspaceId } },
        fetchPolicy: 'no-cache',
      });
      return result.data.ids;
    },
  },
};
</script>
<lang-strings>
  {
    "container_workspace": [
      "add_audiences_background_notice",
      "add_audiences_started_message",
      "audiences_tab_label",
      "discuss_tab_label",
      "library_tab_label",
      "member_tab_label",
      "visibility_help"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspacePage {
  .tui-layoutTwoColumn {
    &__heading {
      display: none;
    }
  }

  &__mainContent {
    margin: 0;

    @media (min-width: $tui-screen-sm) {
      margin: var(--gap-8) var(--gap-8) 0 0;
    }
  }

  &__primaryAction {
    display: flex;
    width: 100%;
    padding: 0 var(--gap-4);

    @media (min-width: $tui-screen-sm) {
      justify-content: flex-end;
    }

    @media (min-width: $tui-screen-md) {
      padding: var(--gap-4) 0 0;
    }

    &-action {
      width: 100%;

      @media (min-width: $tui-screen-sm) {
        width: inherit;
      }
    }
  }

  &__tabs {
    padding: var(--gap-4);

    .tui-tabs__tabs {
      padding-left: var(--gap-4);
      @media (min-width: $tui-screen-sm) {
        padding: 0;
      }
    }

    @media (min-width: $tui-screen-md) {
      padding: 0;
    }

    &-text {
      @include tui-font-body();
    }

    &-discussionTab {
      margin-top: var(--gap-4);
    }
  }
}
</style>
