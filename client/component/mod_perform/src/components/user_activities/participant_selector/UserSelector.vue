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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @package mod_perform
-->

<template>
  <TagList
    :tags="usersTagList"
    :items="usersDropdownList"
    :filter="fullnameFilter"
    @select="select"
    @remove="remove"
    @filter="updateFilter"
  >
    <template v-slot:item="{ item }">
      <MiniProfileCard
        :no-border="true"
        :no-padding="true"
        :read-only="true"
        :display="item.card_display"
      />
    </template>
  </TagList>
</template>

<script>
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import TagList from 'tui/components/tag/TagList';
import SelectableUsersQuery from 'mod_perform/graphql/selectable_users';
import { debounce } from 'tui/util';

export default {
  components: {
    MiniProfileCard,
    TagList,
  },

  props: {
    subjectInstanceId: {
      required: true,
      type: [Number, String],
    },
    value: {
      type: Array,
      default() {
        return [];
      },
    },
    excludeUsers: {
      type: Array,
      default() {
        return [];
      },
    },
  },

  data() {
    return {
      fullnameFilter: '',
      fullnameFilterDebounced: '',
      users: [],
    };
  },

  apollo: {
    users: {
      query: SelectableUsersQuery,
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
          filters: {
            fullname: this.fullnameFilterDebounced,
            exclude_users: this.excludeUsers,
          },
        };
      },
      update: data => data.mod_perform_selectable_users,
    },
  },

  computed: {
    /**
     * List of users to display in the dropdown.
     * This gets the users who haven't been selected and meet the filter criteria.
     *
     * @returns {[Object]}
     */
    usersDropdownList() {
      return this.users.filter(user => {
        return !this.value.some(selectedUser => selectedUser.id === user.id);
      });
    },

    /**
     * List of users to display as tags.
     *
     * @returns {[Object]}
     */
    usersTagList() {
      return this.value.map(user => {
        return {
          id: user.id,
          text: user.card_display.display_fields[0].value,
        };
      });
    },
  },

  methods: {
    /**
     * Add user to selected values.
     *
     * @param {Object} selectedUser
     */
    select(selectedUser) {
      this.fullnameFilter = '';
      this.fullnameFilterDebounced = '';
      this.$emit('input', this.value.concat([selectedUser]));
    },

    /**
     * Remove user from selected values.
     *
     * @param {Object} selectedUser
     */
    remove(selectedUser) {
      this.$emit(
        'input',
        this.value.filter(user => user.id !== selectedUser.id)
      );
    },

    updateFilter(input) {
      this.fullnameFilter = input;
      this.updateFilterDebounced(input);
    },

    /**
     * Update the full name filter (which re-triggers the query) if the user stopped typing >500 milliseconds ago.
     */
    updateFilterDebounced: debounce(function(input) {
      this.fullnameFilterDebounced = input;
    }, 500),
  },
};
</script>
