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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module theme_ventura
-->

<template>
  <div class="tui-layoutTwoColumn">
    <Responsive
      v-slot="slotProps"
      :breakpoints="[
        { name: 'small', boundaries: [0, 764] },
        { name: 'medium', boundaries: [765, 1192] },
        { name: 'large', boundaries: [1193, 1672] },
      ]"
    >
      <div v-if="slotProps.currentBoundaryName === 'small'">
        <Grid direction="vertical">
          <GridItem key="1">
            <h3
              v-if="$scopedSlots['page-title']"
              class="tui-layoutTwoColumn__heading"
            >
              <slot name="page-title" />
            </h3>
            <slot name="right" :units="12" :direction="'vertical'" />
          </GridItem>
          <GridItem key="2">
            <slot name="left" :units="12" :direction="'vertical'" />
          </GridItem>
        </Grid>
      </div>
      <!-- /small -->
      <div v-if="slotProps.currentBoundaryName === 'medium'">
        <Grid direction="horizontal">
          <GridItem key="2" :units="3">
            <slot name="left" :units="3" :direction="'horizontal'" />
          </GridItem>
          <GridItem key="1" :units="9">
            <h3
              v-if="$scopedSlots['page-title']"
              class="tui-layoutTwoColumn__heading"
            >
              <slot name="page-title" />
            </h3>
            <slot name="right" :units="9" :direction="'horizontal'" />
          </GridItem>
        </Grid>
      </div>
      <!-- /medium -->
      <div v-if="slotProps.currentBoundaryName === 'large'">
        <Grid direction="horizontal">
          <GridItem key="2" :units="2">
            <slot name="left" :units="2" :direction="'horizontal'" />
          </GridItem>
          <GridItem key="1" :units="10">
            <h3
              v-if="$scopedSlots['page-title']"
              class="tui-layoutTwoColumn__heading"
            >
              <slot name="page-title" />
            </h3>
            <slot name="right" :units="10" :direction="'horizontal'" />
          </GridItem>
        </Grid>
      </div>
      <!-- /large -->
    </Responsive>
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Responsive from 'tui/components/responsive/Responsive';

export default {
  components: {
    Grid,
    GridItem,
    Responsive,
  },
};
</script>

<style lang="scss">
.tui-layoutTwoColumn__heading {
  @include tui-font-heading-medium();
}
</style>
