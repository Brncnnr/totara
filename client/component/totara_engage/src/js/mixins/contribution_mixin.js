/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module totara_engage
 */

import { config } from 'tui/config';

// GraphQL
import contributionCards from 'totara_engage/graphql/contribution_cards';

export default {
  props: {
    units: {
      type: Number,
      required: true,
    },
    gridDirection: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      skipCardsQuery: true,
      isLoadMoreVisible: false,
      contributionComponent: '',
      contributionArea: '',
      contributionSource: '',
      filterValue: {
        access: null,
        type: null,
        topic: null,
        sort: 1,
        section: null,
        search: null,
      },
      includeFootnotes: false,
      footnotes: {
        footnotes_type: null,
        footnotes_item_id: null,
        footnotes_area: null,
        footnotes_component: null,
      },
      contribution: {
        cursor: {
          total: 0,
          next: null,
        },
        cards: [],
      },
      loadingMore: false,
    };
  },

  apollo: {
    contribution: {
      query: contributionCards,
      fetchPolicy: 'network-only',
      loadingKey: 'loading',
      variables() {
        return Object.assign(
          {},
          this.filterValue,
          {
            component: this.contributionComponent,
            area: this.contributionArea,
            include_footnotes: this.includeFootnotes,
            source: this.contributionSource,
            theme: config.theme.name,
          },
          this.footnotes
        );
      },

      /**
       * Skip this query from executing. This is to give the different pages the
       * opportunity to inject parameters as this query will actually execute as
       * part of the init events and that is too early as the pages would not have
       * had given their extra filter values by then. We also don't want this query
       * to execute twice.
       *
       * @returns {boolean}
       */
      skip() {
        return this.skipCardsQuery;
      },
    },
  },

  /**
   * Set skipCardsQuery off once created.
   */
  created() {
    this.skipCardsQuery = false;
  },

  methods: {
    /**
     *
     * @param {String} value
     */
    filterAccess({ value }) {
      this.filterValue.access = value;
    },

    /**
     *
     * @param {Number} value
     */
    filterTopic({ value }) {
      this.filterValue.topic = value;
    },

    /**
     *
     * @param {String} value
     */
    filterType({ value }) {
      this.filterValue.type = value;
    },

    /**
     *
     * @param {Number} value
     */
    filterSort({ value }) {
      this.filterValue.sort = value;
    },

    /**
     *
     * @param {Number} value
     */
    filterSection({ value }) {
      this.filterValue.section = value;
    },

    /**
     *
     * @param {String} value
     */
    filterSearch({ value }) {
      this.filterValue.search = value;
    },

    async scrolledToBottom() {
      if (this.isLoadMoreVisible) {
        return;
      }
      await this.loadMoreItems();
      this.isLoadMoreVisible = true;
    },

    async loadMore() {
      await this.loadMoreItems();
      this.isLoadMoreVisible = false;
    },

    /**
     * Load additional items and append to list
     *
     */
    async loadMoreItems() {
      if (!this.contribution.cursor.next) {
        return;
      }
      this.loadingMore = true;
      this.$apollo.queries.contribution.fetchMore({
        variables: Object.assign(
          {},
          this.filterValue,
          {
            include_footnotes: this.includeFootnotes,
            cursor: this.contribution.cursor.next,
          },
          this.footnotes
        ),

        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult.contribution;
          const newData = fetchMoreResult.contribution;
          const newList = oldData.cards.concat(newData.cards);
          this.loadingMore = false;

          return {
            contribution: {
              cursor: newData.cursor,
              cards: newList,
            },
          };
        },
      });
    },
  },
};
