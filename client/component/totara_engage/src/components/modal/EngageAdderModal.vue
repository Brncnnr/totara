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
  <Adder
    :open="open"
    :title="title"
    :existing-items="existingItems"
    :show-load-more="isLoadMore"
    :show-loading-btn="showLoadingBtn"
    @load-more="$emit('load-more')"
    @added="$emit('added', $event)"
    @cancel="$emit('cancel')"
  >
    <template v-slot:browse-filters>
      <ContributionFilter
        :component="filterComponent"
        :area="filterArea"
        :has-bottom-bar="true"
        :value="filterValue"
        :show-access="false"
        :show-type="false"
        :show-topic="true"
        :show-sort="false"
        :show-search="true"
        :show-section="true"
        @topic="$emit('topic', $event)"
        @search="$emit('search', $event)"
        @section="$emit('section', $event)"
      />
    </template>

    <template v-slot:browse-list="{ disabledItems, selectedItems, update }">
      <AdderBrowseAllTable
        :disabled-items="disabledItems"
        :selected-items="selectedItems"
        :cards="cards"
        @update="selectCard($event, update)"
      />
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <AdderSelectedTable
        :disabled-items="disabledItems"
        :selected-items="selectedItems"
        :cards="cardsSelected"
        @update="removeCard($event, update)"
      />
    </template>
  </Adder>
</template>

<script>
import Adder from 'tui/components/adder/Adder';
import ContributionFilter from 'totara_engage/components/contribution/Filter';
import AdderBrowseAllTable from 'totara_engage/components/table/AdderBrowseAllTable';
import AdderSelectedTable from 'totara_engage/components/table/AdderSelectedTable';

// Mixins
import AdderMixin from 'totara_engage/mixins/adder_mixin';

export default {
  components: {
    Adder,
    ContributionFilter,
    AdderBrowseAllTable,
    AdderSelectedTable,
  },

  mixins: [AdderMixin],

  props: {
    title: {
      type: String,
      required: true,
    },

    existingItems: {
      type: Array,
      default: () => [],
    },

    open: Boolean,
    showLoadingBtn: Boolean,

    filterValue: {
      type: Object,
      default: () => ({
        topic: null,
        search: null,
        section: null,
      }),
    },

    cards: {
      type: Array,
      required: true,
    },

    cursor: {
      type: Object,
      default() {
        return { total: null, next: null };
      },
    },

    filterComponent: {
      type: String,
      required: true,
    },

    filterArea: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      cardsSelected: [],
      isLoadMore: !!this.cursor.next,
    };
  },

  watch: {
    cursor(value) {
      this.isLoadMore = !!value.next;
    },
  },

  methods: {
    /**
     *
     * @param {Array} selected
     * @param {Function} update
     */
    selectCard(selected, update) {
      update(selected);

      this.cardsSelected = this.cards
        .filter(card => selected.includes(this.createCardId(card)))
        .map(card =>
          Object.assign({}, card, {
            id: this.createCardId(card),
          })
        );
    },

    /**
     *
     * @param {Array} selected
     * @param {Function} update
     */
    removeCard(selected, update) {
      update(selected);

      this.cardsSelected = this.cardsSelected.filter(card =>
        selected.includes(card.id)
      );
    },
  },
};
</script>
