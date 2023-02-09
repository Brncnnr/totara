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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <Loader :loading="outerFirstLoader && loading && initialLoad">
    <div
      class="tui-layoutOneColumnSidePanelActivities"
      :class="{
        'tui-layoutOneColumnSidePanelActivities--flush': flush,
      }"
    >
      <slot name="feedback-banner" />

      <slot name="user-overview" />

      <div class="tui-layoutOneColumnSidePanelActivities__heading">
        <slot name="content-nav" />

        <PageHeading :title="title">
          <template v-slot:buttons>
            <slot name="header-buttons" />
          </template>
        </PageHeading>
      </div>

      <Responsive
        :breakpoints="[
          { name: 'small', boundaries: [0, 451] },
          {
            name: 'medium',
            boundaries: [450, 734],
          },
          {
            name: 'large',
            boundaries: [733, 1366],
          },
          { name: 'xLarge', boundaries: [1365, 1642] },
        ]"
        @responsive-resize="resize"
      >
        <Grid :direction="gridDirection" :stack-at="733">
          <GridItem :units="gridUnitsLeft">
            <div
              class="tui-layoutOneColumnSidePanelActivities__sidePanel"
              :class="{
                'tui-layoutOneColumnSidePanelActivities__sidePanel--hasBorder':
                  gridDirection == 'horizontal',
              }"
            >
              <slot name="side-panel" />
            </div>
          </GridItem>
          <GridItem :units="gridUnitsRight">
            <Loader
              :loading="!initialLoad && loading"
              class="tui-layoutOneColumnSidePanelActivities__body"
            >
              <slot name="content" />
            </Loader>
          </GridItem>
        </Grid>
      </Responsive>

      <slot name="modals" />
    </div>
  </Loader>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Loader from 'tui/components/loading/Loader';
import PageHeading from 'tui/components/layouts/PageHeading';
import Responsive from 'tui/components/responsive/Responsive';

export default {
  components: {
    Grid,
    GridItem,
    Loader,
    PageHeading,
    Responsive,
  },

  props: {
    flush: Boolean,
    loading: Boolean,
    /** Use a outer wrapper loader for initial load,
     * used when the first load provides additional data
     * which isn't updated in subsequent requests
     */
    outerFirstLoader: Boolean,
    title: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      boundaryDefaults: {
        small: {
          gridDirection: 'vertical',
          gridUnitsLeft: 8,
          gridUnitsRight: 8,
        },
        medium: {
          gridDirection: 'vertical',
          gridUnitsLeft: 12,
          gridUnitsRight: 12,
        },
        large: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 3,
          gridUnitsRight: 9,
        },
        xLarge: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 2,
          gridUnitsRight: 10,
        },
      },
      currentBoundaryName: null,
      initialLoad: true,
    };
  },

  computed: {
    /**
     * Return the grid direction
     *
     * @return {Number}
     */
    gridDirection() {
      if (!this.currentBoundaryName) {
        return;
      }
      return this.boundaryDefaults[this.currentBoundaryName].gridDirection;
    },

    /**
     * Return the number of grid units for side panel
     *
     * @return {Number}
     */
    gridUnitsLeft() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsLeft;
    },

    /**
     * Return the number of grid units for main content
     *
     * @return {Number}
     */
    gridUnitsRight() {
      if (!this.currentBoundaryName) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundaryName].gridUnitsRight;
    },
  },

  watch: {
    loading() {
      this.initialLoad = false;
    },
  },

  methods: {
    /**
     * Handles responsive resizing which wraps the grid layout for this page
     *
     * @param {String} boundaryName
     */
    resize(boundaryName) {
      this.currentBoundaryName = boundaryName;
    },
  },
};
</script>

<style lang="scss">
.tui-layoutOneColumnSidePanelActivities {
  @include tui-font-body();
  margin-top: var(--gap-2);

  @include tui-stack-vertical(var(--gap-8));

  &__heading {
    @include tui-stack-vertical(var(--gap-2));
  }

  &--flush {
    margin-top: var(--gap-12);
  }

  &__sidePanel {
    &--hasBorder {
      border-right: var(--border-width-thin) solid var(--color-neutral-5);
    }
  }
}
</style>
