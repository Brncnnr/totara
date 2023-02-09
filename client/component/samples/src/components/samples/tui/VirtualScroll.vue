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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module samples
-->

<template>
  <Tabs>
    <Tab id="list" name="List">
      <div class="tui-sample-virtualScroll__container">
        <VirtualScroll
          data-key="id"
          :data-list="items"
          :page-mode="false"
          aria-label="List"
          :is-loading="isLoading"
          @scrolltop="onScrollToTop"
          @scrollbottom="onScrollToBottom"
        >
          <template v-slot:item="{ item, posInSet, setSize }">
            <article
              class="tui-sample-virtualScroll__row"
              role="article"
              :aria-posinset="posInSet"
              :aria-setsize="setSize"
              :aria-labelledby="$id(`item-${item.id}`)"
              tabindex="0"
            >
              <h4 :id="$id(`item-${item.id}`)">
                {{ item.id }} {{ item.index }}
              </h4>
            </article>
          </template>

          <template v-slot:footer>
            <div class="loader-wrapper">
              <Loader :loading="isLoading" />
            </div>
          </template>
        </VirtualScroll>
      </div>
    </Tab>

    <Tab id="listPageMode" name="List (Page Mode)">
      <VirtualScroll
        data-key="id"
        :data-list="items"
        :page-mode="true"
        aria-label="List"
        :is-loading="isLoading"
        @scrolltop="onScrollToTop"
        @scrollbottom="onScrollToBottom"
      >
        <template v-slot:item="{ item, posInSet, setSize }">
          <article
            class="tui-sample-virtualScroll__row"
            role="article"
            :aria-posinset="posInSet"
            :aria-setsize="setSize"
            :aria-labelledby="$id(`item-${item.id}`)"
            tabindex="0"
          >
            <h4 :id="$id(`item-${item.id}`)">{{ item.id }} {{ item.index }}</h4>
          </article>
        </template>

        <template v-slot:footer>
          <div class="loader-wrapper">
            <Loader :loading="isLoading" />
          </div>
        </template>
      </VirtualScroll>
    </Tab>

    <Tab id="grid" name="Grid">
      <div class="tui-sample-virtualScroll__container">
        <VirtualScroll
          data-key="index"
          :data-list="rows"
          :page-mode="false"
          aria-label="List"
          :is-loading="isLoading"
          @scrolltop="onScrollToTop"
          @scrollbottom="onScrollToBottom"
        >
          <template v-slot:item="{ item: row }">
            <Grid
              class="tui-sample-virtualScroll__grid-row"
              :max-units="maxUnits + ''"
            >
              <GridItem
                v-for="(card, i) in row.items"
                :key="i"
                :units="itemUnits"
              >
                <article
                  class="tui-sample-virtualScroll__grid-card"
                  role="article"
                  :aria-posinset="row.index * rowSize + i + 1"
                  :aria-setsize="atEnd ? items.length : -1"
                  :aria-labelledby="$id(`row-${row.index}-${i}-label`)"
                  tabindex="0"
                >
                  <h4 :id="$id(`row-${row.index}-${i}-label`)">
                    {{ card.id }}
                  </h4>
                </article>
              </GridItem>
            </Grid>
          </template>

          <template v-slot:footer>
            <div class="tui-sample-virtualScroll__loader-wrapper">
              <Loader :loading="isLoading" />
            </div>
          </template>
        </VirtualScroll>
      </div>
    </Tab>

    <Tab id="gridPageMode" name="Grid (Page Mode)">
      <VirtualScroll
        data-key="index"
        :data-list="rows"
        :page-mode="true"
        aria-label="List"
        :is-loading="isLoading"
        @scrolltop="onScrollToTop"
        @scrollbottom="onScrollToBottom"
      >
        <template v-slot:item="{ item: row }">
          <Grid
            class="tui-sample-virtualScroll__grid-row"
            :max-units="maxUnits + ''"
          >
            <GridItem
              v-for="(card, i) in row.items"
              :key="i"
              :units="itemUnits"
            >
              <article
                class="tui-sample-virtualScroll__grid-card"
                role="article"
                :aria-posinset="row.index * rowSize + i + 1"
                :aria-setsize="atEnd ? items.length : -1"
                :aria-labelledby="$id(`row-${row.index}-${i}-label`)"
                tabindex="0"
              >
                <h4 :id="$id(`row-${row.index}-${i}-label`)">
                  {{ card.id }}
                </h4>
              </article>
            </GridItem>
          </Grid>
        </template>

        <template v-slot:footer>
          <div class="tui-sample-virtualScroll__loader-wrapper">
            <Loader :loading="isLoading" />
          </div>
        </template>
      </VirtualScroll>
    </Tab>
  </Tabs>
</template>

<script>
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';
import Loader from 'tui/components/loading/Loader';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Tabs from 'tui/components/tabs/Tabs';
import Tab from 'tui/components/tabs/Tab';

const uniqueIdGen = prefix => {
  return `${prefix}$${Math.random()
    .toString(16)
    .substr(5)}`;
};

let counter = 0;

const getPageData = (count, currentLength, l) => {
  const dataItems = [];
  for (let i = 0; i < count; i++) {
    const index = currentLength + i;
    counter++;
    dataItems.push({
      index,
      name: 'sdf',
      id: uniqueIdGen(index),
      desc: 'asd',
      count: l++,
    });
  }
  return dataItems;
};

const pageSize = 20;

export default {
  components: {
    VirtualScroll,
    Loader,
    Grid,
    GridItem,
    Tabs,
    Tab,
  },

  data() {
    return {
      nextPage: 2,
      items: getPageData(pageSize, 0, 0),
      itemUnits: 2,
      maxUnits: 12,
      isLoading: false,
      count: counter,
      atEnd: false, // no more items to load
    };
  },

  computed: {
    rows() {
      if (!Array.isArray(this.items)) return [];
      const rows = [];
      for (let index = 0; index < this.items.length; index += this.rowSize) {
        let row = this.items.slice(index, index + this.rowSize);
        rows.push({ index: rows.length, items: row });
      }
      return rows;
    },

    rowSize() {
      return Math.floor(this.maxUnits / this.itemUnits);
    },
  },

  methods: {
    onScrollToTop() {},

    onScrollToBottom() {
      if (this.isLoading) {
        return;
      }

      this.isLoading = true;

      setTimeout(() => {
        this.isLoading = false;
        this.items = this.items.concat(
          getPageData(pageSize, this.items.length, this.count)
        );
      }, 500);
    },
  },
};
</script>

<style lang="scss">
.tui-sample-virtualScroll {
  &__container {
    height: 600px;
    overflow: auto;
  }

  &__row {
    margin-bottom: var(--gap-4);
    padding: var(--gap-4);
    background-color: #ddd;
  }

  // add margin between grid rows (except last)
  &__grid-row:not(:last-child) {
    margin-bottom: var(--grid-gutter);
  }

  &__grid-card {
    height: 20rem;
    padding: var(--gap-4);
    background-color: #ddd;
  }

  &__loader-wrapper {
    padding: 1em;
  }
}
</style>
