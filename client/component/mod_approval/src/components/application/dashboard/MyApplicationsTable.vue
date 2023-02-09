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
  <div class="tui-mod_approval-myApplicationsTable">
    <p
      v-if="zeroMyApplications && $matches('myApplications.ready')"
      class="tui-mod_approval-myApplicationsTable__zeroApplications"
    >
      {{ $str('no_applications', 'mod_approval') }}
    </p>
    <div v-else>
      <FilterBar
        :title="$str('filter_applications', 'mod_approval')"
        class="tui-mod_approval-myApplicationsTable__filterBar"
      >
        <template v-slot:filters-left>
          <SelectFilter
            :value="$selectors.getMyApplicationsOverallProgress($context)"
            :label="$str('overall_progress', 'mod_approval')"
            :options="progressOptions"
            :show-label="true"
            @input="
              optionId => filter({ key: 'overall_progress', value: optionId })
            "
          />
        </template>
      </FilterBar>
      <div class="tui-mod_approval-myApplicationsTable__sort">
        <h5>
          {{
            $str('total_of', 'mod_approval', {
              count: myApplications.length,
              total,
            })
          }}
        </h5>
        <SelectFilter
          v-if="total !== 0"
          :value="$selectors.getMyApplicationsSortBy($context)"
          :label="$str('sort_by', 'mod_approval')"
          :options="sortbyOptions"
          :show-label="true"
          @input="optionId => sort(optionId)"
        />
      </div>
      <Table
        :data="myApplications"
        :header-has-loaded="true"
        :loading-preview="tableLoading"
        :loading-preview-rows="loadingPreviewRows"
        :loading-overlay-active="true"
        :no-items-text="$str('no_applications_match', 'mod_approval')"
      >
        <template v-slot:header-row>
          <HeaderCell size="4" valign="center">
            {{ $str('application_id', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('application_title', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('application_type', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('submitted_on', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('overall_progress', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="1" valign="center"
            ><span class="sr-only">{{
              $str('actions', 'mod_approval')
            }}</span></HeaderCell
          >
        </template>
        <template v-slot:row="{ row }">
          <Cell
            size="4"
            :column-header="$str('application_id', 'mod_approval')"
          >
            <a :href="applicationIdUrl(row)"> {{ row.id_number }}</a>
          </Cell>
          <Cell
            size="4"
            :column-header="$str('application_title', 'mod_approval')"
          >
            {{ row.title }}
          </Cell>
          <Cell
            size="4"
            :column-header="$str('application_type', 'mod_approval')"
          >
            {{ row.workflow_type }}
          </Cell>
          <Cell size="4" :column-header="$str('submitted_on', 'mod_approval')">
            {{ row.submitted }}
          </Cell>
          <Cell
            size="4"
            :column-header="$str('overall_progress', 'mod_approval')"
          >
            {{ row.overall_progress_label }}
          </Cell>
          <Cell
            size="1"
            :column-header="$str('more_actions', 'mod_approval')"
            align="center"
          >
            <ApplicationActions :application="row" />
          </Cell>
        </template>
      </Table>
      <Paging
        v-if="total > 0"
        class="tui-mod_approval-myApplicationsTable__paging"
        :page="$selectors.getMyApplicationsPage($context)"
        :items-per-page="limit"
        :total-items="total"
        @page-change="page => pageChange(page)"
        @count-change="count => countChange(count)"
      />
    </div>
  </div>
</template>

<script>
import {
  MOD_APPROVAL__DASHBOARD_TABLE,
  MY_APPLICATIONS,
  ApplicationTableColumn,
  OverallProgressState,
} from 'mod_approval/constants';
import { getApplicationPageUrl } from 'mod_approval/item_selectors/application';

import SelectFilter from 'tui/components/filters/SelectFilter';
import Table from 'tui/components/datatable/Table';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Paging from 'tui/components/paging/Paging';
import FilterBar from 'tui/components/filters/FilterBar';
import ApplicationActions from 'mod_approval/components/application/dashboard/ApplicationActions';

export default {
  components: {
    ApplicationActions,
    Cell,
    FilterBar,
    HeaderCell,
    Paging,
    SelectFilter,
    Table,
  },

  props: {
    progressOptions: Array,
  },

  data() {
    return {
      OverallProgressState,
      tab: MY_APPLICATIONS,
      sortbyOptions: [
        {
          id: ApplicationTableColumn.SUBMITTED,
          label: this.$str('submitted_on', 'mod_approval'),
        },
        {
          id: ApplicationTableColumn.ID_NUMBER,
          label: this.$str('application_id', 'mod_approval'),
        },
        {
          id: ApplicationTableColumn.TITLE,
          label: this.$str('application_title', 'mod_approval'),
        },
        {
          id: ApplicationTableColumn.WORKFLOW_TYPE_NAME,
          label: this.$str('application_type', 'mod_approval'),
        },
      ],
    };
  },

  xState: {
    machineId: MOD_APPROVAL__DASHBOARD_TABLE,
  },

  computed: {
    tableLoading() {
      return [
        'myApplications.loading',
        'myApplications.querying',
        'myApplications.deletingApplication',
      ].some(this.$matches);
    },

    myApplications() {
      return this.$selectors.getMyApplications(this.$context);
    },

    total() {
      return this.$selectors.getMyApplicationsTotal(this.$context);
    },

    zeroMyApplications() {
      return this.$selectors.getZeroMyApplications(this.$context);
    },

    limit() {
      return this.$selectors.getMyApplicationsLimit(this.$context);
    },

    loadingPreviewRows() {
      return this.myApplications.length !== 0 &&
        this.myApplications.length < this.limit
        ? this.myApplications.length
        : this.limit;
    },
  },

  methods: {
    filter({ key, value }) {
      if (value === 'ALL' || value === '') {
        value = undefined;
      }

      this.$send({
        type: this.$e.FILTER,
        path: [MY_APPLICATIONS, 'query_options', 'filters', key],
        value,
      });
    },

    countChange(count) {
      this.$send({
        type: this.$e.CHANGE_COUNT,
        path: [MY_APPLICATIONS, 'query_options', 'pagination', 'limit'],
        value: count,
      });
    },

    pageChange(page) {
      this.$send({
        type: this.$e.CHANGE_PAGE,
        path: [MY_APPLICATIONS, 'query_options', 'pagination', 'page'],
        value: page,
      });
    },

    sort(column) {
      this.$send({
        type: this.$e.SORT,
        path: [MY_APPLICATIONS, 'query_options', 'sort_by'],
        value: column,
      });
    },

    applicationIdUrl(application) {
      const applicationUrl = getApplicationPageUrl(application);
      if (applicationUrl) {
        return this.$url(
          applicationUrl,
          this.$selectors.getParsedParams(this.$context)
        );
      }

      return '';
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-myApplicationsTable {
  &__loader {
    margin-top: var(--gap-6);
  }

  &__zeroApplications {
    margin-top: var(--gap-6);
    font-size: var(--font-size-15);
  }

  &__filterBar {
    margin-top: var(--gap-2);
    border-top: 0;
  }

  &__sort {
    display: flex;
    justify-content: space-between;
    padding: var(--gap-5) 0;

    h5 {
      @include tui-font-body();
      font-weight: bold;
    }
  }

  &__table {
    &--querying {
      .tui-dataTableRow {
        opacity: 0.4;
      }

      // <MoreIcon /> has no :disabled prop
      a,
      button {
        pointer-events: none;
      }
    }
  }

  &__paging {
    margin-top: var(--gap-5);
  }
}
</style>

<lang-strings>
{
  "mod_approval": [
    "actions",
    "application_title",
    "application_type",
    "application_id",
    "filter_applications",
    "workflow_name",
    "no_applications",
    "no_applications_match",
    "overall_progress",
    "sort_by",
    "submitted_on",
    "more_actions",
    "total_of"
  ]
}
</lang-strings>
