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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <Responsive
    v-slot="{ currentBoundaryName }"
    :breakpoints="[
      { name: 'small', boundaries: [0, 1330] },
      { name: null, boundaries: [1331, 1331] },
    ]"
  >
    <Grid v-if="data.items && data.items.length > 0" :stack-at="1329">
      <GridItem
        v-for="(item, key) in data.items"
        :key="key + item.name + item.overall_progress"
        :units="6"
      >
        <div
          class="tui-competencyCharts__chart"
          :class="{
            'tui-competencyCharts__chart--center':
              currentBoundaryName == 'small',
          }"
        >
          <IndividualAssignmentProgress
            :assignment-progress="item"
            :user-id="userId"
            :is-current-user="isCurrentUser"
          />
        </div>
      </GridItem>
    </Grid>
  </Responsive>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Responsive from 'tui/components/responsive/Responsive';
import IndividualAssignmentProgress from 'totara_competency/components/IndividualAssignmentProgress';

export default {
  components: {
    Grid,
    GridItem,
    Responsive,
    IndividualAssignmentProgress,
  },

  props: {
    data: {
      required: true,
      type: Object,
    },
    userId: {
      type: Number,
      required: true,
    },
    isCurrentUser: {
      type: Boolean,
      required: true,
    },
  },
};
</script>

<style lang="scss">
.tui-competencyCharts {
  &__chart {
    padding: var(--gap-7) var(--gap-5);
    border: 1px var(--color-neutral-5) solid;
    border-radius: 4px;

    &--center {
      max-width: 800px;
      margin-right: auto;
      margin-left: auto;
    }
  }
}
</style>
