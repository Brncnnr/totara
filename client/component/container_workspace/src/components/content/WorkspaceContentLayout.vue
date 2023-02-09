<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <Grid
    class="tui-workspaceContentLayout"
    :direction="gridDirection"
    :max-units="maxUnits"
    :stack-at="stackAt"
  >
    <GridItem :units="leftUnits">
      <!-- 60% of the grid system is for the content-->
      <slot name="content" />
    </GridItem>

    <GridItem :units="rightUnits">
      <!--
        40% rest of the grid is for the inner sidepanel.
      -->
      <slot name="side" />
    </GridItem>
  </Grid>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';

export default {
  components: {
    GridItem,
    Grid,
  },

  props: {
    maxUnits: {
      type: [Number, String],
      required: true,
    },

    stackAt: {
      type: Number,
      default: 764,
    },

    gridDirection: String,
  },

  computed: {
    /**
     * @return {Number}
     */
    leftUnits() {
      return Math.floor((60 * this.maxUnits) / 100);
    },

    /**
     *
     * @return {number}
     */
    rightUnits() {
      return this.maxUnits - this.leftUnits;
    },
  },
};
</script>
