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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <Responsive
    :breakpoints="breakPoints"
    class="tui-engageCardsGrid"
    @responsive-resize="handleResize"
  >
    <VirtualScroll
      data-key="index"
      :data-list="rows"
      :page-mode="true"
      :aria-label="$str('items_list', 'totara_engage')"
      :is-loading="isLoading"
      @scrollbottom="scrolledToBottom"
    >
      <template v-slot:item="{ item: row }">
        <CoreGrid
          :direction="gridDirection"
          :max-units="maxUnits"
          class="tui-engageCardsGrid__row"
        >
          <GridItem
            v-for="(card, i) in row.items"
            :key="i"
            :units="cardUnits"
            class="tui-engageCardsGrid__card"
          >
            <EngageCard
              :card-attribute="card"
              :aria-labelledby="$id(`row-${row.index}-${i}-label`)"
              :label-id="$id(`row-${row.index}-${i}-label`)"
              :aria-posinset="row.index * itemsPerRow + i + 1"
              :aria-setsize="cards.length"
              :show-footnotes="showFootnotes"
              @refetch="$emit('refetch')"
            />
          </GridItem>
        </CoreGrid>
      </template>
      <template v-slot:footer>
        <PageLoader :fullpage="false" :loading="isLoading" />
      </template>
    </VirtualScroll>
  </Responsive>
</template>

<script>
import Responsive from 'tui/components/responsive/Responsive';
import CoreGrid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import { engageGrid, calculateRow } from 'totara_engage/index';
import EngageCard from 'totara_engage/components/card/compute/EngageCard';
import theme from 'tui/theme';
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';
import PageLoader from 'tui/components/loading/Loader';

export default {
  components: {
    Responsive,
    CoreGrid,
    GridItem,
    EngageCard,
    VirtualScroll,
    PageLoader,
  },

  inheritAttrs: false,

  props: {
    maxUnits: {
      type: [String, Number],
      validator: units => units > 0,
      default() {
        return theme.getVar('grid-maxunits');
      },
    },
    cards: {
      required: true,
      type: Array,
    },
    showFootnotes: {
      type: Boolean,
      default: false,
    },
    isLoading: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      // We need to keep track of the size.
      size: 'large',
    };
  },

  computed: {
    gridSize() {
      return engageGrid[this.size];
    },

    itemsPerRow() {
      let items = Math.floor(this.maxUnits / this.gridSize['cardUnits']);
      let maxItems = this.gridSize['maxItemsPerRow'];
      return items > maxItems ? maxItems : items;
    },

    rows() {
      return calculateRow(this.cards, this.itemsPerRow);
    },

    cardUnits() {
      return this.maxUnits / this.itemsPerRow;
    },

    gridDirection() {
      return this.gridSize['direction'];
    },

    breakPoints() {
      let breakpoints = Object.values(engageGrid);

      return breakpoints.map(({ name, boundaries }) => {
        return { name, boundaries };
      });
    },
  },

  methods: {
    handleResize(size) {
      this.size = size;
    },

    scrolledToBottom() {
      this.$emit('scrolledtobottom');
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "items_list"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageCardsGrid {
  &__row.tui-grid {
    margin-bottom: var(--gap-8);
  }

  &__card {
    display: flex;
  }
}
</style>
