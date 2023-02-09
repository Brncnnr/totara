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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module pathway_manual
-->

<template>
  <div>
    <div
      v-if="isRatingSingleCompetency"
      class="tui-bulkManualUserCompetenciesFilters__singleCompetencyMessage"
    >
      <span
        class="tui-bulkManualUserCompetenciesFilters__singleCompetencyMessage-text"
      >
        {{ $str('viewing_single_competency', 'pathway_manual') }}
      </span>
      <Button
        class="tui-bulkManualUserCompetenciesFilters__singleCompetencyMessage-button"
        :text="$str('view_all', 'pathway_manual')"
        :styleclass="{ small: true }"
        @click="viewAll"
      />
    </div>
    <FilterBar
      v-else-if="hasAnyFilters"
      v-model="selectedFilters"
      :title="this.$str('filter_competencies', 'totara_competency')"
    >
      <!-- Left aligned content -->
      <template v-slot:filters-left="{ stacked }">
        <SelectFilter
          v-if="filterOptions.competency_type"
          v-model="selectedFilters.competencyType"
          :label="$str('filter_competency_type', 'totara_competency')"
          :show-label="true"
          :options="competencyTypeFilterOptions"
          :stacked="stacked"
          @input="filtersSelected = true"
        />
        <SelectFilter
          v-if="filterOptions.assignment_reason"
          v-model="selectedFilters.assignmentReason"
          :label="$str('filter_reason_assigned', 'pathway_manual')"
          :show-label="true"
          :options="assignmentReasonFilterOptions"
          :stacked="stacked"
          @input="filtersSelected = true"
        />
        <SelectFilter
          v-if="filterOptions.rating_history"
          v-model="selectedFilters.ratingHistory"
          :label="$str('filter_rating_history', 'pathway_manual')"
          :show-label="true"
          :options="ratingHistoryFilterOptions"
          :stacked="stacked"
          @input="filtersSelected = true"
        />
      </template>

      <!-- Right aligned content -->
      <template v-slot:filters-right="{ stacked }">
        <ButtonFilter :stacked="stacked">
          <Button
            :text="$str('filter_update_selection', 'pathway_manual')"
            :styleclass="{ small: true }"
            :disabled="!filtersSelected"
            @click="updateFiltersWithWarning"
          />
        </ButtonFilter>
      </template>
    </FilterBar>
    <ConfirmationModal
      :open="showConfirmFiltersModal"
      :title="$str('modal_confirm_update_filters_title', 'pathway_manual')"
      @confirm="updateFilters"
      @cancel="showConfirmFiltersModal = false"
    >
      <span
        v-html="$str('modal_confirm_update_filters_body', 'pathway_manual')"
      />
    </ConfirmationModal>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonFilter from 'tui/components/filters/ButtonFilter';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import FilterBar from 'tui/components/filters/FilterBar';
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    Button,
    ButtonFilter,
    ConfirmationModal,
    FilterBar,
    SelectFilter,
  },

  props: {
    filterOptions: {
      required: true,
      type: Object,
    },
    hasRatings: {
      required: true,
      type: Boolean,
    },
    isRatingSingleCompetency: {
      default: false,
      type: Boolean,
    },
  },

  data() {
    return {
      selectedFilters: {
        competencyType: 0,
        assignmentReason: 0,
        ratingHistory: 0,
      },
      showConfirmFiltersModal: false,
      filtersSelected: false,
    };
  },

  computed: {
    /**
     * Are there any filters that can be selected?
     * @returns {boolean}
     */
    hasAnyFilters() {
      return (
        this.filterOptions.assignment_reason != null ||
        this.filterOptions.competency_type != null ||
        this.filterOptions.rating_history
      );
    },

    /**
     * Assignment reason filters that can be selected.
     * @returns {{id: *, label: *}[]}
     */
    assignmentReasonFilterOptions() {
      let filters = this.filterOptions.assignment_reason;

      filters = filters.map(reason => {
        return {
          id: reason.key,
          label: reason.reason,
        };
      });
      filters.unshift({
        id: 0,
        label: this.$str('all', 'core'),
      });

      return filters;
    },

    /**
     * Competency type filter options that can be selected.
     * @returns {{id: *, label: *}[]}
     */
    competencyTypeFilterOptions() {
      let filters = this.filterOptions.competency_type;

      filters = filters.map(type => {
        return {
          id: type.id,
          label: type.display_name,
        };
      });
      filters.unshift({
        id: 0,
        label: this.$str('all', 'core'),
      });

      return filters;
    },

    /**
     * Previous rating history filter options that can be selected.
     * @returns {{id: *, label: *}[]}
     */
    ratingHistoryFilterOptions() {
      return [
        {
          id: 0,
          label: this.$str('all', 'core'),
        },
        {
          id: -1,
          label: this.$str('never_rated', 'pathway_manual'),
        },
        {
          id: 1,
          label: this.$str('filter_previously_rated', 'pathway_manual'),
        },
      ];
    },

    /**
     * An MD5 hash table of assignment reason's associated assignments.
     *
     * An assignment reason is just a collection of assignment IDs,
     * and we can't use an array for the filter values in the DOM.
     * So instead we use a hash of the assignments for a reason, and
     * use the hash as the filter value in the DOM.
     *
     * @returns {Object}
     */
    assignmentHashMap() {
      let assignmentReason = this.filterOptions.assignment_reason;
      let map = {};
      for (let i = 0; i < assignmentReason.length; i++) {
        let reason = assignmentReason[i];
        map[reason.key] = reason.assignments.map(assignment => assignment.id);
      }
      return map;
    },
  },

  methods: {
    /**
     * Confirm that the user wants to change the screen before actually applying the filters.
     */
    updateFiltersWithWarning() {
      if (this.hasRatings) {
        this.showConfirmFiltersModal = true;
        return;
      }

      this.updateFilters();
    },

    /**
     * Apply the filters that have been selected by notify the parent of what has been selected.
     */
    updateFilters() {
      let filters = {};

      if (this.selectedFilters.competencyType !== 0) {
        filters.competency_type = this.selectedFilters.competencyType;
      }
      if (this.selectedFilters.assignmentReason !== 0) {
        filters.assignment_reason = this.assignmentHashMap[
          this.selectedFilters.assignmentReason
        ];
      }
      if (this.selectedFilters.ratingHistory !== 0) {
        filters.rating_history = this.selectedFilters.ratingHistory > 0;
      }

      this.filtersSelected = false;
      this.showConfirmFiltersModal = false;

      this.$emit('update-filters', filters);
    },

    /**
     * Reset any applied filters.
     */
    viewAll() {
      this.selectedFilters.competencyType = 0;
      this.selectedFilters.assignment = 0;
      this.selectedFilters.ratingHistory = 0;
      this.updateFiltersWithWarning();
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "all"
    ],
    "pathway_manual": [
      "filter_previously_rated",
      "filter_rating_history",
      "filter_reason_assigned",
      "filter_update_selection",
      "modal_confirm_update_filters_body",
      "modal_confirm_update_filters_title",
      "never_rated",
      "view_all",
      "viewing_single_competency"
    ],
    "totara_competency": [
      "filter_competency_type",
      "filter_competencies"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualUserCompetenciesFilters {
  &__singleCompetencyMessage {
    &-text {
      margin-right: var(--gap-2);
      @include tui-font-hint;
    }

    @media (max-width: $tui-screen-sm) {
      display: flex;
      align-content: space-between;

      &-text {
        margin-right: var(--gap-4);
      }

      &-button {
        flex-shrink: 0;
      }
    }
  }
}
</style>
