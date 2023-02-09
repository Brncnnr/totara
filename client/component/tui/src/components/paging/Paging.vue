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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-paging" :class="{ 'tui-paging--narrow': narrow }">
    <div v-if="loading" class="tui-paging__loading">
      <SkeletonContent char-length="full" :has-overlay="hasLoadingOverlay" />
    </div>
    <template v-else>
      <div class="tui-paging__perPage">
        <Label
          class="tui-paging__perPage-label"
          :for-id="$id('items-perpage')"
          :label="$str('itemsperpage', 'totara_core')"
        />
        <Select
          :id="$id('items-perpage')"
          :value="itemsPerPage"
          class="tui-paging__perPage-select"
          :options="perPageAmounts"
          @input="changeItemsPerPage"
        />
      </div>

      <div v-if="totalPages !== 1" class="tui-paging__selector">
        <ButtonIcon
          :disabled="page === 1"
          class="tui-paging__selector-number tui-paging__selector-number--previous"
          :styleclass="{ stealth: true }"
          :aria-label="$str('previouspage', 'totara_core')"
          @click.prevent="changePage(page - 1)"
        >
          <BackIcon />
        </ButtonIcon>
        <Button
          v-if="!display.includes(1)"
          class="tui-paging__selector-number tui-paging__selector-number--change"
          :styleclass="{ stealth: true }"
          text="1"
          :aria-label="$str('pagea', 'core', 1)"
          @click.prevent="changePage(1)"
        />
        <span
          v-if="!display.includes(2) && totalPages !== 1"
          aria-current="page"
          class="tui-paging__selector-number tui-paging__selector-number--spacer"
        >
          ...
        </span>
        <template v-for="n in display">
          <span
            v-if="n === page"
            :key="n"
            aria-current="page"
            class="tui-paging__selector-number tui-paging__selector-number--current"
          >
            {{ n }}
          </span>
          <Button
            v-else
            :key="n"
            class="tui-paging__selector-number tui-paging__selector-number--change"
            :styleclass="{ stealth: true }"
            :text="n.toString()"
            :aria-label="$str('pagea', 'core', n.toString())"
            @click.prevent="changePage(n)"
          />
        </template>
        <span
          v-if="!display.includes(totalPages - 1) && totalPages !== 1"
          class="tui-paging__selector-number tui-paging__selector-number--spacer"
        >
          ...
        </span>
        <Button
          v-if="!display.includes(totalPages) && totalPages !== 1"
          class="tui-paging__selector-number tui-paging__selector-number--change"
          :styleclass="{ stealth: true }"
          :text="totalPages.toString()"
          :aria-label="$str('pagea', 'core', totalPages.toString())"
          @click.prevent="changePage(totalPages)"
        />
        <ButtonIcon
          :disabled="page === totalPages"
          class="tui-paging__selector-number tui-paging__selector-number--next"
          :styleclass="{ stealth: true }"
          :aria-label="$str('nextpage', 'totara_core')"
          @click.prevent="changePage(page + 1)"
        >
          <ForwardIcon />
        </ButtonIcon>
      </div>
      <div v-if="totalPages !== 1" class="tui-paging__direct">
        <Label
          class="tui-paging__direct-label"
          :for-id="$id('target-page')"
          :label="$str('page', 'core')"
        />
        <InputText
          :id="$id('target-page')"
          v-model="newPage"
          autocomplete="off"
          input-type="numeric"
          class="tui-paging__direct-page"
          char-length="5"
          @keyup.native.enter="changePage()"
        />
        <Button
          class="tui-paging__direct-button"
          :text="$str('go', 'core')"
          :aria-label="$str('gotopage', 'totara_core')"
          :styleclass="{ stealth: true }"
          @click.prevent="changePage()"
        />
      </div>
    </template>
  </div>
</template>

<script>
import Label from 'tui/components/form/Label';
import Select from 'tui/components/form/Select';
import InputText from 'tui/components/form/InputText';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import BackIcon from 'tui/components/icons/BackArrow';
import ForwardIcon from 'tui/components/icons/ForwardArrow';
import { throttle } from 'tui/util';
import { getOffsetRect } from 'tui/dom/position';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import SkeletonContent from 'tui/components/loading/SkeletonContent';

const PER_PAGE_AMOUNTS = [10, 20, 50, 100];
const THROTTLE_UPDATE = 150;
const DISPLAY_PAGES_5 = 899;
const DISPLAY_PAGES_3 = 773;
const DISPLAY_PAGES_STACK = 664;

export default {
  components: {
    Label,
    Select,
    InputText,
    Button,
    ButtonIcon,
    BackIcon,
    ForwardIcon,
    SkeletonContent,
  },

  props: {
    page: {
      type: Number,
      required: true,
    },

    itemsPerPage: {
      type: Number,
      default() {
        return 10;
      },
      validator(value) {
        return PER_PAGE_AMOUNTS.includes(value);
      },
    },

    // Content has an external loading overlay
    hasLoadingOverlay: {
      type: Boolean,
    },

    // Content is loading
    loading: {
      type: Boolean,
    },

    totalItems: {
      type: Number,
      required: true,
    },
  },

  data() {
    let selectOptions = [];
    PER_PAGE_AMOUNTS.forEach(element => {
      selectOptions.push({ id: element, label: element });
    });

    return {
      perPageAmounts: selectOptions,
      displayCount: 5,
      newPage: this.page,
      narrow: false,
    };
  },

  computed: {
    /**
     * Get the total number of pages
     *
     * @returns {Number}
     */
    totalPages: function() {
      return Math.ceil(this.totalItems / this.itemsPerPage);
    },

    /**
     * Gets the start page number for the paging bar
     *
     * @returns {Array}
     */
    display() {
      const dc = this.displayCount;
      let start = this.page - Math.floor(dc / 2);
      let end = this.page + Math.floor(dc / 2);
      let pages = [];

      // in certain circumstances, all pages should be shown
      if (this.totalPages <= dc + 4) {
        for (let item = 1; item <= this.totalPages; item++) {
          pages.push(item);
        }

        return pages;
      }

      // on page 3 or the third from end, only return the current page
      if (dc === 1 && (this.page === 3 || this.page === this.totalPages - 2)) {
        return [this.page];
      }

      if (start <= 3) {
        start = 1;
        end = Math.max(dc + 2, end);
      }

      if (end >= this.totalPages - 2) {
        start = Math.min(this.totalPages - dc - 1, start);
        end = this.totalPages;
      }

      if (start < 1) {
        start = 1;
      }

      if (end > this.totalPages) {
        end = this.totalPages;
      }

      for (let item = start; item <= end; item++) {
        pages.push(item);
      }

      return pages;
    },
  },

  watch: {
    /**
     * Update Input box value when page changed externally
     *
     * @param {Number} value
     */
    page(value) {
      if (value) {
        this.newPage = value;
      }
    },
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(
      throttle(this.$_measure, THROTTLE_UPDATE)
    );
    this.resizeObserver.observe(this.$el);
    this.$_measure();
  },

  methods: {
    /**
     * Changes the number of items per page
     *
     * @param {Number} items the number of items that should now be displayed on the page
     * @emits Number the number of items that should be displayed on a page
     */
    changeItemsPerPage(items) {
      this.$emit('count-change', parseInt(items, 10));
    },

    /**
     * Changes the current page
     *
     * @param {Integer} page The page to change to
     * @emits Object {page: Integer} The page that it is being changed to
     */
    changePage(page) {
      if (page === undefined) {
        page = this.newPage;
      }
      if (page < 1) {
        page = 1;
      }
      if (page > this.totalPages) {
        page = this.totalPages;
      }

      this.$emit('page-change', parseInt(page, 10));
    },

    /**
     * Handles the responsiveness of the component
     */
    $_measure() {
      let width = getOffsetRect(this.$el).width;
      if (width > DISPLAY_PAGES_5) {
        this.displayCount = 5;
      } else if (width > DISPLAY_PAGES_3) {
        this.displayCount = 3;
      } else {
        this.displayCount = 1;
      }

      if (width < DISPLAY_PAGES_STACK) {
        this.narrow = true;
      } else {
        this.narrow = false;
      }
    },
  },
};
</script>
<lang-strings>
{
  "core": [
    "go",
    "page",
    "pagea"
  ],
  "totara_core": [
    "gotopage",
    "itemsperpage",
    "nextpage",
    "previouspage"
  ]
}
</lang-strings>
<style lang="scss">
.tui-paging {
  display: flex;
  flex-wrap: wrap;

  &__perPage {
    position: relative;
    display: flex;
    flex-direction: row;
    flex-grow: 1;
    flex-shrink: 0;
    align-items: center;
    margin: auto 0;

    &-label {
      margin: auto var(--gap-3) auto 0;
    }

    &-select {
      flex: 7rem 0 0;
    }
  }

  &__selector {
    display: flex;
    flex-shrink: 0;
    margin: auto 0;

    &-number {
      min-width: 40px;
      min-height: 40px;
      margin: auto 0;
      padding: var(--gap-4);

      &--current {
        font-weight: bold;
      }
    }
  }

  &__direct {
    position: relative;
    display: flex;
    flex-direction: row;
    flex-shrink: 0;
    align-items: center;
    margin: auto 0;
    padding-left: var(--gap-4);
    border-left: var(--border-width-thin) solid var(--color-neutral-5);

    .tui-paging--narrow & {
      padding-left: 0;
      border-left: none;
    }

    &-label {
      margin: auto var(--gap-2) auto 0;
    }

    & &-page[type] {
      margin: auto var(--gap-1) auto 0;
    }
  }

  &__loading {
    width: 100%;
  }
}
</style>
