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
  <div v-if="$selectors.getOverridesVariables($context)">
    <SubPageHeading :title="$str('approvals_overrides', 'mod_approval')">
      <template v-slot:buttons>
        <Button
          :text="$str('button_add_override', 'mod_approval')"
          @click="$send({ type: $e.ADD_OVERRIDE })"
        />
        <Button
          :text="$str('back_to_approvals', 'mod_approval')"
          @click="$send({ type: $e.BACK })"
        />
      </template>
    </SubPageHeading>
    <FilterBar
      :title="$str('filter_overrides', 'mod_approval')"
      class="tui-mod_approval-approvalsOverrides__filterBar"
    >
      <template v-slot:filters-right>
        <SearchFilter
          :value="search"
          :label="$str('search_by_organisation', 'mod_approval')"
          :placeholder="$str('search_by_organisation', 'mod_approval')"
          @input="handleSearch"
        />
      </template>
    </FilterBar>
    <div class="tui-mod_approval-otherApplicationsTable__sort">
      <h5>
        {{
          $str('total_of_n', 'mod_approval', {
            count: tableRows.length,
            total,
          })
        }}
      </h5>
      <SelectFilter
        :label="$str('sort_by', 'mod_approval')"
        :options="overridesSortByOptions"
        :value="$selectors.getOverridesSortBy($context)"
        :show-label="true"
        :disabled="$matches('navigation.approvals.overrides.loading')"
        @input="handleSort"
      />
    </div>
    <p v-if="zeroOverrides">
      {{ $str('no_approvers', 'mod_approval') }}
    </p>

    <Table
      :data="tableRows"
      :no-items-text="$str('no_approvals_match', 'mod_approval')"
      :loading-preview="loadingPreview"
      :loading-preview-rows="loadingPreviewRows"
      :loading-overlay-active="true"
      :header-has-loaded="tableRows.length > 0 || search !== ''"
    >
      <template v-slot:header-row>
        <HeaderCell size="4" valign="center">
          {{ $str('model_assignment_type_organisation', 'mod_approval') }}
        </HeaderCell>
        <HeaderCell
          v-for="(approvalLevel, index) in $selectors.getApprovalLevels(
            $context
          )"
          :key="index"
          size="4"
          valign="center"
        >
          {{ levelName(approvalLevel) }}
        </HeaderCell>
        <HeaderCell size="1" valign="center" />
      </template>
      <template v-slot:row="{ row }">
        <Cell size="4" valign="center">
          {{ get(row, ['assignment', 'name']) }}
        </Cell>
        <Cell
          v-for="assignmentApprovalLevel in row.assignment_approval_levels"
          :key="assignmentApprovalLevel.approval_level.id"
          size="4"
          valign="center"
        >
          <OverrideCell
            :assignment-approval-level="assignmentApprovalLevel"
            :organisation-name="get(row, ['assignment', 'name'])"
            :approval-level-name="
              levelName(assignmentApprovalLevel.approval_level)
            "
          />
        </Cell>
        <Cell size="1" align="center">
          <Dropdown
            position="bottom-left"
            class="tui-mod_approval-myApplicationsTable__dropdown"
          >
            <template v-slot:trigger="{ toggle }">
              <MoreButton
                :no-padding="true"
                :aria-label="$str('more_actions', 'mod_approval')"
                @click="toggle"
              />
            </template>
            <DropdownItem
              @click="
                $send({ type: $e.EDIT_OVERRIDES, overrideAssignment: row })
              "
            >
              {{ $str('edit', 'mod_approval') }}
            </DropdownItem>
            <DropdownItem
              @click="
                $send({ type: $e.ARCHIVE_OVERRIDES, overrideAssignment: row })
              "
            >
              {{ $str('archive', 'mod_approval') }}
            </DropdownItem>
          </Dropdown>
        </Cell>
      </template>
    </Table>
    <Paging
      v-if="total > 0"
      class="tui-mod_approval-approvalsOverrides__paging"
      :page="$selectors.getOverridesPage($context)"
      :items-per-page="limit"
      :total-items="total"
      :disabled="$matches('navigation.approvals.overrides.loading')"
      @page-change="handlePageChange"
      @count-change="handleCountChange"
    />
    <ModalPresenter
      :open="$matches('navigation.approvals.overrides.viewApprovers')"
      @request-close="$send($e.CANCEL)"
    >
      <ApproversModal
        :title="$context.approvalModalTitle"
        :approvers="$context.approvers"
        :approval-level-name="$context.approvalModalLevelName"
      >
        <template v-slot:subtitle>
          <div
            class="tui-mod_approval-approvalsOverrides__approversModalSubtitle"
          >
            <strong>{{
              $str('approval_level_name', 'mod_approval') + ':'
            }}</strong>
            {{ $context.approvalModalLevelName }}
            <div
              v-if="$context.inheritedFrom"
              class="tui-mod_approval-approvalsOverrides__inheritedFrom"
            >
              <strong>{{ $str('inherited_from', 'mod_approval') }}</strong>
              {{ $context.inheritedFrom }}
            </div>
          </div>
        </template>
      </ApproversModal>
    </ModalPresenter>
    <ModalPresenter
      :open="$matches('navigation.approvals.overrides.addOrEditOverride')"
      @request-close="$send($e.CANCEL)"
    >
      <AddOrEditOverridesModal :approver-types="$context.approverTypes" />
    </ModalPresenter>
    <ConfirmationModal
      :open="$matches('navigation.approvals.overrides.confirmArchiveOverrides')"
      :title="$str('archive_overrides_warning_title', 'mod_approval')"
      @confirm="$send({ type: $e.ARCHIVE_OVERRIDES })"
      @cancel="$send({ type: $e.CANCEL })"
    >
      <p>
        {{ $str('archive_overrides_warning_message', 'mod_approval') }}
      </p>
    </ConfirmationModal>
  </div>
</template>

<script>
import {
  MOD_APPROVAL__WORKFLOW_EDIT,
  OverridesSortBy,
} from 'mod_approval/constants';

import { get } from 'tui/util';

import Button from 'tui/components/buttons/Button';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectFilter from 'tui/components/filters/SelectFilter';
import Table from 'tui/components/datatable/Table';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import FilterBar from 'tui/components/filters/FilterBar';
import Paging from 'tui/components/paging/Paging';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import MoreButton from 'tui/components/buttons/MoreIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import OverrideCell from 'mod_approval/components/workflow/OverrideCell';
import SubPageHeading from 'mod_approval/components/page/SubPageHeading';
import ApproversModal from 'mod_approval/components/application/ApproversModal';
import AddOrEditOverridesModal from 'mod_approval/components/workflow/add_or_edit_override/AddOrEditOverridesModal';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';

export default {
  name: 'ApprovalsOverrides',

  components: {
    SubPageHeading,
    FilterBar,
    SearchFilter,
    SelectFilter,
    Table,
    HeaderCell,
    Cell,
    MoreButton,
    Dropdown,
    DropdownItem,
    Paging,
    Button,
    OverrideCell,
    ModalPresenter,
    ApproversModal,
    AddOrEditOverridesModal,
    ConfirmationModal,
  },

  data() {
    return {
      get,
      overridesSortByOptions: [
        {
          id: OverridesSortBy.NAME_DESC,
          label: this.$str('sort_by_org_name_desc', 'mod_approval'),
        },
        {
          id: OverridesSortBy.NAME_ASC,
          label: this.$str('sort_by_org_name_asc', 'mod_approval'),
        },
      ],
    };
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  computed: {
    total() {
      return this.$selectors.getOverridesTotal(this.$context);
    },

    tableRows() {
      return this.$selectors.getOverrides(this.$context);
    },

    limit() {
      return this.$selectors.getOverridesLimit(this.$context);
    },

    loadingPreview() {
      return [
        'navigation.approvals.overrides.loading',
        'navigation.approvals.overrides.savingOverrides',
        'navigation.approvals.overrides.archivingOverrides',
        'navigation.approvals.overrides.refetching',
      ].some(this.$matches);
    },

    loadingPreviewRows() {
      const count = this.tableRows.length;
      return count !== 0 && count < this.limit ? count : this.limit;
    },

    search() {
      return this.$selectors.getOverridesNameSearch(this.$context);
    },

    zeroOverrides() {
      return (
        this.total === 0 &&
        this.$matches('navigation.approvals.overrides.ready') &&
        this.search === ''
      );
    },
  },

  methods: {
    levelName(level) {
      return (
        level.name || this.$str('level_x', 'mod_approval', level.ordinal_number)
      );
    },

    getAssignmentName(row) {
      const name = get(row, ['assignment', 'name']);
      return this.$str('edit_x', 'mod_approval', name);
    },

    handleSearch(search) {
      this.$send({
        type: this.$e.FILTER,
        variables: {
          input: {
            workflow_stage_id: this.$selectors.getActiveStageId(this.$context),
            pagination: {
              page: 1,
              limit: 20,
            },
            filters: { name: search },
            sort_by: this.$selectors.getOverridesSortBy(this.$context),
          },
        },
      });
    },

    handleSort(sortById) {
      this.$send({
        type: this.$e.SORT,
        variables: {
          input: {
            workflow_stage_id: this.$selectors.getActiveStageId(this.$context),
            pagination: {
              page: 1,
              limit: 20,
            },
            filters: this.$selectors.getOverridesFilters(this.$context),
            sort_by: sortById,
          },
        },
      });
    },

    handlePageChange(page) {
      this.$send({
        type: this.$e.PAGE,
        variables: {
          input: {
            workflow_stage_id: this.$selectors.getActiveStageId(this.$context),
            pagination: {
              page,
              limit: this.limit,
            },
            filters: this.$selectors.getOverridesFilters(this.$context),
            sort_by: this.$selectors.getOverridesSortBy(this.$context),
          },
        },
      });
    },

    handleCountChange(count) {
      this.$send({
        type: this.$e.PAGE,
        variables: {
          input: {
            workflow_stage_id: this.$selectors.getActiveStageId(this.$context),
            pagination: {
              page: 1,
              limit: count,
            },
            filters: this.$selectors.getOverridesFilters(this.$context),
            sort_by: this.$selectors.getOverridesSortBy(this.$context),
          },
        },
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "approval_level_name",
    "approvals_overrides",
    "archive",
    "archive_overrides_warning_title",
    "archive_overrides_warning_message",
    "back_to_approvals",
    "button_add_override",
    "edit",
    "filter_overrides",
    "inherited_from",
    "level_x",
    "model_assignment_type_organisation",
    "more_actions",
    "no_approvers",
    "no_approvals_match",
    "search_by_organisation",
    "sort_by",
    "sort_by_org_name_desc",
    "sort_by_org_name_asc",
    "total_of_n"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-approvalsOverrides {
  &__filterBar {
    margin-top: var(--gap-7);
    padding-right: 0;
    padding-left: 0;
  }

  &__approversModalSubtitle {
    margin-top: var(--gap-8);
    margin-bottom: var(--gap-1);
    padding: var(--gap-2);
    background: var(--color-neutral-3);
  }

  &__inheritedFrom {
    margin-top: var(--gap-2);
  }

  &__paging {
    margin-top: var(--gap-5);
  }
}
</style>
