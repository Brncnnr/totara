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
  <Collapsible
    :label="groupTitle"
    :initial-state="isExpanded"
    :always-render="true"
  >
    <Table :data="group.competencies" :expandable-rows="false">
      <template v-slot:header-row>
        <HeaderCell size="8">
          {{ $str('competency', 'totara_hierarchy') }}
        </HeaderCell>
        <HeaderCell size="4">
          <div class="tui-bulkManualRatingFrameworkGroup__headerWithHelp">
            {{ $str('last_rating_given', 'pathway_manual') }}
            <LastRatingHelp :is-for-self="role === roleSelf" />
          </div>
        </HeaderCell>
        <HeaderCell size="4">
          <div class="tui-bulkManualRatingFrameworkGroup__headerWithHelp">
            {{ $str('new_rating', 'pathway_manual') }}
            <InfoIconButton
              :is-help-for="$str('rating_scale', 'totara_competency')"
              position="right"
            >
              <RatingScaleOverview :scale="group" :show-descriptions="true" />
            </InfoIconButton>
          </div>
        </HeaderCell>
      </template>
      <template v-slot:row="{ row }">
        <Cell
          size="8"
          valign="center"
          :column-header="$str('competency', 'totara_hierarchy')"
        >
          {{ row.competency.display_name }}
        </Cell>
        <Cell
          size="4"
          valign="center"
          :column-header="$str('last_rating_given', 'pathway_manual')"
        >
          <LastRatingBlock
            :latest-rating="row.latest_rating"
            :current-user-id="currentUserId"
          />
        </Cell>
        <Cell
          size="4"
          valign="center"
          :column-header="$str('new_rating', 'pathway_manual')"
        >
          <RatingCell
            :comp-id="row.competency.id"
            :competency="row.competency.display_name"
            :scale="group"
            :rating="getRating(row.competency.id)"
            @update-rating="value => updateRating(row.competency.id, value)"
            @delete-rating="deleteRating(row.competency.id)"
          />
        </Cell>
      </template>
    </Table>
  </Collapsible>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import Collapsible from 'tui/components/collapsible/Collapsible';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import LastRatingBlock from 'pathway_manual/components/LastRatingBlock';
import LastRatingHelp from 'pathway_manual/components/LastRatingHelp';
import RatingCell from 'pathway_manual/components/RatingCell';
import RatingScaleOverview from 'totara_competency/components/RatingScaleOverview';
import Table from 'tui/components/datatable/Table';
import { ROLE_SELF } from 'pathway_manual/constants';

export default {
  components: {
    Cell,
    Collapsible,
    HeaderCell,
    InfoIconButton,
    LastRatingBlock,
    LastRatingHelp,
    RatingCell,
    RatingScaleOverview,
    Table,
  },

  props: {
    currentUserId: {
      required: true,
      type: Number,
    },
    role: {
      required: true,
      type: String,
    },
    group: {
      required: true,
      type: Object,
    },
    isExpanded: {
      default: true,
      type: Boolean,
    },
    selectedRatings: {
      required: true,
      type: Array,
    },
  },

  data() {
    return {
      roleSelf: ROLE_SELF,
      showRatingTooltip: false,
      showScaleTooltip: false,
    };
  },

  computed: {
    /**
     * The name of the framework and how many competencies it has.
     * @returns {String}
     */
    groupTitle() {
      let count = this.group.competencies.length;

      let string =
        count > 1
          ? 'competency_framework_count_plural'
          : 'competency_framework_count_singular';

      return this.$str(string, 'pathway_manual', {
        name: this.group.framework.display_name,
        count: count,
      });
    },
  },

  methods: {
    /**
     * Notify the parent of a new/updated rating.
     * @param {Number} competencyId
     * @param {Object} ratingData
     */
    updateRating(competencyId, ratingData) {
      this.$emit('update-rating', {
        competency_id: competencyId,
        scale_value_id: ratingData.scale_value_id,
        comment: ratingData.comment,
      });
    },

    /**
     * Notify the parent of a deleted rating.
     * @param {Number} competencyId
     */
    deleteRating(competencyId) {
      this.$emit('delete-rating', competencyId);
    },

    /**
     * Get the rating made for a specific competency.
     * @param {Number} competencyId
     * @returns {Object}
     */
    getRating(competencyId) {
      return this.selectedRatings.find(
        compData => compData.competency_id === competencyId
      );
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_manual": [
      "competency_framework_count_plural",
      "competency_framework_count_singular",
      "last_rating_given",
      "last_rating_given_other_tooltip",
      "last_rating_given_self_tooltip",
      "new_rating",
      "rater_details_removed",
      "rating_set_to_none"
    ],
    "totara_competency": [
      "rating_scale"
    ],
    "totara_hierarchy": [
      "competency"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingFrameworkGroup {
  &:not(:last-child) {
    margin-bottom: var(--gap-7);
  }

  &__headerWithHelp {
    display: flex;
    flex-direction: row;
  }
}
</style>
