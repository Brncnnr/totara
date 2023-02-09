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
  <div class="tui-otherUserLibrary">
    <ContributionBaseContent
      :loading="$apollo.loading"
      :loading-more="loadingMore"
      :cards="contribution.cards"
      :show-heading="true"
      grid-direction="horizontal"
      :show-footnotes="false"
      :is-load-more-visible="isLoadMoreVisible"
      :total-cards="contribution.cursor.total"
      :custom-title="countSharedResource"
      :custom-load-more-text="loadMoreText"
      :show-empty-content="filterChange"
      :show-empty-contribution="showEmptyContribution"
      :custom-empty-content="$str('nocontributions', 'totara_engage', name)"
      @scrolled-to-bottom="scrolledToBottom"
      @load-more="loadMore"
    >
      <template v-slot:buttons>
        <ResourceNavigationBar
          class="tui-otherUserLibrary__header"
          :back-button="{
            label: name,
            url: $url('/user/profile.php', { id: userId }),
          }"
        />
      </template>

      <template v-slot:heading>
        {{ $str('usersresources', 'totara_engage', name) }}
      </template>

      <template v-slot:filters>
        <ContributionFilter
          v-if="contributionCount"
          component="totara_engage"
          area="otheruserlib"
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
import ResourceNavigationBar from 'totara_engage/components/header/ResourceNavigationBar';

import LibraryMixin from 'totara_engage/mixins/library_mixin';
import OtherContributionMixin from 'totara_engage/mixins/other_contribution_mixin';

export default {
  components: {
    ContributionBaseContent,
    ContributionFilter,
    ResourceNavigationBar,
  },

  mixins: [OtherContributionMixin, LibraryMixin],

  props: {
    userId: {
      type: Number,
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
  },

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
    showEmptyContribution() {
      return this.contributionCount === 0;
    },
  },

  created() {
    this.contributionComponent = 'totara_engage';
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "nocontributions",
    "usersresources",
    "itemscount",
    "itemscountone"
  ],
  "engage_article": [
    "viewedresources"
  ]
}
</lang-strings>

<style lang="scss">
.tui-otherUserLibrary {
  &__header {
    padding-left: 0;
  }
}
</style>
