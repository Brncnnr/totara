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

  @author Murali Nair <murali.nair@totaralearning.com>
  @package mod_perform
-->

<template>
  <div class="tui-performManualParticipantSelectionBanner">
    <ActionCard :has-shadow="true" :shaded="true">
      <template v-slot:card-body>
        <div class="tui-performManualParticipantSelectionBanner__body">
          <Users
            size="400"
            class="tui-performManualParticipantSelectionBanner__body-icon"
          />

          <div class="tui-performManualParticipantSelectionBanner__text">
            <h4 class="tui-performManualParticipantSelectionBanner__text-title">
              {{ title }}
            </h4>
            <div>
              {{
                $str(
                  'user_activities_require_manual_participant_selection_body',
                  'mod_perform'
                )
              }}
            </div>
          </div>
        </div>
      </template>
      <template v-slot:card-action>
        <ActionLink
          class="tui-performManualParticipantSelectionBanner__button"
          :href="$url('/mod/perform/activity/select-participants.php')"
          :styleclass="{ primary: true }"
          :text="
            $str(
              'user_activities_require_manual_participant_selection_link_text',
              'mod_perform'
            )
          "
        />
      </template>
    </ActionCard>
  </div>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import ActionLink from 'tui/components/links/ActionLink';
import Users from 'tui/components/icons/Users';

export default {
  components: {
    ActionCard,
    ActionLink,
    Users,
  },

  props: {
    itemCount: {
      required: true,
      type: Number,
    },
  },

  computed: {
    /**
     * Title string for the banner
     *
     * @return {String}
     */
    title() {
      return this.$str(
        this.itemCount > 1
          ? 'user_activities_require_manual_participant_selection_title_plural'
          : 'user_activities_require_manual_participant_selection_title',
        'mod_perform',
        this.itemCount
      );
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "user_activities_require_manual_participant_selection_body",
      "user_activities_require_manual_participant_selection_link_text",
      "user_activities_require_manual_participant_selection_title",
      "user_activities_require_manual_participant_selection_title_plural"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManualParticipantSelectionBanner {
  &__body {
    display: flex;

    & > * + * {
      margin-left: var(--gap-4);
    }

    &-icon {
      flex-shrink: 0;
    }
  }

  &__text {
    & > * + * {
      margin-top: var(--gap-2);
    }

    &-title {
      margin: 0;
      @include tui-font-heading-small();
    }
  }

  &__button {
    margin-left: var(--gap-10);
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performManualParticipantSelectionBanner {
    &__button {
      margin-left: 0;
    }
  }
}
</style>
