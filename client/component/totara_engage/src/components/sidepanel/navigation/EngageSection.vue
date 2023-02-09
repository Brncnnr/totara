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
  <section>
    <SearchLibrary
      :selected-id="selectedId"
      :values="values"
      :redirection="redirection"
    />

    <h3 class="sr-only">{{ $str('yourresources', 'totara_engage') }}</h3>

    <YourResources
      :selected-id="selectedId"
      :values="values"
      :show-contribute="showContribute"
    />

    <!-- SharedWithYou -->
    <div :class="getNavigationLinkClass('sharedwithyou', 1)">
      <a
        class="tui-engageNavigationPanel__link-text"
        :href="$url('/totara/engage/shared_with_you.php')"
      >
        {{ $str('sharedwithyou', 'totara_engage') }}
      </a>
    </div>
    <!-- /SharedWithYou -->

    <!-- SavedResources -->
    <div :class="getNavigationLinkClass('savedresources', 2)">
      <a
        class="tui-engageNavigationPanel__link-text"
        :href="$url('/totara/engage/saved_resources.php')"
      >
        {{ $str('savedresources', 'totara_engage') }}
      </a>
    </div>
    <!-- /SavedResources -->
  </section>
</template>

<script>
import SearchLibrary from 'totara_engage/components/sidepanel/navigation/SearchLibrary';
import YourResources from 'totara_engage/components/sidepanel/navigation/YourResources';

// Mixins
import NavigationMixin from 'totara_engage/mixins/navigation_mixin';

export default {
  components: {
    SearchLibrary,
    YourResources,
  },

  mixins: [NavigationMixin],

  props: {
    redirection: Boolean,
  },

  methods: {
    selectSharedWithYouFilter() {
      this.$emit('library-filter-selected', { filterId: 'sharedwithyou' });
    },
    selectSavedResourcesFilter() {
      this.$emit('library-filter-selected', { filterId: 'savedresources' });
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "sharedwithyou",
    "savedresources",
    "yourresources"
  ]
}
</lang-strings>
