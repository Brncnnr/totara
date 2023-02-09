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
  <div class="tui-contributionYourResources">
    <ContributionBaseContent
      :units="units"
      :loading="$apollo.loading"
      :loading-more="loadingMore"
      :cards="contribution.cards"
      :total-cards="contribution.cursor.total"
      :show-heading="gridDirection !== 'vertical'"
      :grid-direction="gridDirection"
      :is-load-more-visible="isLoadMoreVisible"
      :show-empty-content="filterChange"
      @scrolled-to-bottom="scrolledToBottom"
      @load-more="loadMore"
    >
      <template v-slot:heading>
        {{ $str('yourresources', 'totara_engage') }}
      </template>

      <template v-slot:filters>
        <ContributionFilter
          component="totara_engage"
          area="owned"
          :has-bottom-bar="true"
          :has-top-bar="true"
          :value="filterValue"
          :show-access="true"
          :show-type="true"
          :show-topic="true"
          :initial-filters="pageProps.filters"
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
  props: {
    pageProps: {
      type: Object,
      validator: function(value) {
        return value.filters instanceof Object;
      },
    },
  },

  computed: {
    filterChange() {
      const { access, type, topic, sort } = this.filterValue;
      return access !== null || type !== null || topic !== null || sort !== 1;
    },
  },

  created() {
    this.contributionComponent = 'totara_engage';
    this.contributionArea = 'owned';
    this.contributionSource = UrlSourceType.libraryYourResources();
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "yourresources"
  ]
}
</lang-strings>
