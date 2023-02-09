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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <section
    class="tui-contributionFilter"
    :class="{
      'tui-contributionFilter--hasSortBy': showSort,
    }"
  >
    <template v-if="!$apollo.loading">
      <FilterBar
        v-model="selection"
        :has-bottom-bar="hasBottomBar"
        :has-top-bar="hasTopBar"
        :title="$str('filterbartitle', 'totara_engage')"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-if="showAccess"
            id="filter_access"
            v-model="access"
            :label="$str('filteraccess', 'totara_engage')"
            :show-label="true"
            :options="filter.access.options"
            :stacked="stacked"
          />

          <SelectFilter
            v-if="showType"
            id="filter_type"
            v-model="type"
            :label="$str('filtertype', 'totara_engage')"
            :show-label="true"
            :options="filter.type.options"
            :stacked="stacked"
          />

          <SelectFilter
            v-if="showTopic"
            id="filter_topic"
            v-model="topic"
            :label="$str('filtertopic', 'totara_engage')"
            :show-label="true"
            :options="filter.topic.options"
            :stacked="stacked"
          />

          <SelectFilter
            v-if="showSection"
            id="filter_section"
            v-model="section"
            :label="$str('filtersection', 'totara_engage')"
            :show-label="true"
            :options="filter.section.options"
            :stacked="stacked"
          />
        </template>

        <template v-slot:filters-right>
          <SearchFilter
            v-if="showSearch"
            v-model="search"
            :drop-label="true"
            :label="$str('filtersearch', 'totara_engage')"
            :aria-label="$str('filtersearch', 'totara_engage')"
            :placeholder="searchPlaceholder"
          />
        </template>
      </FilterBar>

      <div v-if="showSort" class="tui-contributionFilter__sort">
        <SelectFilter
          v-model="sort"
          :label="$str('sortby', 'core')"
          :show-label="true"
          :options="filter.sort.options"
        />
      </div>
    </template>
  </section>
</template>

<script>
import FilterBar from 'tui/components/filters/FilterBar';
import SelectFilter from 'tui/components/filters/SelectFilter';
import SearchFilter from 'tui/components/filters/SearchFilter';

// GraphQL
import getFilterOptions from 'totara_engage/graphql/get_filter_options';

export default {
  components: {
    FilterBar,
    SelectFilter,
    SearchFilter,
  },

  props: {
    hasBottomBar: Boolean,
    hasTopBar: Boolean,
    component: {
      type: String,
      required: true,
    },
    area: {
      type: String,
      required: true,
    },
    showAccess: {
      type: Boolean,
      default: true,
    },
    showType: {
      type: Boolean,
      default: true,
    },
    showTopic: {
      type: Boolean,
      default: true,
    },
    showSort: {
      type: Boolean,
      default: true,
    },
    showSection: {
      type: Boolean,
      default: false,
    },
    showSearch: {
      type: Boolean,
      default: false,
    },
    searchPlaceholder: {
      type: String,
      required: false,
    },
    value: {
      type: [Array, Object],
      required: true,
    },
    initialFilters: {
      type: Object,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      sortLabel: this.$str('sortby', 'core'),
      filter: this.initialFilters,
      selection: {
        access: this.value.access,
        type: this.value.type,
        topic: this.value.topic,
        section: this.value.section,
        search: this.value.search,
      },
    };
  },

  apollo: {
    filter: {
      query: getFilterOptions,
      variables() {
        return {
          component: this.component,
          area: this.area,
        };
      },
      skip() {
        return this.initialFilters !== null;
      },
      update({ accesses, types, topics, sorts, sections }) {
        topics = topics.map(({ id, value }) => {
          return {
            id: id,
            value: id,
            label: value,
          };
        });

        // Adding default selected topic value.
        topics.unshift({
          id: null,
          label: this.$str('all', 'core'),
        });

        return {
          access: {
            options: accesses.options,
            label: accesses.label,
          },

          type: {
            options: types.options,
            label: types.label,
          },

          topic: {
            options: topics,
            label: this.$str('filtertopic', 'totara_engage'),
          },

          sort: {
            options: sorts.options,
            sort: sorts.label,
          },

          section: {
            options: sections.options,
            label: sections.label,
          },
        };
      },
    },
  },

  computed: {
    access: {
      get() {
        return this.value.access;
      },

      set(value) {
        this.$emit('access', {
          value: value,
        });
      },
    },

    type: {
      get() {
        return this.value.type;
      },
      set(value) {
        this.$emit('type', {
          value: value,
        });
      },
    },

    topic: {
      get() {
        return this.value.topic;
      },
      set(value) {
        this.$emit('topic', {
          value: value,
        });
      },
    },

    sort: {
      get() {
        return this.value.sort;
      },
      set(value) {
        this.$emit('sort', {
          value: value,
        });
      },
    },

    section: {
      get() {
        return this.value.section;
      },
      set(value) {
        this.$emit('section', {
          value: value,
        });
      },
    },

    search: {
      get() {
        return this.value.search;
      },
      set(value) {
        this.$emit('search', {
          value: value,
        });
      },
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "all",
    "sortby"
  ],
  "totara_engage": [
    "filteraccess",
    "filterbartitle",
    "filtertype",
    "filtertopic",
    "filtersection",
    "filtersearch",
    "searchlibrary"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contributionFilter {
  .tui-formLabel {
    @include tui-font-heading-label-small;
  }

  &__sort {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    margin-top: var(--gap-4);
    margin-right: 0;
  }
}
</style>
