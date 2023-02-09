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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<!--
This layout is capable of the following:
  1. Layout where no side panels are needed.
  2. Left side panel with one column.
  3. Right side panel with one column.
  4. Left and right side panel with one column in middle.

  For a visual example please refer to:
   totara/samples/tui/components/samples/totara_core/layouts/LayoutOneColumnWithMultiSidePanel.vue
-->
<template>
  <div class="tui-engagelayoutOneColumnWithMultiSidePanel">
    <Responsive
      :breakpoints="responsiveBreakpoints"
      class="tui-engagelayoutOneColumnWithMultiSidePanel__responsiveContainer"
      @responsive-resize="$_resize"
    >
      <Grid
        :direction="gridDirection"
        :stack-at="stackAt"
        :use-vertical-gap="false"
      >
        <!-- LeftSide -->
        <GridItem
          v-if="showLeftSidePanel"
          :units="gridUnitsOuterLeft"
          :grows="true"
        >
          <!-- LeftSidePanel -->
          <SidePanel
            ref="sidePanelLeft"
            class="tui-engagelayoutOneColumnWithMultiSidePanel__leftSidePanel"
            direction="ltr"
            :animated="leftAnimated"
            :sticky="leftSticky"
            :initially-open="leftSidePanelInitiallyOpen"
            :overflows="leftSidePanelOverflows"
            :show-button-control="showLeftSidePanelControl"
            :min-height="minSidePanelHeight"
            @sidepanel-expanding="expandLeftRequest"
            @sidepanel-collapsing="collapseLeftRequest"
          >
            <slot
              name="sidePanelLeft"
              :grid-direction="gridDirection"
              :units="gridUnitsOuterLeft"
            />
          </SidePanel>
          <!-- /LeftSidePanel -->
        </GridItem>
        <!-- /LeftSide -->

        <!-- RightSide -->
        <GridItem
          class="tui-engagelayoutOneColumnWithMultiSidePanel__outerRight"
          :units="gridUnitsOuterRight"
          :grows="true"
        >
          <Grid direction="horizontal">
            <!-- Column -->
            <GridItem
              class="tui-engagelayoutOneColumnWithMultiSidePanel__column"
              :units="gridUnitsInnerLeft"
              :grows="false"
            >
              <div
                class="tui-engagelayoutOneColumnWithMultiSidePanel__columnContainer"
                :style="styleInnerLeft"
              >
                <slot
                  name="column"
                  :grid-direction="gridDirection"
                  :units="gridUnitsInnerLeft"
                />
              </div>
            </GridItem>
            <!-- /Column -->

            <!-- RightSidePanel -->
            <GridItem
              v-if="showRightSidePanel"
              :units="unitsInnerRight"
              :grows="false"
            >
              <div
                ref="sidePanelRightContainer"
                class="tui-engagelayoutOneColumnWithMultiSidePanel__rightSidePanelContainer"
                :style="styleInnerRight"
              >
                <SidePanel
                  ref="sidePanelRight"
                  class="tui-engagelayoutOneColumnWithMultiSidePanel__rightSidePanel"
                  direction="rtl"
                  :animated="rightAnimated"
                  :sticky="rightSticky"
                  :initially-open="rightSidePanelIsOpen"
                  :overflows="rightSidePanelOverflows"
                  :show-button-control="showRightSidePanelControl"
                  @sidepanel-expanding="expandRightRequest"
                  @sidepanel-collapsing="collapseRightRequest"
                >
                  <slot
                    name="sidePanelRight"
                    :grid-direction="gridDirection"
                    :units="unitsInnerRight"
                  />
                </SidePanel>
              </div>
            </GridItem>
            <!-- /RightSidePanel -->
          </Grid>
        </GridItem>
        <!-- /RightSide -->
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
  props: {
    /**
     * See Responsive implementation for more details on breakpoints.
     **/
    breakpoints: {
      type: Array,
    },

    /**
     * Object defining properties for each breakpoint. See this.boundaryDefaults
     * for an example on usage.
     **/
    boundaries: {
      type: Object,
    },

    /**
     * Name of key identifying a property in boundaries, used as the default boundary.
     */
    defaultBoundary: {
      type: String,
      default: 'large',
    },

    /**
     * See Grid implementation for more details on stacking.
     */
    stackAt: {
      type: Number,
      default: 764,
    },

    /**
     * Whether the SidePanels should display or not.
     */
    showLeftSidePanel: {
      type: Boolean,
      default: true,
    },
    showRightSidePanel: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether the SidePanel control (open and close button) should be displayed or not.
     */
    showLeftSidePanelControl: {
      type: Boolean,
      default: true,
    },
    showRightSidePanelControl: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether the SidePanel inner container should invoke a scrollbar if its
     * contents exceed its available height
     **/
    leftSidePanelOverflows: {
      type: Boolean,
      default: false,
    },
    rightSidePanelOverflows: {
      type: Boolean,
      default: false,
    },

    /**
     * Whether the SidePanel should be open when it is first rendered
     **/
    leftSidePanelInitiallyOpen: {
      type: Boolean,
      default: false,
    },
    rightSidePanelInitiallyOpen: {
      type: Boolean,
      default: false,
    },

    /**
     * Whether the SidePanel should remain wholly in the viewport when a long
     * page is scrolled
     **/
    leftSidePanelSticky: {
      type: Boolean,
      default: null,
    },
    rightSidePanelSticky: {
      type: Boolean,
      default: null,
    },

    /**
     * Whether transition lifecycles should be managed for CSS-based animations
     **/
    leftSidePanelAnimated: {
      type: Boolean,
      default: null,
    },
    rightSidePanelAnimated: {
      type: Boolean,
      default: null,
    },

    /**
     * Whether to set a CSS max-height value that is not `initial`
     **/
    leftSidePanelLimitHeight: {
      type: Boolean,
      default: null,
    },
    rightSidePanelLimitHeight: {
      type: Boolean,
      default: null,
    },

    /**
     * Whether the SidePanel's height should grow when scrolling, up to a max
     * height of the current size of the viewport
     **/
    leftSidePanelGrowHeightOnScroll: {
      type: Boolean,
      default: null,
    },
    rightSidePanelGrowHeightOnScroll: {
      type: Boolean,
      default: null,
    },
  },
  data() {
    return {
      breakpointDefaults: [
        { name: 'small', boundaries: [0, 764] },
        { name: 'medium', boundaries: [765, 1192] },
        { name: 'large', boundaries: [1193, 1672] },
      ],
      boundaryDefaults: {
        small: {
          gridDirection: 'vertical',
          gridUnitsOuterLeftExpanded: 12,
          gridUnitsOuterLeftCollapsed: 12,
          gridUnitsOuterRightExpanded: 6,
          gridUnitsOuterRightCollapsed: 11,
          gridUnitsInnerLeftExpanded: 6,
          gridUnitsInnerLeftCollapsed: 11,
          gridUnitsInnerRightExpanded: 6,
          gridUnitsInnerRightCollapsed: 1,
        },
        medium: {
          gridDirection: 'horizontal',
          gridUnitsOuterLeftExpanded: 3,
          gridUnitsOuterLeftCollapsed: 1,
          gridUnitsOuterRightExpanded: 9,
          gridUnitsOuterRightCollapsed: 11,
          gridUnitsInnerLeftExpanded: 6,
          gridUnitsInnerLeftCollapsed: 11,
          gridUnitsInnerRightExpanded: 6,
          gridUnitsInnerRightCollapsed: 1,
        },
        large: {
          gridDirection: 'horizontal',
          gridUnitsOuterLeftExpanded: 2,
          gridUnitsOuterLeftCollapsed: 1,
          gridUnitsOuterRightExpanded: 10,
          gridUnitsOuterRightCollapsed: 11,
          gridUnitsInnerLeftExpanded: 6,
          gridUnitsInnerLeftCollapsed: 11,
          gridUnitsInnerRightExpanded: 6,
          gridUnitsInnerRightCollapsed: 1,
        },
      },
      currentBoundaryName: this.defaultBoundary,
      leftSidePanelIsOpen: this.leftSidePanelInitiallyOpen,
      rightSidePanelIsOpen: this.rightSidePanelInitiallyOpen,
      styleInnerLeft: {},
      styleInnerRight: {},
      unitsInnerRight: 1,
    };
  },
  computed: {
    responsiveBreakpoints() {
      return this.breakpoints || this.breakpointDefaults;
    },

    responsiveBoundaries() {
      return this.boundaries || this.boundaryDefaults;
    },

    gridDirection() {
      return this.responsiveBoundaries[this.currentBoundaryName].gridDirection;
    },

    gridUnitsOuterLeft() {
      // If left SidePanel should not be shown then it takes up no columns.
      if (!this.showLeftSidePanel) return 0;

      let left = this.leftSidePanelIsOpen
        ? 'gridUnitsOuterLeftExpanded'
        : 'gridUnitsOuterLeftCollapsed';

      return this.responsiveBoundaries[this.currentBoundaryName][left];
    },

    gridUnitsOuterRight() {
      if (this.gridUnitsOuterLeft === 0) return 12;

      let right = this.leftSidePanelIsOpen
        ? 'gridUnitsOuterRightExpanded'
        : 'gridUnitsOuterRightCollapsed';

      return this.responsiveBoundaries[this.currentBoundaryName][right];
    },

    gridUnitsInnerLeft() {
      if (this.gridUnitsInnerRight === 0) return 12;

      let left = this.rightSidePanelIsOpen
        ? 'gridUnitsInnerLeftExpanded'
        : 'gridUnitsInnerLeftCollapsed';

      return this.responsiveBoundaries[this.currentBoundaryName][left];
    },

    gridUnitsInnerRight() {
      // If right SidePanel should not be shown then it takes up no columns.
      if (!this.showRightSidePanel) return 0;

      let right = this.rightSidePanelIsOpen
        ? 'gridUnitsInnerRightExpanded'
        : 'gridUnitsInnerRightCollapsed';

      return this.responsiveBoundaries[this.currentBoundaryName][right];
    },

    leftSticky() {
      if (this.leftSidePanelSticky != null) {
        return this.leftSidePanelSticky;
      }
      return this.currentBoundaryName !== 'small';
    },

    rightSticky() {
      if (this.leftSidePanelSticky != null) {
        return this.rightSidePanelSticky;
      }
      return this.currentBoundaryName !== 'small';
    },

    leftAnimated() {
      if (this.leftSidePanelAnimated != null) {
        return this.leftSidePanelAnimated;
      }
      return this.currentBoundaryName !== 'small';
    },

    rightAnimated() {
      if (this.rightSidePanelAnimated != null) {
        return this.rightSidePanelAnimated;
      }
      return this.currentBoundaryName !== 'small';
    },

    leftGrowHeightOnScroll() {
      if (this.leftSidePanelGrowHeightOnScroll != null) {
        return this.leftSidePanelGrowHeightOnScroll;
      }
      return this.currentBoundaryName !== 'small';
    },

    rightGrowHeightOnScroll() {
      if (this.rightSidePanelGrowHeightOnScroll != null) {
        return this.rightSidePanelGrowHeightOnScroll;
      }
      return this.currentBoundaryName !== 'small';
    },

    /**
     * Get the minimum side panel length, based on the main page container height.
     * The reason for this work around is that the side panel is only bound to the
     * height of the main vue container which has a minimum height based on its content.
     *
     * If the contents of the main vue container are relatively short and the page
     * footer is also short (no performance data or customizations), then on some
     * screen resolutions the side panel will not reach the bottom of the main page container.
     *
     * There are several layers of container divs that are defined in php meaning to
     * fix this problem with markup and css, it would require significant page restructuring.
     */
    minSidePanelHeight() {
      // Don't specify a min height when the side panel is stack on top of the content.
      if (this.gridDirection === 'vertical') {
        return null;
      }

      const mainContent = document.querySelector('#page');

      if (mainContent === null) {
        return null;
      }

      return mainContent.scrollHeight - 1 || null;
    },
  },

  watch: {
    gridUnitsInnerRight: {
      immediate: true,
      handler(units) {
        // If units is 12 then we need to do something special as the inner left and inner
        // right needs to display on the same line but with the inner right overlaying the
        // inner left.
        if (units === 12) {
          this.unitsInnerRight = this.responsiveBoundaries[
            this.currentBoundaryName
          ].gridUnitsInnerRightCollapsed;
          this.styleInnerRight.position = 'absolute';
          this.styleInnerRight.top = 0;
          this.styleInnerRight.left = 0;
          this.styleInnerRight.right = 0;
          this.styleInnerLeft.opacity = 0;
        } else {
          this.styleInnerRight = {};
          this.unitsInnerRight = units;
          this.styleInnerLeft.opacity = 1;
        }
      },
    },
    rightSidePanelIsOpen(val) {
      if (this.$el.offsetWidth > 764) {
        storage.set('sidepanel', { isOpen: val });
      }
    },
  },
  mounted() {
    // Never start with the side panel open on mobile.
    // We use innerWidth directly because currentBoundary isn't know on at this point or even in next tick.
    if (this.$el.offsetWidth > 764) {
      let state = this.rightSidePanelInitiallyOpen;
      const sidePanelState = storage.get('sidepanel');

      if (sidePanelState) {
        state = sidePanelState.isOpen;
      }
      this.rightSidePanelIsOpen = state;
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
      this.$emit('responsive-resize', boundaryName);
    },

    expandLeftRequest() {
      this.leftSidePanelIsOpen = true;
    },

    collapseLeftRequest() {
      this.leftSidePanelIsOpen = false;
    },

    expandRightRequest() {
      this.rightSidePanelIsOpen = true;
    },

    collapseRightRequest() {
      this.rightSidePanelIsOpen = false;
    },
  },
};
</script>

<style lang="scss">
.tui-engagelayoutOneColumnWithMultiSidePanel {
  display: flex;
  max-width: 100%;

  &__responsiveContainer {
    display: flex;
    flex-grow: 1;
    max-width: 100%;
  }

  &__outerRight {
    position: relative;
    display: flex;
  }
  &__rightSidePanelContainer {
    height: 100%;
  }
}
</style>
