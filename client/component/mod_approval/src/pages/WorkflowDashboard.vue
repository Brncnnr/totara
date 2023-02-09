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
  <div class="tui-mod_approval-workflowDashboard">
    <div class="tui-mod_approval-workflowDashboard__titleRow">
      <h1 class="tui-mod_approval-workflowDashboard__title">
        {{ $str('workflow_dashboard_title', 'mod_approval') }}
      </h1>

      <div class="tui-mod_approval-workflowDashboard__actions">
        <Button
          v-if="canCreateWorkflow"
          :text="$str('button_new_workflow', 'mod_approval')"
          :styleclass="{ primary: true }"
          @click="$send($e.CREATE_WORKFLOW)"
        />
        <Dropdown
          position="bottom-right"
          class="tui-mod_approval-workflowDashboard__actions--options"
        >
          <template v-slot:trigger="{ toggle, isOpen }">
            <ButtonIcon
              :aria-label="$str('more_actions', 'mod_approval')"
              :aria-expanded="isOpen ? 'true' : 'false'"
              @click="toggle"
            >
              <MoreIcon :size="400" />
            </ButtonIcon>
          </template>
          <DropdownItem
            :href="this.$url('/mod/approval/workflow/types/index.php')"
          >
            {{ $str('manage_approval_workflows_types', 'mod_approval') }}
          </DropdownItem>
          <DropdownItem :href="this.$url('/mod/approval/form/index.php')">
            {{ $str('manage_approval_forms', 'mod_approval') }}
          </DropdownItem>
        </Dropdown>
      </div>
    </div>
    <div>
      <FilterBar
        title="text"
        class="tui-mod_approval-workflowDashboard__filterBar"
      >
        <template v-slot:filters-left>
          <SelectFilter
            :value="$selectors.getStatusFilter($context)"
            :label="$str('status', 'mod_approval')"
            :options="statusOptions"
            :show-label="true"
            :stacked="false"
            @input="optionId => filter({ key: 'status', value: optionId })"
          />
          <SelectFilter
            :value="$selectors.getWorkflowTypeIdFilter($context)"
            :label="$str('type', 'mod_approval')"
            :options="[
              {
                id: null,
                label: $str('filter_all', 'mod_approval'),
              },
              ...filterOptions.workflow_types,
            ]"
            :show-label="true"
            @input="
              optionId => filter({ key: 'workflow_type_id', value: optionId })
            "
          />
          <SelectFilter
            :value="$selectors.getAssignmentTypeFilter($context)"
            :label="$str('assignment_type', 'mod_approval')"
            :options="assignmentTypeOptions"
            :show-label="true"
            @input="
              optionId => filter({ key: 'assignment_type', value: optionId })
            "
          />
        </template>
        <template v-slot:filters-right>
          <SearchFilter
            class="tui-mod_approval-workflowDashboard__search"
            :value="$selectors.getWorkflowNameFilter($context)"
            :label="$str('search_by_workflow_name', 'mod_approval')"
            :placeholder="$str('search_by_workflow_name', 'mod_approval')"
            @input="search => filter({ key: 'name', value: search })"
          />
        </template>
      </FilterBar>
      <div>
        <div class="tui-mod_approval-workflowDashboard__sortRow">
          <h5>
            {{
              $str('total_of_workflows', 'mod_approval', {
                count: $selectors.getWorkflows($context).length,
                total,
              })
            }}
          </h5>
          <SelectFilter
            :value="$selectors.getSortBy($context)"
            :label="$str('sort_by', 'mod_approval')"
            :options="sortByOptions"
            :show-label="true"
            @input="column => sort(column)"
          />
        </div>
        <Table
          :data="workflows"
          :header-has-loaded="true"
          :loading-preview="['loading', 'deleting'].some(this.$matches)"
          :loading-preview-rows="loadingPreviewRows"
          :loading-overlay-active="true"
          :no-items-text="$str('no_workflows_match', 'mod_approval')"
        >
          <!-- Render Table Header immediately -->
          <template v-slot:header-row>
            <HeaderCell size="4" valign="center">
              {{ $str('workflow_name', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('last_modified', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('type', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('workflow_id', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('assignment_type', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('assigned_to', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="4" valign="center">
              {{ $str('status', 'mod_approval') }}
            </HeaderCell>
            <HeaderCell size="1" valign="center" />
          </template>
          <template v-slot:row="{ row }">
            <Cell
              size="4"
              :column-header="$str('workflow_name', 'mod_approval')"
            >
              <a :href="editUrl(row.id)">
                {{ row.name }}
              </a>
            </Cell>
            <Cell
              size="4"
              :column-header="$str('last_modified', 'mod_approval')"
            >
              {{ row.updated }}
            </Cell>
            <Cell size="4" :column-header="$str('type', 'mod_approval')">
              {{ get(row, ['workflow_type', 'name']) }}
            </Cell>
            <Cell size="4" :column-header="$str('workflow_id', 'mod_approval')">
              {{ row.id_number }}
            </Cell>
            <Cell
              size="4"
              :column-header="$str('assignment_type', 'mod_approval')"
            >
              {{ get(row, ['default_assignment', 'assignment_type_label']) }}
            </Cell>
            <Cell size="4" :column-header="$str('assigned_to', 'mod_approval')">
              {{ get(row, ['default_assignment', 'assigned_to', 'fullname']) }}
            </Cell>
            <Cell size="4" :column-header="$str('status', 'mod_approval')">
              {{ get(row, ['latest_version', 'status_label']) }}
            </Cell>
            <Cell
              size="1"
              :column-header="$str('more_actions', 'mod_approval')"
              align="center"
            >
              <Dropdown
                v-if="row.interactor"
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
                  v-if="row.interactor.can_clone"
                  @click="$send({ type: $e.CLONE, workflowId: row.id })"
                >
                  {{ $str('clone', 'mod_approval') }}
                </DropdownItem>
                <DropdownItem
                  v-if="row.interactor.can_archive"
                  @click="$send({ type: $e.ARCHIVE, workflowId: row.id })"
                >
                  {{ $str('archive', 'mod_approval') }}
                </DropdownItem>
                <DropdownItem
                  v-if="row.interactor.can_unarchive"
                  @click="$send({ type: $e.UNARCHIVE, workflowId: row.id })"
                >
                  {{ $str('unarchive', 'mod_approval') }}
                </DropdownItem>
                <DropdownItem
                  v-if="row.interactor.can_delete"
                  @click="$send({ type: $e.DELETE, workflowId: row.id })"
                >
                  {{ $str('delete', 'core') }}
                </DropdownItem>
                <DropdownItem
                  v-if="row.interactor.can_view_applications_report"
                  :href="reportUrl(row.id)"
                >
                  {{ $str('view_applications_report', 'mod_approval') }}
                </DropdownItem>
              </Dropdown>
            </Cell>
          </template>
        </Table>
        <Paging
          v-if="total > 0"
          class="tui-mod_approval-workflowDashboard__paging"
          :page="$selectors.getPage($context)"
          :items-per-page="limit"
          :total-items="$selectors.getTotal($context)"
          @count-change="changeCount"
          @page-change="changePage"
        />
        <Loader :loading="$matches('cloning')" :fullpage="true" />
      </div>

      <WorkflowDeleteModal
        :open="$matches('confirmDelete')"
        :name="$selectors.getToMutateWorkflowName($context)"
        @confirm="$send($e.DELETE)"
        @cancel="$send($e.CANCEL)"
      />
      <ModalPresenter
        :open="$matches('create')"
        @request-close="$send({ type: $e.CANCEL_MODAL })"
      >
        <WorkflowCreateModal />
      </ModalPresenter>
      <ModalPresenter
        :open="$matches('clone')"
        @request-close="$send({ type: $e.CANCEL_MODAL })"
      >
        <WorkflowCloneModal />
      </ModalPresenter>
      <ConfirmationModal
        :open="$matches('confirmArchive')"
        :title="$str('archive_workflow_warning_title', 'mod_approval')"
        :confirm-button-text="$str('archive', 'mod_approval')"
        :loading="$matches('confirmArchive.archiving')"
        @confirm="$send($e.ARCHIVE)"
        @cancel="$send($e.CANCEL)"
      >
        <p v-html="$str('archive_workflow_warning_message', 'mod_approval')" />
      </ConfirmationModal>
      <ConfirmationModal
        :open="$matches('confirmUnarchive')"
        :title="$str('unarchive_workflow_warning_title', 'mod_approval')"
        :confirm-button-text="$str('unarchive', 'mod_approval')"
        :loading="$matches('confirmUnarchive.unarchiving')"
        @confirm="$send($e.UNARCHIVE)"
        @cancel="$send($e.CANCEL)"
      >
        <p
          v-html="$str('unarchive_workflow_warning_message', 'mod_approval')"
        />
      </ConfirmationModal>
    </div>
    <Loader v-if="fullPageLoading" :fullpage="true" :loading="true" />
  </div>
</template>

<script>
import { get } from 'tui/util';
import { WorkflowsSortOption } from 'mod_approval/constants';
import workflowDashboardMachine from 'mod_approval/workflow/index/machine';

import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Loader from 'tui/components/loading/Loader';
import FilterBar from 'tui/components/filters/FilterBar';
import SelectFilter from 'tui/components/filters/SelectFilter';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Table from 'tui/components/datatable/Table';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Paging from 'tui/components/paging/Paging';
import MoreButton from 'tui/components/buttons/MoreIcon';
import MoreIcon from 'tui/components/icons/More';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkflowCloneModal from 'mod_approval/components/workflow/WorkflowCloneModal';
import WorkflowCreateModal from 'mod_approval/components/workflow/create/WorkflowCreateModal';
import WorkflowDeleteModal from 'mod_approval/components/workflow/WorkflowDeleteModal';

export default {
  name: 'WorkflowDashboard',

  components: {
    Button,
    ButtonIcon,
    Loader,
    Table,
    Cell,
    HeaderCell,
    Paging,
    SelectFilter,
    SearchFilter,
    FilterBar,
    MoreButton,
    MoreIcon,
    Dropdown,
    DropdownItem,
    ModalPresenter,
    ConfirmationModal,
    WorkflowCloneModal,
    WorkflowCreateModal,
    WorkflowDeleteModal,
  },

  props: {
    contextId: {
      type: Number,
      required: true,
    },
    canCreateWorkflow: {
      required: true,
      type: Boolean,
    },
    filterOptions: {
      required: true,
      type: Object,
    },
  },

  data() {
    return {
      get,
      statusOptions: this.filterOptions.status.map(statusOption => {
        return {
          id: statusOption.enum,
          label: statusOption.label,
        };
      }),
      workflowTypeOptions: this.filterOptions.workflow_types.map(
        workflowType => {
          return {
            id: workflowType.id,
            label: workflowType.name,
          };
        }
      ),
      assignmentTypeOptions: this.filterOptions.assignment_types.map(
        assignmentType => {
          return {
            id: assignmentType.enum,
            label: assignmentType.label,
          };
        }
      ),

      sortByOptions: [
        {
          id: WorkflowsSortOption.UPDATED,
          label: this.$str('last_modified', 'mod_approval'),
        },
        {
          id: WorkflowsSortOption.NAME,
          label: this.$str('workflow_name', 'mod_approval'),
        },
      ],
    };
  },

  xState: {
    machine() {
      return workflowDashboardMachine({
        categoryContextId: this.contextId,
        workflowTypeOptions: this.filterOptions.workflow_types,
      });
    },
  },

  computed: {
    fullPageLoading() {
      return ['cloning', 'creating'].some(this.$matches);
    },

    workflows() {
      return this.$selectors.getWorkflows(this.$context);
    },

    limit() {
      return this.$selectors.getLimit(this.$context);
    },

    loadingPreviewRows() {
      const count = this.workflows.length;

      return count !== 0 && count < this.limit ? count : this.limit;
    },

    total() {
      return this.$selectors.getTotal(this.$context);
    },

    workflowType() {
      return this.filterOptions.workflow_types.map(workflowType => {
        return {
          id: workflowType.id,
          label: workflowType.name,
        };
      });
    },
  },

  methods: {
    editUrl(workflow_id) {
      return this.$url('/mod/approval/workflow/edit.php', { workflow_id });
    },
    reportUrl(workflow_id) {
      return this.$url('/mod/approval/workflow/report.php', { workflow_id });
    },

    handleEditClick(workflowId) {
      window.location.href = this.editUrl(workflowId);
    },

    filter({ key, value }) {
      if (value === null || value === '') {
        value = undefined;
      }

      this.$send({
        type: this.$e.FILTER,
        filters: {
          ...this.$selectors.getFilters(this.$context),
          [key]: value,
        },
      });
    },

    sort(column) {
      this.$send({
        type: this.$e.SORT,
        sortBy: column,
      });
    },

    changeCount(count) {
      this.$send({
        type: this.$e.CHANGE_PAGINATION,
        pagination: { page: 1, limit: count },
      });
    },

    changePage(page) {
      this.$send({
        type: this.$e.CHANGE_PAGINATION,
        pagination: { page, limit: this.limit },
      });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "edit",
    "delete",
    "loading"
  ],

  "mod_approval": [
    "archive",
    "archive_workflow_warning_message",
    "archive_workflow_warning_title",
    "workflow_dashboard_title",
    "assigned_to",
    "assignment_type",
    "button_new_workflow",
    "clone",
    "filter_all",
    "workflow_dashboard_title",
    "workflow_id",
    "workflow_name",
    "manage_approval_forms",
    "manage_approval_workflows_types",
    "more_actions",
    "no_workflows_match",
    "last_modified",
    "search_by_workflow_name",
    "sort_by",
    "status",
    "type",
    "total_of_workflows",
    "unarchive",
    "unarchive_workflow_warning_message",
    "unarchive_workflow_warning_title",
    "view_applications_report"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-workflowDashboard {
  padding-top: 0;
  padding-right: var(--gap-4);
  padding-bottom: var(--gap-8);
  padding-left: var(--gap-4);

  &__titleRow {
    display: flex;
    justify-content: space-between;
    @media (max-width: $tui-screen-sm) {
      flex-direction: column;
    }
  }

  &__title {
    margin-top: 0;
    margin-bottom: 0;
  }

  &__actions {
    display: flex;
    flex-shrink: 0;
    align-self: flex-start;
    &--options {
      margin-left: var(--gap-4);
    }
    @media (max-width: $tui-screen-sm) {
      margin-top: var(--gap-6);
    }
  }

  &__filterBar {
    margin-top: var(--gap-10);
  }

  // default is too narrow for placeholder text
  &__search {
    min-width: 22rem;
  }

  &__sortRow {
    display: flex;
    justify-content: space-between;
    margin-top: var(--gap-7);

    h5 {
      font-weight: bold;
    }
  }

  &__paging {
    margin-top: var(--gap-5);
  }
}
</style>
