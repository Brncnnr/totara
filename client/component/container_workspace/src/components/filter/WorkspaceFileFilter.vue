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
  <div class="tui-workspaceFileFilter">
    <template v-if="!$apollo.loading">
      <FilterBar
        :title="$str('files', 'container_workspace')"
        class="tui-workspaceFileFilter__filter"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="selection.extension"
            :label="$str('file_format', 'container_workspace')"
            :show-label="true"
            :options="filter.extensions"
            :stacked="stacked"
            class="tui-workspaceFileFilter__label"
            @input="$emit('update-filter', selection)"
          />
        </template>
      </FilterBar>

      <SelectFilter
        v-if="showSort"
        v-model="selection.sort"
        :label="$str('sortby', 'core')"
        :show-label="true"
        :options="filter.sorts"
        class="tui-workspaceFileFilter__sortFilter"
        @input="$emit('update-filter', selection)"
      />
    </template>
  </div>
</template>

<script>
import FilterBar from 'tui/components/filters/FilterBar';
import SelectFilter from 'tui/components/filters/SelectFilter';

// GraphQL queries
import getFileFilterOptions from 'container_workspace/graphql/file_filter_options';

export default {
  components: {
    FilterBar,
    SelectFilter,
  },

  props: {
    selectedSort: {
      type: String,
      required: true,
    },

    selectedExtension: {
      type: String,
      default: '',
    },

    workspaceId: {
      type: [Number, String],
      required: true,
    },

    showSort: {
      type: Boolean,
      required: true,
    },
  },

  apollo: {
    filter: {
      query: getFileFilterOptions,
      variables() {
        return {
          workspace_id: this.workspaceId,
        };
      },
      update({ sorts, extensions }) {
        extensions = Array.prototype.map.call(
          extensions,
          ({ value, label }) => {
            return {
              id: value,
              label: label,
            };
          }
        );

        // Adding default extensions value, which should be empty.
        extensions.unshift({
          id: '',
          label: this.$str('all', 'core'),
        });

        return {
          extensions,
          sorts: Array.prototype.map.call(sorts, ({ label, value }) => {
            return {
              id: value,
              label: label,
            };
          }),
        };
      },
    },
  },

  data() {
    return {
      selection: {
        sort: this.selectedSort,
        extension: this.selectedExtension,
      },
    };
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "file_format",
      "files",
      "no_file_found"
    ],

    "core": [
      "sortby",
      "all"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceFileFilter {
  display: flex;
  flex-direction: column;
  &__sortFilter {
    align-self: flex-end;
    margin-top: var(--gap-8);
    padding-bottom: var(--gap-2);
    .tui-formLabel {
      @include tui-font-heading-label-small();
    }

    @media (max-width: 765px) {
      padding: var(--gap-4);
    }
  }

  &__label {
    .tui-formLabel {
      @include tui-font-heading-label-small();
    }
  }
}
</style>
