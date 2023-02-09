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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performActivitySectionRelationship">
    <div v-if="editable" class="tui-performActivitySectionRelationship__item">
      <div class="tui-performActivitySectionRelationship__item-name">
        {{ participant.core_relationship.name }}
        <DeleteIcon
          :aria-label="
            $str(
              'delete_relationship',
              'mod_perform',
              participant.core_relationship.name
            )
          "
          @click="removeDisplayedParticipant(participant)"
        />
      </div>
      <div
        v-if="!isViewOnlyParticipant"
        class="tui-performActivitySectionRelationship__item-options"
      >
        <Checkbox
          v-model="participant_can_view"
          @change="toggleCanViewOnParticipant"
        >
          {{ $str('activity_participant_can_view', 'mod_perform') }}
        </Checkbox>
      </div>
    </div>
    <p v-else class="tui-performActivitySectionRelationship__item">
      {{ participant.core_relationship.name
      }}{{ participant.can_view && !isViewOnlyParticipant ? '*' : '' }}
    </p>
  </div>
</template>

<script>
import Checkbox from 'tui/components/form/Checkbox';
import DeleteIcon from 'tui/components/buttons/DeleteIcon';

export default {
  components: {
    Checkbox,
    DeleteIcon,
  },
  props: {
    participant: {
      type: Object,
      required: true,
    },
    editable: {
      type: Boolean,
      required: true,
    },
    isViewOnlyParticipant: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      participant_can_view: this.getCanView(),
    };
  },
  methods: {
    /**
     * Gets can_view property of participant.
     *
     * @return {Boolean}
     */
    getCanView() {
      return this.participant.can_view;
    },
    /**
     * Change can_view property on participant.
     * @param {Boolean} checked Value to change can_view to
     */
    toggleCanViewOnParticipant(checked) {
      const participant = Object.assign({}, this.participant, {
        can_view: checked,
      });
      this.$emit('can-view-changed', participant);
    },
    /**
     * Removes displayed participant.
     * @param {Object} participant
     */
    removeDisplayedParticipant(participant) {
      this.$emit('participant-removed', participant);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_participant_can_view",
      "delete_relationship"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performActivitySectionRelationship {
  &__item {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    margin: 0;
  }

  &__item-name {
    display: flex;
    flex-grow: 1;
    justify-content: space-between;
    padding: var(--gap-2);
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: var(--border-radius-normal);
  }

  &__item-options {
    margin-top: var(--gap-2);
  }
}

@media (min-width: $tui-screen-xs) {
  .tui-performActivitySectionRelationship {
    &__item {
      flex-direction: row;
    }

    &__item-name {
      max-width: 20rem;
    }

    &__item-options {
      margin: auto 0;
      padding: var(--gap-2);
    }
  }
}
</style>
