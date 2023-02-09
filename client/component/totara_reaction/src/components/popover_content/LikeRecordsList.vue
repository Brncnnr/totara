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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_reaction
-->
<template>
  <div class="tui-likeRecordsList">
    <Loading v-if="$apollo.loading" size="200" />
    <template v-else>
      <p v-if="0 === like.count">
        {{ $str('nolikes', 'totara_reaction') }}
      </p>

      <ul v-else class="tui-likeRecordsList__list">
        <li v-for="({ user: { fullname } }, index) in reactions" :key="index">
          {{ fullname }}
        </li>
      </ul>

      <p v-if="like.count > 10">
        {{ $str('numberofmore', 'totara_reaction', like.count - 10) }}
      </p>
    </template>
  </div>
</template>

<script>
import Loading from 'tui/components/icons/Loading';

// GraphQL queries
import getLikes from 'totara_reaction/graphql/get_likes';

export default {
  components: {
    Loading,
  },

  props: {
    component: {
      type: String,
      required: true,
    },

    area: {
      type: String,
      required: true,
    },

    instanceId: {
      type: [String, Number],
      required: true,
    },

    /**
     * A prop to tell apollo whether to load or the records or not.
     * This prop is being used in skip function, which it will only affect once.
     */
    skipLoadingRecords: Boolean,
  },

  apollo: {
    like: {
      query: getLikes,
      skip() {
        return this.skipLoadingRecords;
      },
      variables() {
        return {
          component: this.component,
          area: this.area,
          instanceid: this.instanceId,
        };
      },

      /**
       *
       * @param {Number} count
       * @param {Array} reactions
       * @return {{count, reactions}}
       */
      update({ count, reactions }) {
        return {
          count: count,
          reactions: reactions,
        };
      },
    },
  },

  data() {
    return {
      page: 1,
      like: {
        count: 0,
        reactions: [],
      },
    };
  },

  computed: {
    /**
     * Only fetching the first 10 of the items.
     * @return {Array}
     */
    reactions() {
      return Array.prototype.slice.call(this.like.reactions, 0, 9);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_reaction": [
      "nolikes",
      "numberofmore"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-likeRecordsList {
  @include tui-wordbreak--hyphens;
  &__list {
    margin: 0;
    list-style-type: none;
  }
}
</style>
