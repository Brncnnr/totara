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
  @package totara_contentmarketplace
-->

<template>
  <div class="tui-contentMarketplaceImport">
    <!-- Let screen readers know the page content/flow has changed -->
    <div class="sr-only" aria-atomic="true" aria-live="polite" role="status">
      <template v-if="reviewingSelection">
        {{ $str('a11y_import_page_reviewing', 'totara_contentmarketplace') }}
      </template>
      <template v-else>
        {{ $str('a11y_import_page_selecting', 'totara_contentmarketplace') }}
      </template>
    </div>

    <!-- Selecting content layout -->
    <LayoutSelect
      v-if="!reviewingSelection"
      :title="selectionTitle"
      :sub-title="selectionSubTitle"
      :loading-right="loading"
      :stack-at="1150"
    >
      <!-- Back link -->
      <template v-slot:content-nav>
        <slot name="content-nav" />
      </template>

      <!-- Basket -->
      <template v-if="$slots.basket" v-slot:pre-body>
        <Grid :stack-at="1050">
          <GridItem :units="6" />
          <GridItem :units="6">
            <slot name="basket" />
          </GridItem>
        </Grid>
      </template>

      <!-- Filters -->
      <template v-slot:left-content>
        <aside class="tui-contentMarketplaceImport__filters">
          <HideShow
            :aria-region-label="
              $str('a11y_filter_panel', 'totara_contentmarketplace')
            "
            :hide-content-text="
              $str('hide_filters', 'totara_contentmarketplace')
            "
            :mobile-only="true"
            :show-content-text="
              $str('show_filters', 'totara_contentmarketplace')
            "
            :sticky="true"
          >
            <template
              v-slot:trigger="{ controls, expanded, text, toggleContent }"
            >
              <ButtonIcon
                :aria-controls="controls"
                :aria-label="false"
                class="tui-contentMarketplaceImport__filters-toggle"
                :class="{
                  'tui-contentMarketplaceImport__filters-toggleExpanded': expanded,
                }"
                :styleclass="{ transparent: true }"
                :text="text"
                @click="toggleContent"
              >
                <SliderIcon />
              </ButtonIcon>
            </template>

            <template v-slot:content>
              <div class="tui-contentMarketplaceImport__filters-content">
                <slot name="primary-filter" />
                <slot name="filters" contentId="contentMarketplaceImportBody" />
              </div>
            </template>
          </HideShow>
        </aside>
      </template>

      <!-- Selection data -->
      <template v-slot:right-content>
        <div
          id="contentMarketplaceImportBody"
          class="tui-contentMarketplaceImport__body"
          tabindex="-1"
        >
          <!-- Selection Overview-->
          <Grid :stack-at="600">
            <GridItem
              :units="8"
              class="tui-contentMarketplaceImport__summary-gridItem"
            >
              <slot name="summary-count" />
            </GridItem>
            <GridItem
              :units="4"
              class="tui-contentMarketplaceImport__summary-gridItem"
            >
              <slot name="summary-sort" />
            </GridItem>
          </Grid>

          <!-- Selection data table -->
          <slot name="select-table" />
        </div>
      </template>
    </LayoutSelect>

    <!-- Reviewing content layout -->
    <LayoutReview v-else :title="reviewTitle" :loading="loading">
      <template v-slot:content-nav>
        <slot name="content-nav" />
      </template>

      <!-- Basket -->
      <template v-slot:pre-body>
        <Grid :stack-at="1150">
          <GridItem :units="6" />
          <GridItem :units="6">
            <slot name="basket" />
          </GridItem>
        </Grid>
      </template>

      <template v-slot:content>
        <!-- Reviewing data table -->
        <div class="tui-contentMarketplaceImport__body">
          <slot name="review-table" />
        </div>
      </template>
    </LayoutReview>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import HideShow from 'tui/components/collapsible/HideShow';
import LayoutReview from 'tui/components/layouts/LayoutOneColumn';
import LayoutSelect from 'totara_contentmarketplace/components/layouts/PageLayoutTwoColumn';
import SliderIcon from 'tui/components/icons/Slider';

export default {
  components: {
    ButtonIcon,
    Grid,
    GridItem,
    HideShow,
    LayoutReview,
    LayoutSelect,
    SliderIcon,
  },

  props: {
    loading: Boolean,
    reviewTitle: String,
    reviewingSelection: Boolean,
    selectionTitle: {
      type: String,
      required: true,
    },
    selectionSubTitle: String,
  },
};
</script>

<lang-strings>
{
  "totara_contentmarketplace": [
    "a11y_filter_panel",
    "a11y_import_page_reviewing",
    "a11y_import_page_selecting",
    "hide_filters",
    "show_filters"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contentMarketplaceImport {
  &__body {
    & > * + * {
      margin-top: var(--gap-2);
    }

    &:focus {
      outline: none;
    }
  }

  &__filters {
    margin-right: var(--gap-6);

    &-content {
      & > * + * {
        margin-top: var(--gap-6);
      }
    }

    &-toggle {
      margin: 0 auto;
    }

    &-toggleExpanded {
      margin-bottom: var(--gap-5);
    }
  }

  &__summary {
    &-gridItem {
      display: flex;
      align-items: center;
    }
  }
}
</style>
