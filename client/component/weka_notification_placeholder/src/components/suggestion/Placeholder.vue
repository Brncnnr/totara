<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module weka_notification_placeholder
-->

<template>
  <div class="tui-wekaPlaceholderSuggestion" :style="positionStyle">
    <Dropdown
      :separator="true"
      :open="showSuggestions"
      :inline-menu="true"
      @dismiss="$emit('dismiss')"
    >
      <span class="sr-only">
        {{ $str('matching_placeholders', 'editor_weka') }}:
      </span>

      <template v-if="$apollo.loading">
        <DropdownItem :disabled="true">
          <Loader :loading="true" />
        </DropdownItem>
      </template>

      <DropdownItem
        v-for="(placeholder, index) in placeholders"
        :key="index"
        :no-padding="true"
        @click="pickPlaceholder(placeholder)"
      >
        <span class="tui-wekaPlaceholderSuggestion__label">
          {{ placeholder.label }}
        </span>
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import Loader from 'tui/components/loading/Loader';

// GraphQL queries
import findPlaceholders from 'weka_notification_placeholder/graphql/placeholders';

export default {
  components: {
    Dropdown,
    DropdownItem,
    Loader,
  },

  props: {
    contextId: {
      type: [Number, String],
      required: true,
    },
    resolverClassName: {
      type: String,
      required: true,
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

  apollo: {
    placeholders: {
      query: findPlaceholders,
      fetchPolicy: 'network-only',
      variables() {
        return {
          pattern: this.pattern,
          context_id: this.contextId,
          resolver_class_name: this.resolverClassName,
        };
      },
    },
  },

  data() {
    return {
      placeholders: [],
    };
  },

  computed: {
    showSuggestions() {
      return this.$apollo.loading || this.placeholders.length > 0;
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

  methods: {
    /**
     *
     * @param {Number} key
     * @param {String} label
     */
    pickPlaceholder({ key, label }) {
      this.$emit('item-selected', { id: key, text: label });
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "matching_placeholders"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaPlaceholderSuggestion {
  position: absolute;
  z-index: var(--zindex-popover);
  width: 32.6rem;

  &__label {
    padding: var(--gap-1);
  }
}
</style>
