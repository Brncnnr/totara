<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totara.com] for more information.

  @author Simon Chester <simon.chester@totara.com>
  @module totara_useraction
-->

<template>
  <LayoutOneColumn
    :title="$str('scheduled_user_actions', 'totara_useraction')"
    :loading="loading"
  >
    <template v-slot:header-buttons>
      <Button
        :text="$str('add_action', 'totara_useraction')"
        @click="handleCreateClick"
      />
    </template>

    <template v-slot:content>
      <div class="tui-totara_useraction-scheduledActions__content">
        <Table
          :data="rows"
          expandable-rows
          expand-multiple-rows
          stealth-expanded
          :loading-preview="loading"
          :loading-preview-rows="pageSize"
          loading-overlay-active
          :no-items-text="$str('no_actions', 'totara_useraction')"
        >
          <template v-slot:header-row>
            <ExpandCell :header="true" />
            <HeaderCell size="7">
              {{ $str('action_name', 'totara_useraction') }}
            </HeaderCell>
            <HeaderCell size="7">
              {{ $str('action_type', 'totara_useraction') }}
            </HeaderCell>
            <HeaderCell size="3">{{ $str('status', 'core') }}</HeaderCell>
            <HeaderCell size="2" />
          </template>

          <template v-slot:row="{ row, expand, expandState, isStacked }">
            <ExpandCell
              :aria-label="row.name"
              :expand-state="expandState"
              @click="expand()"
            />
            <Cell
              size="7"
              :column-header="$str('action_name', 'totara_useraction')"
            >
              {{ row.name }}
            </Cell>
            <Cell
              size="7"
              :column-header="$str('action_type', 'totara_useraction')"
            >
              {{ row.action && row.action.name }}
            </Cell>
            <Cell size="3" :column-header="$str('status', 'core')">
              {{
                row.status
                  ? $str('enabled', 'totara_useraction')
                  : $str('disabled', 'totara_useraction')
              }}
            </Cell>
            <Cell
              size="2"
              :column-header="$str('actions', 'totara_useraction')"
              valign="center"
            >
              <div
                class="tui-totara_useraction-scheduledActions__row-actions"
                :class="{
                  'tui-totara_useraction-scheduledActions__row-actions--stacked': isStacked,
                }"
              >
                <Dropdown position="bottom-right">
                  <template v-slot:trigger="{ toggle, isOpen }">
                    <MoreButton
                      :aria-expanded="isOpen.toString()"
                      @click="toggle"
                    />
                  </template>
                  <DropdownItem
                    :href="
                      $url('/totara/useraction/edit_scheduled_action.php', {
                        id: row.id,
                      })
                    "
                  >
                    {{ $str('edit_action', 'totara_useraction') }}
                  </DropdownItem>
                  <DropdownButton @click="handleDeleteClick(row)">
                    {{ $str('delete', 'core') }}
                  </DropdownButton>
                  <DropdownItem
                    :href="
                      $url('/totara/useraction/history.php', {
                        rule_id: row.id,
                      })
                    "
                  >
                    {{ $str('view_history', 'totara_useraction') }}
                  </DropdownItem>
                </Dropdown>
              </div>
            </Cell>
          </template>

          <template v-slot:expand-content="{ row }">
            <div
              class="tui-totara_useraction-scheduledActions__row-expandedWrapper"
            >
              <RuleExtraDetail :rule="row" />
            </div>
          </template>
        </Table>

        <Paging
          v-if="scheduledActions && scheduledActions.total > 0"
          :page="page"
          :items-per-page="pageSize"
          :total-items="scheduledActions.total"
          @page-change="handlePageChange"
          @count-change="handlePageSizeChange"
        />
      </div>
    </template>

    <template v-slot:modals>
      <ConfirmationModal
        :open="deleteConfirmationOpen"
        :title="$str('delete_scheduled_action', 'totara_useraction')"
        :confirm-button-text="$str('delete', 'core')"
        :loading="deleting"
        @confirm="handleDeleteConfirm"
        @cancel="handleDeleteCancel"
      >
        {{
          $str('delete_scheduled_action_confirm_message', 'totara_useraction')
        }}
      </ConfirmationModal>
    </template>
  </LayoutOneColumn>
</template>

<script>
import { notify } from 'tui/notifications';
import Button from 'tui/components/buttons/Button';
import MoreButton from 'tui/components/buttons/MoreIcon';
import Cell from 'tui/components/datatable/Cell';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import LayoutOneColumn from 'tui/components/layouts/LayoutOneColumn';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Paging from 'tui/components/paging/Paging';
import RuleExtraDetail from 'totara_useraction/components/scheduled_rules/RuleExtraDetail';
import actionsQuery from 'totara_useraction/graphql/scheduled_rules';
import deleteMutation from 'totara_useraction/graphql/delete_scheduled_rule';

export default {
  components: {
    Button,
    MoreButton,
    Cell,
    ExpandCell,
    HeaderCell,
    Table,
    Dropdown,
    DropdownItem,
    DropdownButton,
    LayoutOneColumn,
    ConfirmationModal,
    Paging,
    RuleExtraDetail,
  },

  data() {
    return {
      page: 1,
      pageSize: 20,
      deleteConfirmationOpen: false,
      deleting: false,
      editingRow: null,
    };
  },

  computed: {
    initiallyLoading() {
      return this.$apollo.loading && !this.scheduledActions;
    },

    loading() {
      return this.$apollo.loading;
    },

    rows() {
      return (this.scheduledActions && this.scheduledActions.items) || [];
    },
  },

  apollo: {
    scheduledActions: {
      query: actionsQuery,
      fetchPolicy: 'network-only',
      variables() {
        return {
          input: {
            tenant_id: this.tenantId,
            pagination: {
              page: this.page,
              limit: this.pageSize,
            },
          },
        };
      },
      update: result => result.rules,
    },
  },

  mounted() {
    const queryString = window.location.search;
    this.urlParams = new URLSearchParams(queryString);

    if (this.urlParams.get('edit_success')) {
      notify({ message: this.$str('action_saved', 'totara_useraction') });

      // Remove any params as we've just handled them
      window.history.replaceState(
        null,
        null,
        this.$url('/totara/useraction/scheduled_actions.php')
      );
    }
  },

  methods: {
    handleCreateClick() {
      window.location = this.$url(
        '/totara/useraction/add_scheduled_action.php'
      );
    },

    handleDeleteClick(row) {
      this.editingRow = row;
      this.deleteConfirmationOpen = true;
    },

    async handleDeleteConfirm() {
      this.deleting = true;
      try {
        await this.$apollo.mutate({
          mutation: deleteMutation,
          variables: {
            id: this.editingRow.id,
          },
          refetchQueries: ['totara_useraction_scheduled_rules'],
          awaitRefetchQueries: true,
        });

        notify({ message: this.$str('action_deleted', 'totara_useraction') });
      } finally {
        this.deleting = false;
        this.deleteConfirmationOpen = false;
      }
    },

    handleDeleteCancel() {
      this.deleteConfirmationOpen = false;
    },

    handlePageChange(page) {
      this.page = page;
    },

    handlePageSizeChange(size) {
      this.page = 1;
      this.pageSize = size;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "delete",
    "status"
  ],
  "totara_useraction": [
    "action_deleted",
    "action_name",
    "action_saved",
    "action_type",
    "actions",
    "add_action",
    "delete_scheduled_action",
    "delete_scheduled_action_confirm_message",
    "disabled",
    "edit_action",
    "enabled",
    "filter_applies_to_all_users",
    "no_actions",
    "scheduled_user_actions",
    "view_history"
  ]
}
</lang-strings>

<style lang="scss">
.tui-totara_useraction-scheduledActions {
  @include tui-font-body();

  &__content {
    @include tui-stack-vertical(var(--gap-4));
  }

  &__row {
    &-actions {
      display: flex;
      justify-content: flex-end;
      &--isStacked {
        justify-content: flex-start;
      }
    }

    &-expandedWrapper {
      padding: var(--gap-4);
      border: var(--border-width-thin) solid var(--color-neutral-5);
    }
  }
}
</style>
