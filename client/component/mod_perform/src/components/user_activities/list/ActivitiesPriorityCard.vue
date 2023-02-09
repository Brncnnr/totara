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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <Card :clickable="true" @click="cardClick">
    <article
      class="tui-performUserActivitiesPriorityCard"
      :aria-labelledby="$id('activityPriorityCard')"
    >
      <div class="tui-performUserActivitiesPriorityCard__top">
        <Avatar
          :alt="subjectUser.profileimagealt"
          :aria-hidden="true"
          :src="subjectUser.profileimageurlsmall"
          size="xsmall"
          class="tui-performUserActivitiesPriorityCard__avatar"
        />

        <div class="tui-performUserActivitiesPriorityCard__top-overdue">
          <Lozenge
            v-if="overdue"
            :text="$str('is_overdue', 'mod_perform')"
            type="alert"
          />
        </div>
      </div>

      <div class="tui-performUserActivitiesPriorityCard__subject">
        <div class="tui-performUserActivitiesPriorityCard__subjectName">
          {{ subjectUser.fullname }}
        </div>

        <div class="tui-performUserActivitiesPriorityCard__subjectAssignment">
          {{ jobAssignment }}
        </div>
      </div>

      <h4
        :id="$id('activityPriorityCard')"
        class="tui-performUserActivitiesPriorityCard__activity"
        :title="title"
      >
        {{ title }}
      </h4>

      <div class="tui-performUserActivitiesPriorityCard__due">
        <template v-if="overdue && dueDate">
          {{
            $str('user_activities_unit_overdue', 'mod_perform', {
              number: dueDate.units_to_due_date,
              unit: dueDate.units_to_due_date_type,
            })
          }}
        </template>
        <template v-else-if="dueDate && dueDate.units_to_due_date === 0">
          {{ $str('user_activities_due_today', 'mod_perform') }}
        </template>
        <template v-else-if="dueDate">
          {{
            $str('user_activities_unit_to_due', 'mod_perform', {
              number: dueDate.units_to_due_date,
              unit: dueDate.units_to_due_date_type,
            })
          }}
        </template>
      </div>

      <ActionLink
        class="tui-performUserActivitiesPriorityCard__action"
        :aria-label="accessibleLinkText"
        :href="url"
        :styleclass="{ small: true }"
        :text="linkText"
      />
    </article>
  </Card>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import Avatar from 'tui/components/avatar/Avatar';
import Card from 'tui/components/card/Card';
import Lozenge from 'tui/components/lozenge/Lozenge';

export default {
  components: {
    ActionLink,
    Avatar,
    Card,
    Lozenge,
  },

  props: {
    dueDate: Object,
    jobAssignment: String,
    overdue: Boolean,
    status: {
      required: true,
      type: String,
    },
    subjectUser: {
      required: true,
      type: Object,
    },
    title: {
      required: true,
      type: String,
    },
    url: {
      required: true,
      type: String,
    },
  },

  computed: {
    /**
     * Provide the accessible string for action link
     *
     * @return {String}
     */
    accessibleLinkText() {
      return this.$str(
        this.status === 'NOT_STARTED'
          ? 'user_activities_start_a11y'
          : 'user_activities_resume_a11y',
        'mod_perform',
        this.title
      );
    },

    /**
     * Provide the string for action link
     *
     * @return {String}
     */
    linkText() {
      return this.$str(
        this.status === 'NOT_STARTED'
          ? 'user_activities_start'
          : 'user_activities_resume',
        'mod_perform'
      );
    },
  },

  methods: {
    /**
     * Redirect to the cards activity page
     */
    cardClick() {
      window.location.href = this.url;
    },

    /**
     * Truncate the text to a set character length
     * and replace the last three characters with an ellipsis
     *
     * @param {String} str string to be truncated
     * @param {Number} characters Number of characters to limit the string to
     * @deprecated since 16.0
     */
    truncateString(str, characters) {
      const ellipsis = '...';
      return str.length > characters
        ? str.substr(0, characters - ellipsis.length) + ellipsis
        : str;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "is_overdue",
      "user_activities_due_today",
      "user_activities_resume",
      "user_activities_resume_a11y",
      "user_activities_start",
      "user_activities_start_a11y",
      "user_activities_unit_overdue",
      "user_activities_unit_to_due"
    ]
  }
</lang-strings>

<style lang="scss">
$tui-activity-card-line-height: 1.25em;

.tui-performUserActivitiesPriorityCard {
  width: 100%;
  padding: var(--gap-3);

  & > * + * {
    margin-top: var(--gap-2);
  }

  &__activity {
    position: relative;
    @include tui-font-heading-small-regular();
    height: $tui-activity-card-line-height * 3;
    margin: var(--gap-2) 0 0;
    overflow: hidden;

    &::after {
      position: absolute;
      right: 0;
      bottom: 0;
      left: 0;
      height: $tui-activity-card-line-height;
      background: linear-gradient(
        0deg,
        rgba(255, 255, 255, 1) 0%,
        rgba(255, 255, 255, 0) 100%
      );
      content: ' ';
    }

    @supports (-webkit-line-clamp: 3) and (-webkit-box-orient: vertical) and
      (display: -webkit-box) {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      text-overflow: ellipsis;

      &::after {
        height: 0;
        background: none;
      }
    }

    // Safari in RTL Languages doesn't show ellipsis so fall back to fade
    [dir='rtl'] .safari &::after {
      height: $tui-activity-card-line-height;
      background: linear-gradient(
        0deg,
        rgba(255, 255, 255, 1) 0%,
        rgba(255, 255, 255, 0) 100%
      );
    }
  }

  &__action {
    margin-top: var(--gap-4);
  }

  &__due {
    @include tui-font-body-x-small();
    height: $tui-activity-card-line-height;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__subject {
    margin-top: var(--gap-1);
  }

  &__subjectName {
    @include tui-font-heading-label-small();
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__subjectAssignment {
    @include tui-font-body-x-small();
    height: $tui-activity-card-line-height;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__top {
    display: flex;

    &-overdue {
      margin-left: auto;
    }
  }
}
</style>
