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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module totara_perform
-->

<template>
  <Layout
    :title="$str('perform:manage_activity', 'mod_perform')"
    class="tui-performManageActivityList"
  >
    <template v-slot:header-buttons>
      <Button
        v-if="canAdd"
        :styleclass="{ primary: true }"
        :disabled="$apollo.loading"
        :text="$str('add_activity', 'mod_perform')"
        @click="showCreateModal()"
      />
    </template>

    <template v-slot:modals>
      <ModalPresenter :open="modalOpen" @request-close="modalRequestClose">
        <CreateActivityForm
          :types="activityTypes"
          @mutation-success="activityCreated"
          @mutation-error="creationError"
        />
      </ModalPresenter>
    </template>

    <template v-slot:content>
      <div class="tui-performManageActivityList__content">
        <ActivityFilters
          v-if="activitiesExist"
          :types="activityTypes"
          :shown="paginatedActivities.items.length"
          :total="paginatedActivities.total"
          @update="applyFilters"
        />

        <Loader :loading="$apollo.loading">
          <Table
            :data="paginatedActivities.items"
            :no-items-text="
              activitiesExist
                ? $str('manage_activity_list_none_filtered', 'mod_perform')
                : $str('manage_activity_list_none_created', 'mod_perform')
            "
            class="tui-performManageActivityList__table"
          >
            <template v-slot:header-row>
              <HeaderCell size="8">
                {{ $str('view_name', 'mod_perform') }}
              </HeaderCell>
              <HeaderCell size="2">
                {{ $str('view_type', 'mod_perform') }}
              </HeaderCell>
              <HeaderCell size="2">
                {{ $str('view_creation_date', 'mod_perform') }}
              </HeaderCell>
              <HeaderCell size="2">
                {{ $str('view_status', 'mod_perform') }}
              </HeaderCell>
              <HeaderCell size="1" />
            </template>
            <template v-slot:row="{ row }">
              <Cell size="8" :column-header="$str('view_name', 'mod_perform')">
                <a v-if="row.can_manage" :href="getEditActivityUrl(row.id)">{{
                  row.name
                }}</a>
                <div v-else>{{ row.name }}</div>
              </Cell>
              <Cell size="2" :column-header="$str('view_type', 'mod_perform')">
                {{ row.type.display_name }}
              </Cell>
              <Cell
                size="2"
                :column-header="$str('view_creation_date', 'mod_perform')"
              >
                {{ row.created_at }}
              </Cell>
              <Cell
                size="2"
                :column-header="$str('view_status', 'mod_perform')"
              >
                {{ row.state_details.display_name }}
              </Cell>
              <Cell
                size="1"
                :column-header="$str('view_actions', 'mod_perform')"
              >
                <ActivityActions
                  :activity="row"
                  @refetch="refetchActivities"
                  @activity-cloned="activityCloned"
                />
              </Cell>
            </template>
          </Table>

          <div
            v-if="!$apollo.loading && hasMoreActivities"
            class="tui-performManageActivityList__loadMore"
          >
            <Button :text="$str('loadmore', 'totara_core')" @click="loadMore" />
          </div>
        </Loader>
      </div>
    </template>
  </Layout>
</template>

<script>
import ActivityActions from 'mod_perform/components/activities_list/ActivityActions';
import ActivityFilters from 'mod_perform/components/activities_list/AdminActivityFilters';
import Button from 'tui/components/buttons/Button';
import Cell from 'tui/components/datatable/Cell';
import CreateActivityForm from 'mod_perform/components/manage_activity/CreateActivityForm';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import Loader from 'tui/components/loading/Loader';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Table from 'tui/components/datatable/Table';
import { notify } from 'tui/notifications';
import { redirectWithPost } from 'tui/dom/form';
import { DEFAULT_ITEMS_PER_PAGE } from 'mod_perform/constants';
// Query
import performActivitiesQuery from 'mod_perform/graphql/paginated_activities';
import activityTypesQuery from 'mod_perform/graphql/activity_types';

export default {
  components: {
    ActivityActions,
    ActivityFilters,
    Button,
    Cell,
    CreateActivityForm,
    HeaderCell,
    Layout,
    Loader,
    ModalPresenter,
    Table,
  },
  props: {
    editUrl: {
      required: true,
      type: String,
    },
    canAdd: {
      required: true,
      type: Boolean,
    },
  },
  data() {
    return {
      paginatedActivities: {
        items: [],
        total: null,
        next_cursor: null,
      },
      activityTypes: [],
      pageCount: 0,
      modalOpen: false,
      appliedFilters: null,
      appliedSorting: null,
      filtersHaveChanged: false,
    };
  },
  computed: {
    hasMoreActivities() {
      return (
        this.paginatedActivities.next_cursor !== null &&
        this.paginatedActivities.next_cursor.length > 0
      );
    },

    /**
     * Are there any activities that the current user can manage at all?
     * @return {Boolean}
     */
    activitiesExist() {
      if (this.appliedFilters != null) {
        return true;
      }

      if (this.$apollo.loading) {
        return false;
      }

      return this.paginatedActivities.items.length > 0;
    },
  },
  methods: {
    /**
     * Handler for a create mutation error.
     * Shows a generic saving error toast.
     */
    creationError() {
      notify({
        message: this.$str('toast_error_create_activity', 'mod_perform'),
        type: 'error',
      });
    },

    /**
     * Open the create activity modal.
     */
    showCreateModal() {
      this.modalOpen = true;
    },

    /**
     * Close the create activity modal.
     */
    modalRequestClose() {
      this.modalOpen = false;
    },

    /**
     * Activity created observer.
     * @param {Object} activity
     */
    activityCreated(activity) {
      this.redirectToManageActivity(activity.id);
    },

    /**
     * Activity cloned observer.
     * @param {Object} clone
     * @param {Number} id
     */
    activityCloned({ clone, id }) {
      this.redirectToManageActivity(clone.id, {
        activity_cloned: true,
        cloned_activity_id: id,
      });
    },

    /**
     * Full page redirect to the management screen for a specific perform activity.
     * @param {Number} activityId
     * @param {Object} postParameters
     */
    redirectToManageActivity(activityId, postParameters) {
      const activityUrl = this.getEditActivityUrl(activityId);
      postParameters
        ? redirectWithPost(activityUrl, postParameters)
        : (window.location = activityUrl);
    },

    /**
     * Get the url to manage a specific perform activity.
     * @param {Number} activityId
     */
    getEditActivityUrl(activityId) {
      return this.$url(this.editUrl, { activity_id: activityId });
    },

    /**
     * Reload the activities list.
     */
    refetchActivities() {
      let queryVariables = this.getPaginationOption({
        limit: this.pageCount * DEFAULT_ITEMS_PER_PAGE,
      });
      let pageCount = this.pageCount;
      this.$apollo.queries.paginatedActivities
        .refetch(queryVariables)
        .then(() => {
          this.pageCount = pageCount;
        });
    },

    /**
     * Get the params for the query.
     * @param {Number} limit
     * @param {String|NULL} cursor
     */
    getPaginationOption({ limit, cursor }) {
      if (this.filtersHaveChanged) {
        this.filtersHaveChanged = false;
        return {
          query_options_input: {
            pagination: {
              cursor: null,
            },
            filters: this.appliedFilters,
            sort_by: this.appliedSorting,
          },
        };
      }

      if (!cursor) {
        cursor = null;
      }

      let paginationOptions = {
        cursor,
      };

      if (limit) {
        paginationOptions.limit = limit;
      }

      return {
        query_options_input: {
          pagination: paginationOptions,
          filters: this.appliedFilters,
          sort_by: this.appliedSorting,
        },
      };
    },

    /**
     * Load more items to activities list.
     */
    loadMore() {
      this.$apollo.queries.paginatedActivities.fetchMore({
        variables: this.getPaginationOption({
          cursor: this.paginatedActivities.next_cursor,
        }),
        updateQuery: (previousResult, { fetchMoreResult }) => {
          fetchMoreResult.mod_perform_paginated_activities.items.unshift(
            ...this.paginatedActivities.items
          );

          return fetchMoreResult;
        },
      });
    },

    /**
     * Refetch the activities list with the updated filters.
     * @param {Object} filters
     * @param {String} sorting
     */
    applyFilters({ filters, sorting }) {
      this.appliedFilters = filters;
      this.appliedSorting = sorting;
      this.filtersHaveChanged = true;
      this.pageCount = 0;
      this.refetchActivities();
    },
  },

  apollo: {
    paginatedActivities: {
      query: performActivitiesQuery,
      variables() {
        return {
          query_options_input: {
            pagination: {
              cursor: null,
            },
          },
        };
      },
      update(data) {
        this.pageCount++;

        return data.mod_perform_paginated_activities;
      },
    },
    activityTypes: {
      query: activityTypesQuery,
      update({ mod_perform_activity_types: types }) {
        return types
          .map(type => {
            return { id: type.id, label: type.display_name };
          })
          .sort((a, b) => a.label.localeCompare(b.label));
      },
    },
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "add_activity",
      "manage_activity_list_none_created",
      "manage_activity_list_none_filtered",
      "perform:manage_activity",
      "showing_activities",
      "toast_error_create_activity",
      "view_actions",
      "view_creation_date",
      "view_name",
      "view_status",
      "view_type"
    ],
    "totara_core": [
      "loadmore"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityList {
  &__content {
    & > * + * {
      margin-top: var(--gap-8);
    }
  }

  &__loadMore {
    margin-top: var(--gap-3);
    text-align: center;
  }
}
</style>
