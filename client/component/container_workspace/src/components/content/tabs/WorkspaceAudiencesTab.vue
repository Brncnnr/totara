<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module container_workspace
-->

<template>
  <div class="tui-workspaceAudiencesTab">
    <div
      v-if="noAudiencesAssigned"
      class="tui-workspaceAudiencesTab__placeholder"
    >
      <Loader :loading="$apollo.loading">
        <template v-if="!$apollo.loading">
          <p>
            <b>{{ $str('no_audiences_added', 'container_workspace') }}</b>
          </p>
          <div>
            <Button
              :styleclass="{ primary: 'true' }"
              :text="$str('add_audiences', 'container_workspace')"
              @click="$emit('add-audience')"
            />
          </div>
        </template>
      </Loader>
    </div>
    <div v-else>
      <div class="tui-workspaceAudiencesTab__filter">
        <SearchFilter
          v-model="searchAudience"
          name="user-search-input"
          drop-label
          :placeholder="$str('filter_audiences', 'container_workspace')"
          :label="$str('filter_audiences', 'container_workspace')"
        />
      </div>

      <Loader :loading="$apollo.loading">
        <Table
          class="tui-workspaceAudiencesTab__table"
          :loading-preview="$apollo.loading"
          :loading-preview-rows="pagination.limit"
          :data="paginatedAudiences.items"
        >
          <template v-slot:header-row>
            <HeaderCell size="6">
              {{ $str('audience_name', 'container_workspace') }}
            </HeaderCell>
            <HeaderCell size="2">
              {{ $str('audience_id', 'container_workspace') }}
            </HeaderCell>
            <HeaderCell size="2">
              {{ $str('audience_members', 'container_workspace') }}
            </HeaderCell>
            <HeaderCell size="2" align="end">
              <span class="tui-sr-only">{{ $str('actions', 'core') }}</span>
            </HeaderCell>
          </template>
          <template v-slot:row="{ row }">
            <Cell
              :column-header="$str('audience_name', 'container_workspace')"
              size="6"
            >
              <template v-slot:default>
                {{ row.name }}
              </template>
            </Cell>

            <Cell
              :column-header="$str('audience_id', 'container_workspace')"
              size="2"
            >
              {{ row.idnumber }}
            </Cell>

            <Cell
              :column-header="$str('audience_members', 'container_workspace')"
              size="2"
            >
              {{ row.members_count }}
            </Cell>

            <!-- Action buttons -->
            <Cell align="end" size="2" valign="center">
              <template v-slot:default="{ isStacked }">
                <div class="tui-workspaceAudiencesTab__actions">
                  <Button
                    v-if="isStacked"
                    :aria-label="
                      $str('remove_x', 'container_workspace', row.name)
                    "
                    class="tui-workspaceAudiencesTab__actions-stackedButton"
                    :styleclass="{ small: true, transparent: true }"
                    :text="$str('remove', 'core')"
                    @click="showRemove(row)"
                  />

                  <ButtonIcon
                    v-else
                    :aria-label="
                      $str('remove_x', 'container_workspace', row.name)
                    "
                    :styleclass="{
                      small: true,
                      transparent: true,
                    }"
                    @click="showRemove(row)"
                  >
                    <RemoveIcon state="alert" />
                  </ButtonIcon>
                </div>
              </template>
            </Cell>
          </template>
        </Table>

        <Paging
          v-if="paginatedAudiences.total > 0"
          :page="pagination.page"
          :items-per-page="pagination.limit"
          :total-items="paginatedAudiences.total"
          @page-change="updatePage"
          @count-change="updatePageSize"
        />
      </Loader>
    </div>

    <ConfirmationModal
      :open="showRemoveModal"
      :title="
        $str(
          'remove_x',
          'container_workspace',
          activeAudience && activeAudience.name
        )
      "
      :loading="submitting"
      :confirm-button-text="$str('remove', 'core')"
      @confirm="confirmRemove"
      @cancel="cancelRemove"
    >
      <p>
        {{
          $str('remove_audience_warning_msg', 'container_workspace', {
            audience: activeAudience && activeAudience.name,
            workspace: workspace.name,
          })
        }}
      </p>
      <ul>
        <li>
          {{ $str('remove_audience_warning_bullet_1', 'container_workspace') }}
        </li>
        <li>
          {{ $str('remove_audience_warning_bullet_2', 'container_workspace') }}
        </li>
      </ul>
    </ConfirmationModal>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';
import Loader from 'tui/components/loading/Loader';
import Paging from 'tui/components/paging/Paging';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Table from 'tui/components/datatable/Table';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import RemoveIcon from 'tui/components/icons/Remove';

// GraphQL queries
import audiencesQuery from 'container_workspace/graphql/audiences';
import removeAudienceMutation from 'container_workspace/graphql/remove_audience';
import { notify } from 'tui/notifications';

const defaultPage = 1;
const defaultLimit = 10;

export default {
  components: {
    Button,
    ButtonIcon,
    Cell,
    HeaderCell,
    Loader,
    Paging,
    SearchFilter,
    Table,
    ConfirmationModal,
    RemoveIcon,
  },

  props: {
    workspace: {
      type: Object,
      validator: prop =>
        'id' in prop && 'total_audiences' in prop && 'name' in prop,
      required: true,
    },
  },

  data() {
    return {
      searchAudience: '',
      pagination: {
        page: defaultPage,
        limit: defaultLimit,
      },
      paginatedAudiences: {
        items: [],
        total: 0,
      },
      activeAudience: null,
      submitting: false,
      showRemoveModal: false,
    };
  },

  computed: {
    noAudiencesAssigned() {
      return this.workspace.total_audiences === 0;
    },
  },

  watch: {
    searchAudience(newSearch, oldSearch) {
      if (newSearch !== oldSearch) {
        this.pagination.page = defaultPage;
        this.pagination.limit = defaultLimit;
      }
    },
  },

  methods: {
    updatePage(page) {
      this.pagination.page = page;
    },

    updatePageSize(limit) {
      this.pagination.limit = limit;
      this.pagination.page = defaultPage;
    },

    showRemove(audience) {
      this.activeAudience = audience;
      this.submitting = false;
      this.showRemoveModal = true;
    },

    async confirmRemove() {
      this.submitting = true;
      await this.$apollo.mutate({
        mutation: removeAudienceMutation,
        variables: {
          input: {
            workspace_id: this.workspace.id,
            audience_id: this.activeAudience.id,
          },
        },
        refetchQueries: [
          'container_workspace_get_workspace',
          'container_workspace_audiences',
        ],
        awaitRefetchQueries: true,
      });
      this.showRemoveModal = false;
      notify({ message: this.$str('audience_removed', 'container_workspace') });
    },

    cancelRemove() {
      this.showRemoveModal = false;
    },
  },

  apollo: {
    paginatedAudiences: {
      query: audiencesQuery,
      fetchPolicy: 'network-only',
      variables() {
        return {
          input: {
            workspace_id: this.workspace.id,
            name: this.searchAudience,
            pagination: {
              page: this.pagination.page,
              limit: this.pagination.limit,
            },
          },
        };
      },
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "actions",
    "remove"
  ],
  "container_workspace": [
    "add_audiences",
    "audience_name",
    "audience_id",
    "audience_members",
    "audience_removed",
    "filter_audiences",
    "no_audiences_added",
    "remove_audience_warning_msg",
    "remove_audience_warning_bullet_1",
    "remove_audience_warning_bullet_2",
    "remove_x"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspaceAudiencesTab {
  margin-bottom: var(--gap-8);

  &__filter {
    display: flex;
    margin-bottom: var(--gap-4);
    padding-bottom: var(--gap-4);
    padding-left: var(--gap-4);
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);

    @media (min-width: $tui-screen-sm) {
      justify-content: flex-end;
      padding-right: var(--gap-4);
    }
  }

  &__table {
    margin-bottom: var(--gap-4);
  }

  &__actions {
    display: flex;

    &-stackedButton {
      margin-top: var(--gap-3);
    }
  }

  &__placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-top: var(--gap-8);
    text-align: center;
  }
}
</style>
