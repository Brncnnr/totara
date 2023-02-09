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
  <div
    class="tui-engagelayoutOneColumnContentWithSidepanel"
    :class="{
      'tui-engagelayoutOneColumnContentWithSidepanel-fullSidePanel':
        currentBoundaryName !== null && gridUnitsRight === 12,
    }"
  >
    <Responsive
      :breakpoints="[
        { name: 'xsmall', boundaries: [0, 599] },
        { name: 'small', boundaries: [600, 767] },
        { name: 'medium', boundaries: [768, 1192] },
        { name: 'large', boundaries: [1193, 1396] },
        { name: 'xlarge', boundaries: [1397, 1672] },
      ]"
      @responsive-resize="$_resize"
    >
      <Grid v-if="currentBoundaryName !== null" direction="horizontal">
        <GridItem v-if="gridUnitsLeft > 0" :units="gridUnitsLeft">
          <div class="tui-engagelayoutOneColumnContentWithSidepanel__heading">
            <slot name="header" />
          </div>
          <Grid direction="horizontal">
            <GridItem :units="gridUnitsColumn.gapLeft" />
            <GridItem :units="gridUnitsColumn.content">
              <slot
                name="column"
                :units="gridUnitsLeft"
                :boundary-name="currentBoundaryName"
                direction="horizontal"
              />
            </GridItem>
            <GridItem :units="gridUnitsColumn.gapRight" />
          </Grid>
        </GridItem>

        <GridItem :units="gridUnitsRight">
          <SidePanel
            ref="sidepanel"
            direction="rtl"
            :animated="!onSmallScreen"
            sticky
            :show-button-control="true"
            :initially-open="sidePanelIsOpen"
            :overflows="false"
            @sidepanel-expanding="expandRequest"
            @sidepanel-collapsing="collapseRequest"
          >
            <slot
              name="sidepanel"
              :units="gridUnitsRight"
              :boundary-name="currentBoundaryName"
              direction="horizontal"
            />
          </SidePanel>
        </GridItem>
      </Grid>
    </Responsive>
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Responsive from 'tui/components/responsive/Responsive';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import { WebStorageStore } from 'tui/storage';
const storage = new WebStorageStore('engage', window.localStorage);

export default {
  components: {
    Grid,
    GridItem,
    Responsive,
    SidePanel,
  },
  data() {
    return {
      boundaryDefaults: {
        xsmall: {
          gridUnitsLeftExpanded: 0,
          gridUnitsLeftCollapsed: 11,
          gridUnitsRightExpanded: 12,
          gridUnitsRightCollapsed: 1,
        },
        small: {
          gridUnitsLeftExpanded: 0,
          gridUnitsLeftCollapsed: 11,
          gridUnitsRightExpanded: 12,
          gridUnitsRightCollapsed: 1,
        },
        medium: {
          gridUnitsLeftExpanded: 7,
          gridUnitsLeftCollapsed: 11,
          gridUnitsRightExpanded: 5,
          gridUnitsRightCollapsed: 1,
        },
        large: {
          gridUnitsLeftExpanded: 7,
          gridUnitsLeftCollapsed: 11,
          gridUnitsRightExpanded: 5,
          gridUnitsRightCollapsed: 1,
        },
        xlarge: {
          gridUnitsLeftExpanded: 7,
          gridUnitsLeftCollapsed: 11,
          gridUnitsRightExpanded: 5,
          gridUnitsRightCollapsed: 1,
        },
      },

      // Note: the initial state of the boundary or side panel should not be set to any default value, as
      // it will calculate the wrong initial state of other components within this layout.
      currentBoundaryName: null,
      sidePanelIsOpen: null,
    };
  },
  computed: {
    gridUnitsColumn() {
      if (this.sidePanelIsOpen) {
        if (this.onBigScreen) return { gapLeft: 2, content: 8, gapRight: 2 };
        if (this.currentBoundaryName === 'medium')
          return { gapLeft: 1, content: 10, gapRight: 1 };
        if (this.currentBoundaryName === 'small')
          return { gapLeft: 0, content: 0, gapRight: 0 };
        // equal to `if (this.currentBoundaryName === 'xsmall')`
        else return { gapLeft: 0, content: 0, gapRight: 0 };
      } else {
        // When sidePanel is closed
        if (this.onBigScreen) return { gapLeft: 3, content: 6, gapRight: 3 };
        if (this.currentBoundaryName === 'medium')
          return { gapLeft: 2, content: 8, gapRight: 2 };
        if (this.currentBoundaryName === 'small')
          return { gapLeft: 1, content: 10, gapRight: 1 };
        // equal to `if (this.currentBoundaryName === 'xsmall')`
        else return { gapLeft: 0, content: 12, gapRight: 0 };
      }
    },
    gridUnitsLeft() {
      let left = this.sidePanelIsOpen
        ? 'gridUnitsLeftExpanded'
        : 'gridUnitsLeftCollapsed';
      return this.boundaryDefaults[this.currentBoundaryName][left];
    },
    gridUnitsRight() {
      let right = this.sidePanelIsOpen
        ? 'gridUnitsRightExpanded'
        : 'gridUnitsRightCollapsed';
      return this.boundaryDefaults[this.currentBoundaryName][right];
    },
    onBigScreen() {
      return (
        this.currentBoundaryName === 'xlarge' ||
        this.currentBoundaryName === 'large'
      );
    },
    onSmallScreen() {
      return (
        this.currentBoundaryName === 'xsmall' ||
        this.currentBoundaryName === 'small'
      );
    },
  },
  watch: {
    sidePanelIsOpen(val) {
      if (this.$el.offsetWidth > 767) {
        storage.set('sidepanel', { isOpen: val });
      }
    },
  },
  mounted() {
    // Never start with the side panel open on mobile.
    // We use innerWidth directly because currentBoundary isn't know on at this point or even in next tick.
    if (this.$el.offsetWidth > 767) {
      let state = true;
      const sidePanelState = storage.get('sidepanel');
      if (sidePanelState) {
        state = sidePanelState.isOpen;
      }
      this.sidePanelIsOpen = state;
    }
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

    expandRequest: function() {
      this.sidePanelIsOpen = true;
    },
    collapseRequest: function() {
      this.sidePanelIsOpen = false;
    },
  },
};
</script>

<style lang="scss">
.tui-engagelayoutOneColumnContentWithSidepanel {
  &-fullSidePanel {
    > .tui-responsive > .tui-grid > .tui-grid-item {
      border-left: none;
    }
  }
}
</style>
