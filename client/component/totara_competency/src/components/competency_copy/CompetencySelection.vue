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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyCopyPathwaySelection">
    <!-- Framework heading -->
    <h4 class="tui-competencyCopyPathwaySelection__heading">
      {{ frameworkName }}
    </h4>

    <!-- Filters -->
    <FilterBar
      v-model="filterValues"
      :has-top-bar="true"
      :has-bottom-bar="true"
      :title="$str('a11y_filter_competencies', 'totara_competency')"
    >
      <template v-slot:filters-left>
        <!-- Toggle exclude competencies with achievement paths -->
        <ToggleSwitch
          v-model="filterValues.withoutPaths"
          :text="$str('without_achievement_paths', 'totara_competency')"
          :toggle-first="true"
        />
      </template>

      <template v-slot:filters-right="{ stacked }">
        <SearchFilter
          v-model="filterValues.search"
          :label="$str('search_competencies_by_name', 'totara_competency')"
          :placeholder="$str('competency', 'totara_competency')"
          :stacked="stacked"
        />
      </template>
    </FilterBar>

    <Loader :loading="loading">
      <div class="tui-competencyCopyPathwaySelection__body">
        <CompetencyCrumbtrail
          :crumbtrail-data="crumbtrailData"
          :current-level="currentLevel"
          :loading="loading"
          :searching="filterValues.search.length > 0"
          @change-competency-level="$emit('change-competency-level', $event)"
        />

        <!-- Competency count -->
        <div
          ref="selection-count"
          class="tui-competencyCopyPathwaySelection__count"
          tabindex="-1"
        >
          <SkeletonContent
            v-if="loading"
            :char-length="15"
            :has-overlay="true"
          />
          <span v-else>
            {{ $str('competencies', 'totara_competency', totalCompetencies) }}
          </span>
        </div>

        <!-- Competency list -->
        <CompetencySelectionTable
          class="tui-competencyCopyPathwaySelection__list"
          :competencies="competencies.items"
          :loading="loading"
          :selected-competencies="selectedCompetencies"
          :source-competency-id="sourceCompetencyId"
          @change-competency-level="$emit('change-competency-level', $event)"
          @update="$emit('update', $event)"
        />

        <!-- Paging -->
        <div class="tui-competencyCopyPathwaySelection__paging">
          <Paging
            v-if="totalCompetencies >= pageLimit"
            :has-loading-overlay="true"
            :items-per-page="pageLimit"
            :loading="loading"
            :page="currentPage"
            :total-items="totalCompetencies"
            @count-change="setItemsPerPage"
            @page-change="setPaginationPage"
          />
        </div>
      </div>
    </Loader>
  </div>
</template>

<script>
import CompetencyCrumbtrail from 'totara_competency/components/competency_copy/CompetencyCopyCrumbtrail';
import CompetencySelectionTable from 'totara_competency/components/competency_copy/CompetencyCopySelectionTable';
import FilterBar from 'tui/components/filters/FilterBar';
import Loader from 'tui/components/loading/Loader';
import Paging from 'tui/components/paging/Paging';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SkeletonContent from 'tui/components/loading/SkeletonContent';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    CompetencyCrumbtrail,
    CompetencySelectionTable,
    FilterBar,
    Loader,
    Paging,
    SearchFilter,
    SkeletonContent,
    ToggleSwitch,
  },

  props: {
    // List of available competencies
    competencies: {
      type: Object,
    },
    // Current page of list data
    currentPage: {
      type: Number,
      required: true,
    },
    // Values of filter bar options
    filterValues: {
      type: Object,
      required: true,
    },
    // Name of framework competencies are in
    frameworkName: {
      type: String,
      required: true,
    },
    // Competency content is currently loading
    loading: {
      type: Boolean,
    },
    // Number of items per page
    pageLimit: {
      type: Number,
      required: true,
    },
    // List of selected competency ID's
    selectedCompetencies: {
      type: Array,
      required: true,
    },
    // The id of the competency we're copying from
    sourceCompetencyId: {
      type: Number,
    },
  },

  computed: {
    /**
     * Parent path data for crumbtrail
     *
     * @return {Array|null}
     */
    crumbtrailData() {
      if (!this.currentLevel || !this.competencies.parents) {
        return null;
      }

      let path = this.competencies.parents.slice();

      path.unshift({
        id: null,
        name: this.$str('framework', 'totara_competency'),
      });

      return path;
    },

    /**
     * Name of current level
     *
     * @return {String|null}
     */
    currentLevel() {
      if (!this.competencies.current_level) {
        return null;
      }

      return this.competencies.current_level.name;
    },

    /**
     * The total number of available competencies
     *
     * @return {Number}
     */
    totalCompetencies() {
      return this.competencies && this.competencies.total
        ? this.competencies.total
        : 0;
    },
  },

  methods: {
    /**
     * Update number of items displayed in paginated selection results
     *
     * @param {Number} limit
     */
    setItemsPerPage(limit) {
      if (this.$refs['selection-count']) {
        this.$refs['selection-count'].scrollIntoView();
      }

      this.$emit('items-per-page-change', limit);
    },

    /**
     * Update current paginated page of selection results
     *
     * @param {Number} page
     */
    setPaginationPage(page) {
      if (this.$refs['selection-count']) {
        this.$refs['selection-count'].scrollIntoView();
      }

      this.$emit('page-change', page);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "a11y_filter_competencies",
      "competencies",
      "competency",
      "framework",
      "search_competencies_by_name",
      "without_achievement_paths"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyCopyPathwaySelection {
  &__count {
    @include tui-font-heading-label();
  }

  &__heading {
    margin: 0;
    @include tui-font-heading-x-small();
  }

  &__crumbtrail {
    &-list {
      margin: 0;
      list-style: none;
    }

    &-listCurrent {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-listItem {
      @include tui-font-body-small();
      display: inline;
    }

    &-listMultipleItems {
      margin-top: var(--gap-4);
    }
  }

  & > * + * {
    margin-top: var(--gap-4);
  }

  &__body {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__list {
    margin-top: var(--gap-3);
  }
}
</style>
