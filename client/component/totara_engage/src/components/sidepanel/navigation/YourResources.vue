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
  <div :class="getLinkClasses('yourresources')">
    <a
      class="tui-engageNavigationPanel__link-text"
      :href="$url('/totara/engage/your_resources.php')"
    >
      {{ $str('yourresources', 'totara_engage') }}
    </a>
    <Contribute
      v-if="showContribute"
      :show-text="false"
      :show-icon="true"
      :styleclass="{ circle: true, xsmall: true, primary: false }"
    >
      <template v-slot:modal>
        <ContributeModal
          :exclude-modals="['totara_playlist']"
          :show-notification="showNotification"
        />
      </template>
    </Contribute>
  </div>
</template>

<script>
import Contribute from 'totara_engage/components/contribution/Contribute';
import ContributeModal from 'totara_engage/components/modal/ContributeModal';

// Mixins
import NavigationMixin from 'totara_engage/mixins/navigation_mixin';

export default {
  components: {
    Contribute,
    ContributeModal,
  },

  mixins: [NavigationMixin],

  computed: {
    /**
     * Determine showing notification or not.
     *
     */
    showNotification() {
      return Object.keys(this.values).includes('showNotification')
        ? this.values.showNotification
        : true;
    },
  },
  methods: {
    getLinkClasses(name) {
      let classes = this.getNavigationLinkClass(name, 0);
      classes['tui-navigationYourResources'] = true;
      return classes;
    },

    selectYourResourcesFilter() {
      this.$emit('library-filter-selected', { filterId: 'yourresources' });
    },
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

<style lang="scss">
.tui-navigationYourResources {
  display: flex;
  justify-content: space-between;

  .tui-iconBtn--small {
    width: 2rem;
    height: 2rem;
  }
}
</style>
