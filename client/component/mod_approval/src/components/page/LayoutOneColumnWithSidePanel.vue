<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->

<template>
  <div
    class="tui-mod_approval-layoutOneColumnWithSidePanel"
    :class="{
      'tui-mod_approval-layoutOneColumnWithSidePanel--fullSidePanel':
        currentBoundaryName !== null && gridUnitsRight === 12,
      'tui-mod_approval-layoutOneColumnWithSidePanel--onSmallScreen': onSmallScreen,
    }"
  >
    <Responsive
      :breakpoints="[
        { name: 'xsmall', boundaries: [0, 480] },
        { name: 'small', boundaries: [481, 764] },
        { name: 'medium', boundaries: [765, 1192] },
        { name: 'large', boundaries: [1193, 1396] },
        { name: 'xlarge', boundaries: [1397, 1672] },
      ]"
      @responsive-resize="$_resize"
    >
      <Grid v-if="currentBoundaryName !== null">
        <!-- Place the sidepanel at the top (first) on small screens -->
        <GridItem
          v-if="smallerThanMedium.includes(currentBoundaryName)"
          key="sidepanel"
          :units="gridUnitsRight"
        >
          <slot
            name="sidepanel"
            :units="gridUnitsRight"
            :boundary-name="currentBoundaryName"
          />
        </GridItem>

        <GridItem v-show="gridUnitsLeft > 0" :units="gridUnitsLeft">
          <div class="tui-mod_approval-layoutOneColumnWithSidePanel__heading">
            <slot name="header" />
          </div>
          <slot
            name="column"
            :units="gridUnitsLeft"
            :boundary-name="currentBoundaryName"
          />
        </GridItem>

        <!-- Place the sidepanel to the right on medium or larger screens-->
        <GridItem
          v-if="!smallerThanMedium.includes(currentBoundaryName)"
          key="sidepanel"
          :units="gridUnitsRight"
        >
          <slot
            name="sidepanel"
            :units="gridUnitsRight"
            :boundary-name="currentBoundaryName"
          />
        </GridItem>
      </Grid>
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
  data() {
    return {
      boundaryDefaults: {
        xsmall: {
          gridUnitsLeftExpanded: 12,
          gridUnitsRightExpanded: 12,
        },
        small: {
          gridUnitsLeftExpanded: 12,
          gridUnitsRightExpanded: 12,
        },
        medium: {
          gridUnitsLeftExpanded: 8,
          gridUnitsRightExpanded: 4,
        },
        large: {
          gridUnitsLeftExpanded: 8,
          gridUnitsRightExpanded: 4,
        },
        xlarge: {
          gridUnitsLeftExpanded: 8,
          gridUnitsRightExpanded: 4,
        },
      },

      // Note: the initial state of the boundary or side panel should not be set to any default value, as
      // it will calculate the wrong initial state of other components within this layout.
      currentBoundaryName: null,

      smallerThanMedium: ['small', 'xsmall'],
    };
  },
  computed: {
    gridUnitsLeft() {
      return this.boundaryDefaults[this.currentBoundaryName]
        .gridUnitsLeftExpanded;
    },
    gridUnitsRight() {
      return this.boundaryDefaults[this.currentBoundaryName]
        .gridUnitsRightExpanded;
    },
    onSmallScreen() {
      return (
        this.currentBoundaryName === 'xsmall' ||
        this.currentBoundaryName === 'small'
      );
    },
  },
  methods: {
    /**
     * Handles responsive resizing which wraps the grid layout for this page
     *
     * @param {String} boundaryName
     **/
    $_resize(boundaryName) {
      this.currentBoundaryName = boundaryName;
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-layoutOneColumnWithSidePanel {
  &--fullSidePanel {
    > .tui-responsive > .tui-grid > .tui-grid-item {
      border-left: none;
    }
  }

  // Prevents the button edges from being hidden which would prevent the user
  // from selecting the button again
  &--onSmallScreen {
    > .tui-responsive > .tui-grid > .tui-grid-item {
      .tui-sidePanel {
        overflow: visible;
        &--closed {
          .tui-sidePanel__inner {
            overflow: hidden;
          }
        }
      }
    }
  }
}
</style>
