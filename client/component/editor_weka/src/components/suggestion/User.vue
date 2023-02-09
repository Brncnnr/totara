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
  @module editor_weka
-->

<template>
  <div
    v-if="mentionOpen"
    class="tui-wekaUserSuggestions"
    :style="positionStyle"
  >
    <Dropdown
      :separator="true"
      :open="mentionOpen"
      :inline-menu="true"
      @dismiss="$emit('dismiss')"
    >
      <span class="sr-only">
        {{ $str('matching_users', 'editor_weka') }}:
      </span>

      <template v-if="$apollo.loading">
        <DropdownItem :disabled="true">
          <Loader :loading="true" />
        </DropdownItem>
      </template>

      <DropdownItem
        v-for="(user, index) in users"
        :key="index"
        :no-padding="true"
        @click="pickUser(user)"
      >
        <MiniProfileCard :no-border="true" :display="user.card_display" />
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import findUsers from 'editor_weka/graphql/find_users_by_pattern';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    Dropdown,
    DropdownItem,
    MiniProfileCard,
    Loader,
  },

  props: {
    contextId: [Number, String],
    component: String,
    area: String,
    instanceId: [Number, String],

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
    users: {
      query: findUsers,
      fetchPolicy: 'network-only',
      variables() {
        return {
          pattern: this.pattern,
          contextid: this.contextId,
          component: this.component,
          area: this.area,
          instance_id: this.instanceId,
        };
      },
    },
  },

  data() {
    return {
      users: [],
    };
  },

  computed: {
    positionStyle() {
      return {
        left: `${this.location.x}px`,
        top: `${this.location.y}px`,
      };
    },

    /**
     * Whether this has options or is loading options for the mention
     */
    mentionOpen() {
      return this.$apollo.loading || this.users.length > 0;
    },
  },

  methods: {
    /**
     *
     * @param {Number} id
     * @param {String} fullname
     */
    pickUser({ id, fullname }) {
      this.$emit('item-selected', { id, text: fullname });
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "matching_users"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaUserSuggestions {
  position: absolute;
  z-index: var(--zindex-popover);
  width: 32.6rem;
}
</style>
