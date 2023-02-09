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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module container_workspace
-->
<template>
  <div v-if="page.files.length > 0">
    <Table
      class="tui-workspaceFileTable"
      :data="page.files"
      :expandable-rows="false"
    >
      <template v-slot:header-row>
        <HeaderCell size="7">
          {{ $str('file_name', 'container_workspace') }}
        </HeaderCell>

        <HeaderCell size="2">
          {{ $str('format', 'container_workspace') }}
        </HeaderCell>

        <HeaderCell size="2">
          {{ $str('size', 'core') }}
        </HeaderCell>

        <HeaderCell size="2">
          {{ $str('file_date', 'container_workspace') }}
        </HeaderCell>

        <HeaderCell size="5">
          {{ $str('uploaded_by', 'container_workspace') }}
        </HeaderCell>

        <HeaderCell size="4">
          {{ $str('actions', 'container_workspace') }}
        </HeaderCell>
      </template>

      <template v-slot:row="{ row }">
        <Cell
          size="7"
          :column-header="$str('file_name', 'container_workspace')"
        >
          <a href="javascript:;" @click.prevent="$emit('open', row)">
            {{ row.file_name }}
          </a>
        </Cell>

        <Cell size="2" :column-header="$str('format', 'container_workspace')">
          {{ row.extension }}
        </Cell>

        <Cell size="2" :column-header="$str('size', 'core')">
          {{ row.file_size }}
        </Cell>

        <Cell
          size="2"
          :column-header="$str('file_date', 'container_workspace')"
        >
          {{ row.date }}
        </Cell>

        <Cell
          size="5"
          :column-header="$str('uploaded_by', 'container_workspace')"
        >
          {{ row.author.fullname }}
        </Cell>

        <Cell size="4" :column-header="$str('actions', 'container_workspace')">
          <div class="tui-workspaceFileTable__action">
            <a :href="row.context_url">
              {{ $str('view_discussion', 'container_workspace') }}
            </a>

            <a :href="row.download_url">
              {{ $str('download', 'core') }}
            </a>
          </div>
        </Cell>
      </template>
    </Table>
    <PageLoader :fullpage="false" :loading="$apollo.loading" />
    <div
      v-if="page.files.length < page.cursor.total && !$apollo.loading"
      class="tui-workspaceFileTable__loadMoreContainer"
    >
      <div class="tui-workspaceFileTable__viewedFiles">
        {{ $str('vieweditems', 'container_workspace', page.files.length) }}
        {{ $str('total_files', 'container_workspace', page.cursor.total) }}
      </div>
      <Button
        class="tui-workspaceFileTable__loadMore"
        :text="$str('loadmore', 'container_workspace')"
        @click="loadMoreItems"
      />
    </div>
  </div>
</template>

<script>
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';
import Button from 'tui/components/buttons/Button';
import PageLoader from 'tui/components/loading/Loader';

// GraphQL queries
import getFiles from 'container_workspace/graphql/get_files';

export default {
  components: {
    HeaderCell,
    Cell,
    Button,
    PageLoader,
    Table,
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },

    selectedSort: {
      type: String,
      required: true,
    },

    selectedExtension: {
      type: String,
      default: '',
    },
  },

  apollo: {
    page: {
      query: getFiles,
      fetchPolicy: 'network-only',
      variables() {
        return {
          workspace_id: this.workspaceId,
          extension: this.selectedExtension,
          sort: this.selectedSort,
        };
      },

      update({ cursor, files }) {
        return {
          cursor,
          files,
        };
      },
    },
  },

  data() {
    return {
      page: {
        files: [],
        cursor: {
          total: 0,
          next: null,
        },
      },
    };
  },

  methods: {
    async loadMoreItems() {
      if (!this.page.cursor.next) {
        return;
      }
      this.$apollo.queries.page.fetchMore({
        variables: {
          workspace_id: this.workspaceId,
          extension: this.selectedExtension,
          sort: this.selectedSort,
          cursor: this.page.cursor.next,
        },
        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult;
          const newData = fetchMoreResult;
          const newList = oldData.files.concat(newData.files);

          return {
            cursor: newData.cursor,
            files: newList,
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
      "actions",
      "size",
      "uploaded_by",
      "file_name",
      "file_date",
      "format",
      "view_discussion",
      "loadmore",
      "vieweditems",
      "total_files"
    ],

    "core": [
      "download",
      "size"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceFileTable {
  @media (max-width: 764px) {
    padding: var(--gap-4);
  }

  &__action {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
  }

  &__loadMoreContainer {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: var(--gap-8);
  }

  &__viewedFiles {
    display: flex;
    align-self: center;
    margin-bottom: var(--gap-1);
  }

  &__loadMore {
    display: flex;
    align-self: center;
  }
}
</style>
