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

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module mod_perform
-->

<template>
  <Popover :triggers="['click']">
    <h2 class="sr-only">
      {{ $str('activity_participants_select_heading', 'mod_perform') }}
    </h2>
    <label :id="$id('participants-select-heading')">
      {{ $str('activity_participants_select_heading', 'mod_perform') }}
    </label>
    <Checkbox
      v-for="relationship in relationships"
      :key="relationship.id"
      :value="relationship.id"
      :name="relationship.name"
      :checked="isCheckedParticipant(relationship)"
      :disabled="isActiveParticipant(relationship)"
      @change="isChecked => handleChange(isChecked, relationship)"
    >
      <template>
        {{ relationship.name }}
      </template>
    </Checkbox>
    <template v-slot:buttons="{ close }">
      <Button
        :styleclass="{ small: true, primary: true }"
        :text="$str('activity_participants_select_done', 'mod_perform')"
        @click="updateParticipants(close)"
      />
      <Button
        :styleclass="{ small: true, primary: false }"
        :text="$str('cancel', 'core')"
        @click="close"
      />
    </template>
    <template v-slot:trigger>
      <ButtonIcon
        :styleclass="{ small: true }"
        :aria-label="addButtonAriaLabel"
        :disabled="hasAddedAllParticipants"
        @click="resetCheckedParticipants"
      >
        <AddIcon size="100" />
      </ButtonIcon>
    </template>
  </Popover>
</template>
<script>
import AddIcon from 'tui/components/icons/Add';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Checkbox from 'tui/components/form/Checkbox';
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    AddIcon,
    Button,
    ButtonIcon,
    Checkbox,
    Popover,
  },
  props: {
    activeParticipants: {
      required: true,
      type: Array,
      default() {
        return [];
      },
    },
    relationships: {
      type: Array,
      required: true,
    },
    isViewOnlyParticipants: Boolean,
  },
  data() {
    return {
      checkedParticipants: [],
    };
  },
  computed: {
    /**
     * Get the add button aria-label.
     *
     * @return {string}
     */
    addButtonAriaLabel() {
      if (this.isViewOnlyParticipants) {
        return this.$str('activity_participants_view_only_add', 'mod_perform');
      }

      return this.$str('activity_participants_add', 'mod_perform');
    },

    /**
     * Checks if all participants have been added.
     *
     * @return {Boolean}
     */
    hasAddedAllParticipants() {
      return this.relationships.length === this.activeParticipants.length;
    },
  },

  methods: {
    /**
     * Close the popover and notify the parent that a selection has been made or updated.
     * @param {function} close
     */
    updateParticipants(close) {
      close();
      this.$emit('update-participants', this.checkedParticipants);
      this.checkedParticipants = [];
    },

    /**
     * Checks if participant is active.
     * @param {Object} participant
     */
    isCheckedParticipant(participant) {
      return this.checkedParticipants.some(
        checkedParticipant => checkedParticipant.id == participant.id
      );
    },

    /**
     * Uncheck all participants. This should be happening on every display.
     * The check participants state should only be representative of the current
     * "selection session", not the overarching state of selections.
     *
     * Already selected participants should instead be shown as disabled.
     */
    resetCheckedParticipants() {
      return (this.checkedParticipants = []);
    },

    /**
     * Checks if participant is active.
     * @param {Object} participant
     */
    isActiveParticipant(participant) {
      return this.activeParticipants
        .map(participant => participant.core_relationship.id)
        .includes(participant.id);
    },

    /**
     * Handles change in selected participants.
     * @param {Boolean} isChecked
     * @param {Object} participant
     */
    handleChange(isChecked, participant) {
      if (isChecked) {
        this.checkedParticipants.push(participant);
      } else {
        this.checkedParticipants = this.checkedParticipants.filter(
          value => value !== participant
        );
      }
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel"
    ],
    "mod_perform": [
      "activity_participants_select_done",
      "activity_participants_select_heading",
      "activity_participants_add",
      "activity_participants_view_only_add"
    ]    
  }
</lang-strings>
