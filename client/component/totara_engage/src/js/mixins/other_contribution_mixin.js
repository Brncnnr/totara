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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module totara_engage
 */

import { config } from 'tui/config';
import userContributionCards from 'totara_engage/graphql/user_contribution_cards';

export default {
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
      contribution: {
        cursor: {
          total: 0,
          next: null,
        },
        cards: [],
      },
      contributionCount: 0,
      loadingMore: false,
    };
  },

  apollo: {
    otherUserContributions: {
      query: userContributionCards,
      fetchPolicy: 'network-only',
      loadingKey: 'loading',
      variables() {
        return Object.assign({}, this.filterValue, {
          component: this.contributionComponent,
          area: 'otheruserlib',
          include_footnotes: false,
          source: this.contributionSource,
          user_id: this.userId,
          theme: config.theme.name,
        });
      },

      update({ contribution, count }) {
        return {
          contribution,
          count,
        };
      },

      result({ data: { contribution, count } }) {
        // Handle the reactive property count
        this.contributionCount = count;
        // End handling the reactive property count

        // Handle the reactive property contribution
        this.contribution = contribution;

        // Correct the TYPE filter items when getting backend error result
        if (this.filterValue.type) {
          const { cards, cursor } = contribution;
          const filteredCards = cards.filter(
            card => card.component === this.filterValue.type
          );

          this.contribution = {
            cursor,
            cards: filteredCards,
          };
        }

        const checkingErrorTotal = obj =>
          obj.cursor.next === null && obj.cards.length < obj.cursor.total;

        // Correct the total items when getting backend error result
        if (checkingErrorTotal(contribution)) {
          this.contribution = {
            cursor: {
              total: contribution.cards.length,
              next: null,
            },
            cards: contribution.cards,
          };
        }
        // End handling the reactive property contribution
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
      this.$apollo.queries.otherUserContributions.fetchMore({
        variables: Object.assign({}, this.filterValue, {
          cursor: this.contribution.cursor.next,
        }),

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
