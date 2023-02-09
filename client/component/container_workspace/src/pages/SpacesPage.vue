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
  <Responsive
    :breakpoints="boundaries"
    class="tui-spacesPage"
    @responsive-resize="currentBoundaryName = $event"
  >
    <PageHeading :title="$str('find_spaces', 'container_workspace')" />

    <WorkspaceFilter
      :selected-source="inner.selectedSource"
      :selected-sort="inner.selectedSort"
      :selected-access="inner.selectedAccess"
      :search-term="inner.searchTerm"
      :spaces-cursor="workspace.cursor"
      :spaces-is-loading="$apollo.queries.workspace.loading"
      class="tui-spacesPage__filter"
      @submit-search="updateFilter"
      @filter="updateFilter"
      @clear="updateFilter"
    />

    <SpaceCardsGrid
      :max-grid-units="12"
      :workspace-units="cardUnits"
      :workspaces="workspace.items"
      :is-loading="$apollo.queries.workspace.loading"
      :cursor="workspace.cursor"
      class="tui-spacesPage__grid"
      @join-workspace="joinWorkspace"
      @request-to-join-workspace="requestToJoinWorkspace"
      @leave-workspace="leaveWorkspace"
      @loadmoreitems="loadMoreItems"
    />
  </Responsive>
</template>

<script>
import PageHeading from 'tui/components/layouts/PageHeading';
import Responsive from 'tui/components/responsive/Responsive';
import WorkspaceFilter from 'container_workspace/components/filter/WorkspaceFilter';
import SpaceCardsGrid from 'container_workspace/components/grid/SpaceCardsGrid';
import { cardGrid } from 'container_workspace/index';
import apolloClient from 'tui/apollo_client';
import { config } from 'tui/config';

// GraphQL queries
import findWorkspaces from 'container_workspace/graphql/find_workspaces';

export default {
  components: {
    SpaceCardsGrid,
    WorkspaceFilter,
    PageHeading,
    Responsive,
  },

  props: {
    selectedSource: {
      type: [String, Number],
      default: null,
    },

    selectedSort: {
      type: String,
      required: true,
    },

    searchTerm: {
      type: String,
      default: '',
    },

    selectedAccess: {
      type: String,
      default: null,
    },
  },

  apollo: {
    workspace: {
      query: findWorkspaces,
      fetchPolicy: 'network-only',
      variables() {
        return {
          source: this.inner.selectedSource,
          sort: this.inner.selectedSort,
          search_term: this.inner.searchTerm,
          access: this.inner.selectedAccess,
          theme: config.theme.name,
        };
      },

      /**
       *
       * @param {Object[]}      workspaces
       * @param {Object}        cursor
       * @returns {Object}
       */
      update({ workspaces, cursor }) {
        return {
          cursor: cursor,
          items: workspaces,
        };
      },
    },
  },

  data() {
    return {
      workspace: {
        cursor: {
          total: 0,
          next: null,
        },
        items: [],
      },

      // This is to cache the props inside the page. So that we won't change the props by any accidents.
      inner: {
        selectedSource: this.selectedSource,
        selectedSort: this.selectedSort,
        searchTerm: this.searchTerm,
        selectedAccess: this.selectedAccess,
      },

      currentBoundaryName: 'l',
    };
  },

  computed: {
    boundaries() {
      return Object.values(cardGrid);
    },

    /**
     *
     * @returns {Number}
     */
    cardUnits() {
      if (!cardGrid[this.currentBoundaryName]) {
        // Default to 2.
        return 2;
      }

      return cardGrid[this.currentBoundaryName].cardUnits;
    },

    queryVariables() {
      return {
        source: this.inner.selectedSource,
        sort: this.inner.selectedSort,
        search_term: this.inner.searchTerm,
        access: this.inner.selectedAccess,
        theme: config.theme.name,
      };
    },
  },

  methods: {
    /**
     *
     * @param {String}        source
     * @param {String}        sort
     * @param {String|null}   searchTerm
     * @param {String|null}   access
     */
    updateFilter({ source, sort, searchTerm, access }) {
      this.inner.selectedSource = source;
      this.inner.selectedSort = sort;
      this.inner.searchTerm = searchTerm;
      this.inner.selectedAccess = access;
    },

    /**
     *
     * @param {Object} workspace_interactor
     * @param {String|Number} workspace_id
     */
    joinWorkspace({ workspace_id, workspace_interactor }) {
      const { workspaces, cursor } = apolloClient.readQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
      });

      apolloClient.writeQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
        data: {
          cursor: cursor,
          workspaces: workspaces.map(workspace => {
            if (workspace.id == workspace_id) {
              workspace = Object.assign({}, workspace);
              workspace.interactor = workspace_interactor;
            }

            return workspace;
          }),
        },
      });
    },

    /**
     * Handling request to join workspace.
     * @param {Number} workspace_id
     * @param {Object} workspace_interactor
     */
    requestToJoinWorkspace({ workspace_id, workspace_interactor }) {
      const { workspaces, cursor } = apolloClient.readQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
      });

      apolloClient.writeQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
        data: {
          cursor: cursor,
          workspaces: workspaces.map(workspace => {
            if (workspace.id == workspace_id) {
              workspace = Object.assign({}, workspace);
              workspace.interactor = workspace_interactor;
            }

            return workspace;
          }),
        },
      });
    },

    /**
     * Handling leave workspace
     * @param {Number|String} workspace_id
     * @param {Object} workspace_interactor
     */
    leaveWorkspace({ workspace_id, workspace_interactor }) {
      const { workspaces, cursor } = apolloClient.readQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
      });

      apolloClient.writeQuery({
        query: findWorkspaces,
        variables: this.queryVariables,
        data: {
          cursor: cursor,
          workspaces: workspaces.map(workspace => {
            if (workspace.id == workspace_id) {
              workspace = Object.assign({}, workspace);
              workspace.interactor = workspace_interactor;
            }

            return workspace;
          }),
        },
      });
    },

    async loadMoreItems() {
      if (!this.workspace.cursor.next) {
        return;
      }

      this.$apollo.queries.workspace.fetchMore({
        variables: {
          cursor: this.workspace.cursor.next,
          source: this.inner.selectedSource,
          sort: this.inner.selectedSort,
          search_term: this.inner.searchTerm,
          access: this.inner.selectedAccess,
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult;
          const newData = fetchMoreResult;
          const newList = oldData.workspaces.concat(newData.workspaces);

          return {
            cursor: newData.cursor,
            workspaces: newList,
          };
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "find_spaces"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-spacesPage {
  margin-top: var(--gap-12);
  padding: 0 var(--gap-8);

  &__filter {
    margin-top: var(--gap-8);
  }

  &__grid {
    margin-top: var(--gap-4);
  }
}
</style>
