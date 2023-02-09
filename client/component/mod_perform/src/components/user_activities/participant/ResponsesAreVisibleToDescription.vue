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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-responsesAreVisibleToDescription">
    <div
      v-if="isViewOnlyParticipant"
      class="tui-responsesAreVisibleToDescription__viewOnly"
    >
      <Lozenge
        class="tui-responsesAreVisibleToDescription__viewOnly-lozenge"
        :text="$str('response_visibility_view_only_lozenge', 'mod_perform')"
        type="info"
      />
      <p class="tui-responsesAreVisibleToDescription__text">
        {{
          activity.settings.visibility_condition
            .view_only_participant_description
        }}
      </p>
    </div>
    <p v-else class="tui-responsesAreVisibleToDescription__text">
      <span v-html="visibilityString" />
    </p>

    <p
      v-if="
        !isViewOnlyParticipant &&
          activity.settings.visibility_condition.participant_description
      "
      class="tui-responsesAreVisibleToDescription__text"
    >
      {{ activity.settings.visibility_condition.participant_description }}
    </p>
  </div>
</template>

<script>
import {
  RELATIONSHIP_SUBJECT,
  PROGRESS_NOT_APPLICABLE,
} from 'mod_perform/constants';

import Lozenge from 'tui/components/lozenge/Lozenge';

export default {
  components: {
    Lozenge,
  },
  props: {
    currentUserIsSubject: {
      type: Boolean,
      required: true,
    },
    visibleToRelationships: {
      type: Array,
      required: true,
    },
    activity: {
      type: Object,
      required: true,
    },
    participantSection: {
      type: Object,
      required: true,
      validator(value) {
        return value === null || value.progress_status;
      },
    },
  },
  computed: {
    notVisibleToAnyone() {
      return (
        this.visibleToRelationships.length === 0 ||
        (this.currentUserIsSubject && this.visibleToSubjectOnly)
      );
    },
    visibleToSubjectOnly() {
      return this.visibleToRelationships.length === 1 && this.visibleToSubject;
    },
    relationshipDescriptions() {
      if (this.currentUserIsSubject) {
        return this.descriptionsForSubject;
      }

      return this.descriptionsForNonSubject;
    },
    descriptionsForSubject() {
      return this.nonSubjectRelationships.map(relationship =>
        this.$str(
          'response_visibility_your_relationship',
          'mod_perform',
          relationship.name_plural
        )
      );
    },
    descriptionsForNonSubject() {
      const descriptions = [];

      if (this.visibleToSubject) {
        descriptions.push(
          this.$str('response_visibility_the_employee', 'mod_perform')
        );
      }

      const otherDescriptions = this.nonSubjectRelationships.map(relationship =>
        this.$str(
          'response_visibility_the_employees_relationship',
          'mod_perform',
          relationship.name_plural
        )
      );

      Array.prototype.push.apply(descriptions, otherDescriptions);

      return descriptions;
    },
    visibleToSubject() {
      return this.visibleToRelationships.some(
        relationship => relationship.idnumber === RELATIONSHIP_SUBJECT
      );
    },
    nonSubjectRelationships() {
      return this.visibleToRelationships.filter(
        relationship => relationship.idnumber !== RELATIONSHIP_SUBJECT
      );
    },
    anonymousResponses() {
      return this.activity.anonymous_responses;
    },
    isViewOnlyParticipant() {
      return (
        this.participantSection !== null &&
        this.participantSection.progress_status === PROGRESS_NOT_APPLICABLE
      );
    },

    /**
     * Build the visibility string depending on who these responses are visible to
     */
    visibilityString() {
      if (this.notVisibleToAnyone) {
        return this.$str(
          'response_visibility_to_only_granted_access',
          'mod_perform'
        );
      }

      let relationshipListString = '';

      // Build the separated list of relationship descriptions
      this.relationshipDescriptions.forEach((description, index) => {
        relationshipListString += description;

        // Add the separator after all but the last item
        if (index < this.relationshipDescriptions.length - 1) {
          relationshipListString += this.$str(
            'response_visibility_your_relationship_separator',
            'mod_perform'
          );
        }
      });

      if (this.anonymousResponses) {
        return this.$str(
          'response_visibility_label_anonymous',
          'mod_perform',
          relationshipListString
        );
      } else {
        return this.$str(
          'response_visibility_label',
          'mod_perform',
          relationshipListString
        );
      }
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "response_visibility_label",
      "response_visibility_view_only_lozenge",
      "response_visibility_label_anonymous",
      "response_visibility_to_only_granted_access",
      "response_visibility_the_employee",
      "response_visibility_the_employees_relationship",
      "response_visibility_your_relationship",
      "response_visibility_your_relationship_separator"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-responsesAreVisibleToDescription {
  & > * + * {
    margin-top: var(--gap-1);
  }

  &__viewOnly {
    display: flex;
    flex-direction: column;
    align-items: flex-start;

    & > * + * {
      margin-top: var(--gap-2);
    }

    &-lozenge {
      flex-shrink: 0;
    }
  }

  &__text {
    margin-bottom: 0;
  }

  @media (min-width: $tui-screen-xs) {
    &__viewOnly {
      display: flex;
      flex-direction: row;
      align-items: center;

      & > * + * {
        margin-top: 0;
        margin-left: var(--gap-2);
      }
    }
  }
}
</style>
