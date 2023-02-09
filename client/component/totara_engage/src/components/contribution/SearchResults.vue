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
  <div class="tui-contributionSearchResults">
    <ContributionBaseContent
      :units="units"
      :loading="$apollo.loading"
      :loading-more="loadingMore"
      :cards="contribution.cards"
      :total-cards="contribution.cursor.total"
      :show-heading="gridDirection !== 'vertical'"
      :grid-direction="gridDirection"
      :show-footnotes="includeFootnotes"
      :is-load-more-visible="isLoadMoreVisible"
      :show-empty-content="true"
      @scrolled-to-bottom="scrolledToBottom"
      @load-more="loadMore"
    >
      <template v-slot:heading>
        {{ pageHeading }}
      </template>

      <template v-slot:filters>
        <ContributionFilter
          component="totara_engage"
          area="search"
          :value="filterValue"
          :show-access="true"
          :show-type="true"
          :show-topic="true"
          :show-section="true"
          @access="filterAccess"
          @type="filterType"
          @topic="filterTopic"
          @sort="filterSort"
          @section="filterSection"
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

  data() {
    return {
      search: this.pageProps.search,
    };
  },

  computed: {
    pageHeading() {
      return !this.pageProps.search
        ? this.$str('search', 'totara_engage')
        : this.$str(
            'searchresultsdetail',
            'totara_engage',
            this.pageProps.search
          );
    },
  },

  created() {
    this.filterValue = Object.assign({}, this.filterValue, {
      search: this.pageProps.search,
    });
    this.contributionComponent = 'totara_engage';
    this.contributionArea = 'search';
    this.contributionSource = UrlSourceType.librarySearchResults();
    this.includeFootnotes = true;
    this.footnotes.footnotes_type = 'search';
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "search",
    "searchresultsdetail"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contributionSearchResults {
  .tui-contributionBaseContent__title {
    @include tui-font-heading-page-title;
  }
}
</style>
