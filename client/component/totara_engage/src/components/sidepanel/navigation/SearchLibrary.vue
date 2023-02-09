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
  <SearchBox
    class="tui-engageNavigationPanelSearchLibrary"
    :value="searchValue"
    :drop-label="true"
    :disabled="false"
    :aria-label="$str('searchlibrary', 'totara_engage')"
    :placeholder="$str('searchlibrary', 'totara_engage')"
    :enable-clear-icon="true"
    @clear="clearSearchInput"
    @input="searchInput"
    @submit="submit"
  />
</template>

<script>
import SearchBox from 'tui/components/form/SearchBox';

// Mixins
import NavigationMixin from 'totara_engage/mixins/navigation_mixin';

export default {
  components: {
    SearchBox,
  },

  mixins: [NavigationMixin],

  props: {
    redirection: Boolean,
  },

  data() {
    return {
      searchValue: this.values.search || '',
    };
  },

  methods: {
    /**
     *
     * @param {String} value
     */
    searchInput(value) {
      this.searchValue = value;
    },

    /**
     * Submit search
     */
    submit() {
      window.location.href = this.$url('/totara/engage/search_results.php', {
        search: this.searchValue,
      });
    },

    clearSearchInput() {
      if (this.redirection) {
        window.location.href = this.$url('/totara/engage/search_results.php', {
          search: '',
        });
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "searchlibrary",
    "searchresultsdetail"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageNavigationPanelSearchLibrary {
  margin: var(--gap-4);
  margin-right: var(--gap-1);
  margin-bottom: var(--gap-6);
}
</style>
