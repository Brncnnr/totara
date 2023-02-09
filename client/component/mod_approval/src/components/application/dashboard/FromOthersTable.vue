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
  <div class="tui-mod_approval-otherApplicationsTable">
    <p
      v-if="zeroApplications && $matches('applicationsFromOthers.ready')"
      class="tui-mod_approval-otherApplicationsTable__zeroApplications"
    >
      {{ $str('no_applications_from_others', 'mod_approval') }}
    </p>
    <div v-else>
      <FilterBar
        :title="$str('filter_applications', 'mod_approval')"
        class="tui-mod_approval-otherApplicationsTable__filterBar"
      >
        <template v-slot:filters-left>
          <SelectFilter
            :value="$selectors.getOthersApplicationsOverallProgress($context)"
            :label="$str('overall_progress', 'mod_approval')"
            :options="progressOptions"
            :show-label="true"
            @input="
              optionId => filter({ key: 'overall_progress', value: optionId })
            "
          />
          <SelectFilter
            :value="$selectors.getOthersApplicationsYourProgress($context)"
            :label="$str('your_progress', 'mod_approval')"
            :options="yourProgressOptions"
            :show-label="true"
            @input="
              optionId => filter({ key: 'your_progress', value: optionId })
            "
          />
        </template>
        <template v-slot:filters-right>
          <SearchFilter
            :value="$selectors.getOthersApplicationsSearch($context)"
            :label="$str('search_by_applicant', 'mod_approval')"
            :placeholder="$str('search_by_applicant', 'mod_approval')"
            @input="search => filter({ key: 'applicant_name', value: search })"
          />
        </template>
      </FilterBar>
      <div class="tui-mod_approval-otherApplicationsTable__sort">
        <h5>
          {{
            $str('total_of', 'mod_approval', {
              count: applications.length,
              total,
            })
          }}
        </h5>
        <SelectFilter
          v-if="total !== 0"
          :value="$selectors.getOthersApplicationsSortBy($context)"
          :label="$str('sort_by', 'mod_approval')"
          :options="sortByOptions"
          :show-label="true"
          @input="column => sort(column)"
        />
      </div>
      <Table
        :data="applications"
        :no-items-text="$str('no_applications_match', 'mod_approval')"
        :header-has-loaded="true"
        :loading-preview="loadingPreview"
        :loading-preview-rows="loadingPreviewRows"
        :loading-overlay-active="true"
      >
        <template v-slot:header-row>
          <HeaderCell size="3" valign="center">
            {{ $str('application_id', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('application_title', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('application_type', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('applicant', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('submitted_on', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('your_progress', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
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
            size="3"
            :column-header="$str('application_id', 'mod_approval')"
          >
            <a :href="applicationIdUrl(row)"> {{ row.id_number }}</a>
          </Cell>
          <Cell
            size="2"
            :column-header="$str('application_title', 'mod_approval')"
          >
            {{ row.title }}
          </Cell>
          <Cell
            size="2"
            :column-header="$str('application_type', 'mod_approval')"
          >
            {{ row.workflow_type }}
          </Cell>
          <Cell size="2" :column-header="$str('applicant', 'mod_approval')">
            <MicroProfileCard :user="row.user" size="xxsmall" />
          </Cell>
          <Cell size="2" :column-header="$str('submitted_on', 'mod_approval')">
            {{ row.submitted }}
          </Cell>
          <Cell size="2" :column-header="$str('your_progress', 'mod_approval')">
            {{ row.your_progress_label }}
          </Cell>
          <Cell
            size="2"
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
    </div>
    <Paging
      v-if="total > 0"
      class="tui-mod_approval-otherApplicationsTable__paging"
      :page="$selectors.getOthersApplicationsPage($context)"
      :items-per-page="limit"
      :total-items="total"
      @page-change="page => pageChange(page)"
      @count-change="count => countChange(count)"
    />
  </div>
</template>

<script>
import {
  MOD_APPROVAL__DASHBOARD_TABLE,
  APPLICATIONS_FROM_OTHERS,
  ApplicationTableColumn,
} from 'mod_approval/constants';
import { getApplicationPageUrl } from 'mod_approval/item_selectors/application';

import SelectFilter from 'tui/components/filters/SelectFilter';

import MicroProfileCard from 'mod_approval/components/cards/MicroProfileCard';
import Table from 'tui/components/datatable/Table';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Paging from 'tui/components/paging/Paging';

import FilterBar from 'tui/components/filters/FilterBar';
import SearchFilter from 'tui/components/filters/SearchFilter';
import ApplicationActions from 'mod_approval/components/application/dashboard/ApplicationActions';

export default {
  components: {
    ApplicationActions,
    Cell,
    FilterBar,
    HeaderCell,
    MicroProfileCard,
    Paging,
    SearchFilter,
    SelectFilter,
    Table,
  },

  props: {
    progressOptions: Array,
    yourProgressOptions: Array,
  },

  data() {
    return {
      tab: APPLICATIONS_FROM_OTHERS,
      sortByOptions: [
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
        {
          id: ApplicationTableColumn.APPLICANT_NAME,
          label: this.$str('applicant', 'mod_approval'),
        },
      ],
    };
  },

  xState: {
    machineId: MOD_APPROVAL__DASHBOARD_TABLE,
  },

  computed: {
    applications() {
      return this.$context[APPLICATIONS_FROM_OTHERS]
        .mod_approval_others_applications.items;
    },

    total() {
      return this.$selectors.getApplicationsTotal(this.$context);
    },

    zeroApplications() {
      return this.$selectors.getZeroApplicationsFromOthers(this.$context);
    },

    limit() {
      return this.$selectors.getOthersApplicationsLimit(this.$context);
    },

    loadingPreview() {
      return [
        'applicationsFromOthers.loading',
        'applicationsFromOthers.querying',
        'applicationsFromOthers.deletingApplication',
      ].some(this.$matches);
    },

    loadingPreviewRows() {
      const count = this.applications.length;
      return count !== 0 && count < this.limit ? count : this.limit;
    },
  },

  methods: {
    getUserUrl(id) {
      return this.$url('/user/profile.php', { id });
    },

    filter({ key, value }) {
      if (value === 'ALL' || value === '') {
        value = undefined;
      }

      this.$send({
        type: this.$e.FILTER,
        path: [APPLICATIONS_FROM_OTHERS, 'query_options', 'filters', key],
        value,
      });
    },

    sort(column) {
      this.$send({
        type: this.$e.SORT,
        path: [APPLICATIONS_FROM_OTHERS, 'query_options', 'sort_by'],
        value: column,
      });
    },

    countChange(count) {
      this.$send({
        type: this.$e.CHANGE_COUNT,
        path: [
          APPLICATIONS_FROM_OTHERS,
          'query_options',
          'pagination',
          'limit',
        ],
        value: count,
      });
    },

    pageChange(page) {
      this.$send({
        type: this.$e.CHANGE_PAGE,
        path: [APPLICATIONS_FROM_OTHERS, 'query_options', 'pagination', 'page'],
        value: page,
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
.tui-mod_approval-otherApplicationsTable {
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

  &__paging {
    margin-top: var(--gap-5);
  }
}
</style>

<lang-strings>
{
  "mod_approval": [
    "actions",
    "applicant",
    "application_title",
    "application_type",
    "application_id",
    "filter_applications",
    "workflow_name",
    "no_applications_from_others",
    "no_applications_match",
    "overall_progress",
    "search_by_applicant",
    "sort_by",
    "submitted_on",
    "total_of",
    "more_actions",
    "your_progress"
  ]
}
</lang-strings>
