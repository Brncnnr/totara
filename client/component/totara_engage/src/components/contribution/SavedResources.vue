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
  <div class="tui-contributionSavedResources">
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
        {{ $str('savedresources', 'totara_engage') }}
      </template>

      <template v-slot:filters>
        <ContributionFilter
          component="totara_engage"
          area="saved"
          :has-bottom-bar="true"
          :has-top-bar="true"
          :value="filterValue"
          :show-access="false"
          :show-type="true"
          :show-topic="true"
          :show-sort="false"
          @access="filterAccess"
          @type="filterType"
          @topic="filterTopic"
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
    filterChange() {
      const { type, topic } = this.filterValue;
      return type !== null || topic !== null;
    },
  },

  created() {
    this.contributionComponent = 'totara_engage';
    this.contributionArea = 'saved';
    this.contributionSource = UrlSourceType.libraryBookmarked();
  },

  methods: {
    refetch() {
      this.$apollo.queries.contribution.refetch();
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "savedresources"
  ]
}
</lang-strings>

<style lang="scss">
.tui-contributionSavedResources {
  .tui-contributionBaseContent__counter {
    position: relative;
    top: 0;
    padding-bottom: var(--gap-2);
  }
}
</style>
