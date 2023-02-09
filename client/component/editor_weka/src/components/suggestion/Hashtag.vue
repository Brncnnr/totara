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
  @module editor_weka
-->

<template>
  <div class="tui-wekaHashtagSuggestions" :style="positionStyle">
    <Dropdown
      :separator="false"
      :open="showSuggestions"
      :inline-menu="true"
      @dismiss="dismiss"
    >
      <template v-slot:trigger>
        <span class="sr-only">
          {{ $str('matching_hashtags', 'editor_weka') }}:
        </span>
      </template>

      <template v-if="$apollo.loading">
        <DropdownItem :disabled="true">
          {{ $str('loadinghelp', 'core') }}
        </DropdownItem>
      </template>

      <DropdownItem
        v-for="(hashtag, index) in hashtags"
        :key="index"
        @click="pickTag(hashtag)"
      >
        {{ hashtag.tag }}
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import findHashtags from 'core/graphql/find_hashtags';

export default {
  components: {
    Dropdown,
    DropdownItem,
  },

  props: {
    contextId: {
      type: [Number, String],
    },

    component: {
      type: String,
    },

    area: {
      type: String,
    },

    location: {
      required: true,
      type: Object,
    },

    pattern: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      hashtags: [],
    };
  },

  apollo: {
    hashtags: {
      query: findHashtags,
      fetchPolicy: 'network-only',
      variables() {
        return {
          pattern: this.pattern,
          contextid: this.contextId,
          component: this.component,
          area: this.area,
        };
      },
    },
  },

  computed: {
    showSuggestions() {
      return this.$apollo.loading || this.hashtags.length > 0;
    },

    positionStyle() {
      return {
        left: `${this.location.x}px`,
        top: `${this.location.y}px`,
      };
    },
  },

  watch: {
    showSuggestions(active) {
      if (!active) {
        this.$emit('dismiss');
      }
    },
  },

  mounted() {
    let element = this.$refs.customTrigger;

    if (!element) {
      return;
    }

    // Triggering the element click.
    element.click();
  },

  methods: {
    /**
     * @param {Number} id
     * @param {String} tag
     */
    pickTag({ id, tag }) {
      this.$emit('item-selected', { id, text: tag });
    },

    dismiss() {
      this.open = false;
      this.$emit('dismiss');
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "matching_hashtags"
    ],
    "core": [
      "loadinghelp"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaHashtagSuggestions {
  position: absolute;
  z-index: var(--zindex-popover);
}
</style>
