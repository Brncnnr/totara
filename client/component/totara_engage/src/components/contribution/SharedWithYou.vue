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

<template>
  <div class="tui-contributionSharedWithYou">
    <ContributionBaseContent
      :units="units"
      :loading="$apollo.loading"
      :loading-more="loadingMore"
      :cards="contribution.cards"
      :show-heading="gridDirection !== 'vertical'"
      :grid-direction="gridDirection"
      :show-footnotes="includeFootnotes"
      :is-load-more-visible="isLoadMoreVisible"
      :total-cards="contribution.cursor.total"
      :custom-title="countSharedResource"
      :custom-load-more-text="loadMoreText"
      :show-empty-content="filterChange"
      @scrolled-to-bottom="scrolledToBottom"
      @load-more="loadMore"
    >
      <template v-slot:heading>
        {{ $str('sharedwithyou', 'totara_engage') }}
      </template>

      <template v-slot:filters>
        <ContributionFilter
          component="totara_engage"
          area="shared"
          :has-bottom-bar="true"
          :has-top-bar="true"
          :value="filterValue"
          :show-access="false"
          :show-type="true"
          :show-topic="true"
          @access="filterAccess"
          @type="filterType"
          @topic="filterTopic"
          @sort="filterSort"
        />
      </template>
    </ContributionBaseContent>
  </div>
</template>

<script>
import ContributionBaseContent from 'totara_engage/components/contribution/BaseContent';
import ContributionFilter from 'totara_engage/components/contribution/Filter';
import { UrlSourceType } from 'totara_engage/index';

// Mixins
import ContributionMixin from 'totara_engage/mixins/contribution_mixin';
import LibraryMixin from 'totara_engage/mixins/library_mixin';

export default {
  components: {
    ContributionBaseContent,
    ContributionFilter,
  },

  mixins: [ContributionMixin, LibraryMixin],

  computed: {
    loadMoreText() {
      return `${this.$str(
        'viewedresources',
        'engage_article',
        this.contribution.cards.length
      )} ${this.$str(
        'itemscount',
        'totara_engage',
        this.contribution.cursor.total
      )}`;
    },
    countSharedResource() {
      if (this.contribution.cursor.total === 1)
        return this.$str(
          'itemscountone',
          'totara_engage',
          this.contribution.cursor.total
        );
      return this.$str(
        'itemscount',
        'totara_engage',
        this.contribution.cursor.total
      );
    },
    filterChange() {
      const { type, topic, sort } = this.filterValue;
      return type !== null || topic !== null || sort !== 5;
    },
  },

  created() {
    this.filterValue = Object.assign({}, this.filterValue, {
      sort: 5,
    });
    this.contributionComponent = 'totara_engage';
    this.contributionArea = 'shared';
    this.includeFootnotes = true;
    this.footnotes.footnotes_type = 'shared';
    this.contributionSource = UrlSourceType.libraryShared();
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "sharedwithyou",
    "itemscount",
    "itemscountone"
  ],
  "engage_article": [
    "viewedresources"
  ]
}
</lang-strings>
