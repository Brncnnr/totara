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
  @module mod_approval
-->
<template>
  <div class="tui-mod_approval-applicationsTable">
    <Tabs
      :controlled="true"
      :selected="selected"
      class="tui-mod_approval-applicationsTable__tabs"
      @input="event => (selected !== event ? $send($e.SWITCH_TAB) : null)"
    >
      <Tab
        v-if="isApprover"
        :id="tabs[0]"
        :key="tabs[0]"
        :name="$str('applications_from_others', 'mod_approval')"
      />
      <Tab
        :id="tabs[1]"
        :key="tabs[1]"
        :name="$str('your_applications', 'mod_approval')"
      />
    </Tabs>
    <MyApplicationsTable
      v-if="$matches('myApplications')"
      :progress-options="progressOptions"
    />
    <FromOthersTable
      v-else
      :progress-options="progressOptions"
      :your-progress-options="yourProgressOptions"
    />
    <ApplicationDeleteModal
      :open="confirmingDelete"
      :title="$selectors.getToDeleteApplicationTitle($context)"
      @confirm="$send($e.DELETE)"
      @cancel="$send($e.CANCEL)"
    />
  </div>
</template>

<script>
import {
  APPLICATIONS_FROM_OTHERS,
  MY_APPLICATIONS,
  TAB,
  MY,
  OTHERS,
} from 'mod_approval/constants';
import MyApplicationsTable from 'mod_approval/components/application/dashboard/MyApplicationsTable';
import FromOthersTable from 'mod_approval/components/application/dashboard/FromOthersTable';
import ApplicationDeleteModal from 'mod_approval/components/application/ApplicationDeleteModal';
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';
import makeApplicationsTableMachine, {
  tabs,
} from 'mod_approval/application/index/table/machine';
import {
  mapQueryParamsToContext,
  mapContextToQueryParams,
  setTableParams,
} from 'mod_approval/application/index/table/helpers';
import {
  getOthersApplicationsQueryOptions,
  getMyApplicationsQueryOptions,
} from 'mod_approval/application/index/table/selectors';

const hasMyApplications = path => path.includes(MY_APPLICATIONS);
const hasOthersApplications = path => path.includes(APPLICATIONS_FROM_OTHERS);

export default {
  components: {
    FromOthersTable,
    MyApplicationsTable,
    ApplicationDeleteModal,
    Tabs,
    Tab,
  },

  props: {
    pageProps: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      tabs,
      progressOptions: [
        { id: 'ALL', label: this.$str('filter_all', 'mod_approval') },
        { id: 'DRAFT', label: this.$str('filter_draft', 'mod_approval') },
        {
          id: 'IN_PROGRESS',
          label: this.$str('filter_in_progress', 'mod_approval'),
        },
        {
          id: 'FINISHED',
          label: this.$str('filter_finished', 'mod_approval'),
        },
        { id: 'REJECTED', label: this.$str('filter_rejected', 'mod_approval') },
        {
          id: 'WITHDRAWN',
          label: this.$str('filter_withdrawn', 'mod_approval'),
        },
      ],
      yourProgressOptions: [
        { id: 'ALL', label: this.$str('filter_all', 'mod_approval') },
        { id: 'PENDING', label: this.$str('filter_pending', 'mod_approval') },
        { id: 'APPROVED', label: this.$str('filter_approved', 'mod_approval') },
        { id: 'REJECTED', label: this.$str('filter_rejected', 'mod_approval') },
        { id: 'NA', label: this.$str('filter_na', 'mod_approval') },
      ],
    };
  },

  xState: {
    machine() {
      return makeApplicationsTableMachine({
        canApprove: this.pageProps.tabs['applications-from-others'],
      });
    },

    /**
     * Ensures the query params in the url are appropriate for the active tab.
     */
    mapStateToQueryParams(statePaths, prevStatePaths, context) {
      if (
        statePaths.find(hasMyApplications) &&
        !prevStatePaths.find(hasMyApplications)
      ) {
        const myApplicationsQueryOptions = getMyApplicationsQueryOptions(
          context
        );
        return Object.assign(setTableParams(myApplicationsQueryOptions, MY), {
          [TAB]: MY,
          [`${OTHERS}.pagination.page`]: undefined,
          [`${OTHERS}.pagination.limit`]: undefined,
          [`${OTHERS}.filters.overall_progress`]: undefined,
          [`${OTHERS}.filters.your_progress`]: undefined,
          [`${OTHERS}.sort_by`]: undefined,
        });
      }

      if (
        statePaths.find(hasOthersApplications) &&
        !prevStatePaths.find(hasOthersApplications)
      ) {
        const othersApplicationsQueryOptions = getOthersApplicationsQueryOptions(
          context
        );
        return Object.assign(
          setTableParams(othersApplicationsQueryOptions, OTHERS),
          {
            [TAB]: OTHERS,
            [`${MY}.pagination.page`]: undefined,
            [`${MY}.pagination.limit`]: undefined,
            [`${MY}.filters.overall_progress`]: undefined,
            [`${MY}.sort_by`]: undefined,
          }
        );
      }

      return false;
    },

    mapQueryParamsToContext(params) {
      return mapQueryParamsToContext(params);
    },

    mapContextToQueryParams(context, prevContext) {
      return mapContextToQueryParams(context, prevContext);
    },
  },

  computed: {
    isApprover() {
      return this.pageProps.tabs['applications-from-others'];
    },

    selected() {
      if (!this.x) {
        return tabs[0];
      }
      return Object.keys(this.x.state.value)[0];
    },

    confirmingDelete() {
      return [
        { myApplications: 'confirmDelete' },
        { applicationsFromOthers: 'confirmDelete' },
      ].some(this.$matches);
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-applicationsTable {
  &__tabs {
    margin-top: var(--gap-4);
  }
}
</style>

<lang-strings>
{
  "mod_approval": [
    "all_applications",
    "applicant",
    "applications_from_others",
    "error:clone_application",
    "filter_all",
    "filter_approved",
    "filter_draft",
    "filter_finished",
    "filter_in_progress",
    "filter_na",
    "filter_pending",
    "filter_rejected",
    "filter_withdrawn",
    "submitted_on",
    "success:clone_application",
    "success:delete_application",
    "your_applications"
  ]
}
</lang-strings>
