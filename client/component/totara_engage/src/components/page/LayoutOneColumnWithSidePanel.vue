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
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <div
    class="tui-engagelayoutOneColumnWithSidepanel"
    :class="{
      'tui-engagelayoutOneColumnWithSidepanel--fullSidePanel':
        currentBoundaryName !== null && gridUnitsRight === 12,
      'tui-engagelayoutOneColumnWithSidepanel--onSmallScreen': onSmallScreen,
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
        <GridItem v-show="gridUnitsLeft > 0" :units="gridUnitsLeft">
          <div class="tui-engagelayoutOneColumnWithSidepanel__heading">
            <slot name="header" />
          </div>
          <slot
            name="column"
            :units="gridUnitsLeft"
            :boundary-name="currentBoundaryName"
          />
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
    onSmallScreen() {
      return (
        this.currentBoundaryName === 'xsmall' ||
        this.currentBoundaryName === 'small'
      );
    },
  },
  watch: {
    sidePanelIsOpen(val) {
      if (this.$el.offsetWidth > 764) {
        storage.set('sidepanel', { isOpen: val });
      }
    },
  },
  mounted() {
    // Never start with the side panel open on mobile.
    // We use innerWidth directly because currentBoundary isn't know on at this point or even in next tick.
    if (this.$el.offsetWidth > 764) {
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
.tui-engagelayoutOneColumnWithSidepanel {
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
