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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @package mod_contentmarketplace
-->

<template>
  <!-- 
    Outer layout, doesn't provide title or other standard slots
    Slots provided
      * banner-content, (Optional) for adding content over the banner
      * main-content, for main content
      * modals, for any page level modals
      * side-panel, for content in left panel
  -->
  <div class="tui-marketplaceOuterLayoutBannerSidepanel">
    <Loader :loading="loading">
      <Responsive :breakpoints="breakpoints" @responsive-resize="resize">
        <Grid
          :direction="gridDirection"
          :max-units="24"
          :use-vertical-gap="false"
        >
          <!-- Left content (side panel) -->
          <GridItem :units="gridUnitsLeft">
            <slot name="side-panel" :outer-stacked="stacked" />
          </GridItem>

          <!-- Right content (banner and inner page layout) -->
          <GridItem
            :units="gridUnitsRight"
            class="tui-marketplaceOuterLayoutBannerSidepanel__right"
          >
            <!-- Banner -->
            <div class="tui-marketplaceOuterLayoutBannerSidepanel__banner">
              <!-- Banner image (set to ratio) -->
              <div
                class="tui-marketplaceOuterLayoutBannerSidepanel__banner-image"
                :style="{ 'background-image': bannerImage }"
              />

              <!-- Banner extra content  -->
              <div
                v-if="$scopedSlots['banner-content']"
                class="tui-marketplaceOuterLayoutBannerSidepanel__banner-content"
              >
                <div
                  class="tui-marketplaceOuterLayoutBannerSidepanel__banner-contentArea"
                >
                  <slot name="banner-content" :outer-stacked="stacked" />
                </div>
              </div>
            </div>

            <!-- Body content -->
            <div class="tui-marketplaceOuterLayoutBannerSidepanel__body">
              <slot name="main-content" :outer-stacked="stacked" />
            </div>
          </GridItem>
        </Grid>
      </Responsive>
    </Loader>

    <slot name="modals" />
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Loader from 'tui/components/loading/Loader';
import Responsive from 'tui/components/responsive/Responsive';

export default {
  components: {
    Grid,
    GridItem,
    Loader,
    Responsive,
  },

  props: {
    // Image url for banner
    bannerImageUrl: [Boolean, String],
    // Display loader over all content
    loading: Boolean,
  },

  data() {
    return {
      boundaryDefaults: {
        small: {
          gridDirection: 'vertical',
          gridUnitsLeft: 12,
          gridUnitsRight: 12,
        },
        medium: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 6,
          gridUnitsRight: 18,
        },
        large: {
          gridDirection: 'horizontal',
          gridUnitsLeft: 5,
          gridUnitsRight: 19,
        },
      },
      // Breakpoints for layout, changing these will impact child layouts
      breakpoints: [
        { name: 'small', boundaries: [0, 1167] },
        { name: 'medium', boundaries: [1165, 1422] },
        { name: 'large', boundaries: [1420, 1681] },
      ],
      currentBoundary: 'large',
    };
  },

  computed: {
    /**
     * Construct a background image URL
     *
     * @return {String}
     */
    bannerImage() {
      let url = this.bannerImageUrl;
      return !url || url === null ? '' : 'url("' + encodeURI(url) + '")';
    },

    /**
     * Return the grid direction
     *
     * @return {String}
     */
    gridDirection() {
      if (!this.currentBoundary) {
        return;
      }
      return this.boundaryDefaults[this.currentBoundary].gridDirection;
    },

    /**
     * Return the number of grid units for side panel
     *
     * @return {Number}
     */
    gridUnitsLeft() {
      if (!this.currentBoundary) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundary].gridUnitsLeft;
    },

    /**
     * Return the number of grid units for main content
     *
     * @return {Number}
     */
    gridUnitsRight() {
      if (!this.currentBoundary) {
        return;
      }

      return this.boundaryDefaults[this.currentBoundary].gridUnitsRight;
    },

    /**
     * Check if the grid is stacked
     *
     * @return {Bool}
     */
    stacked() {
      return this.gridDirection === 'vertical';
    },
  },

  methods: {
    /**
     * Handles responsive resizing which wraps the grid layout for this page
     *
     * @param {String} boundaryName
     */
    resize(boundaryName) {
      this.currentBoundary = boundaryName;
    },
  },
};
</script>

<style lang="scss">
.tui-marketplaceOuterLayoutBannerSidepanel {
  @include tui-font-body();

  &__banner {
    position: relative;
    left: calc(var(--grid-gutter) * -1);
    width: calc(var(--grid-gutter) + 100%);

    &-image {
      min-height: 120px;
      padding-bottom: 18.04%;
      background-color: var(--color-neutral-3);
      background-position: center;
      background-size: cover;
    }

    &-content {
      position: absolute;
      top: 0;
      width: 100%;
      height: 100%;
      padding: var(--gap-4);
    }

    &-contentArea {
      display: flex;
    }
  }

  &__body {
    margin: var(--gap-10) var(--gap-4) var(--gap-10) 0;
  }
}
</style>
