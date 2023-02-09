<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

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
  <ModalPresenter :open="open" @request-close="closeModal">
    <Modal :aria-labelledby="$id('title')" size="large">
      <ModalContent
        v-if="activity.subject"
        :close-button="true"
        :title="activity.subject.activity.name"
        :title-id="$id('title')"
        :title-visible="false"
      >
        <div class="tui-performUserActivityListSectionsModal">
          <div class="tui-performUserActivityListSectionsModal__heading">
            <Avatar
              :alt="activity.subject.subject_user.profileimagealt"
              :aria-hidden="true"
              class="tui-performUserActivityListSectionsModal__left"
              :src="activity.subject.subject_user.profileimageurlsmall"
              size="xsmall"
            />

            <div>
              <!-- Heading top bar -->
              <div class="tui-performUserActivityListSectionsModal__overview">
                <!-- Subject -->
                <div
                  class="tui-performUserActivityListSectionsModal__overview-subject"
                >
                  {{ activity.subject.subject_user.fullname }}
                </div>

                <!-- Activity type -->
                <div
                  class="tui-performUserActivityListSectionsModal__overview-type"
                >
                  <span class="sr-only">
                    {{ $str('a11y_activity_type_label', 'mod_perform') }}
                  </span>
                  {{ activity.subject.activity.type.display_name }}
                </div>

                <!-- Creation date -->
                <div
                  v-if="activity.subject.created_at"
                  class="tui-performUserActivityListSectionsModal__overview-created"
                >
                  <span class="sr-only">
                    {{ $str('a11y_activity_created_at_label', 'mod_perform') }}
                  </span>
                  {{ activity.subject.created_at }}
                </div>
              </div>

              <!-- Activity name -->
              <h4 class="tui-performUserActivityListSectionsModal__title">
                <span>{{ activity.subject.activity.name }}</span>

                <span
                  v-if="activityOverallAvailabilityClosed"
                  class="tui-performUserActivityListSectionsModal__title-closed"
                >
                  <Lozenge
                    :text="$str('activity_availability_closed', 'mod_perform')"
                    type="neutral"
                  />
                </span>
              </h4>

              <div class="tui-performUserActivityListSectionsModal__details">
                <!-- Overall progress -->
                <div
                  class="tui-performUserActivityListSectionsModal__details-overallProgress"
                >
                  <span
                    :class="{
                      'tui-performUserActivityListSectionsModal__details-overallProgress--overdue': activityOverdue,
                    }"
                  >
                    {{ activityOverallProgress }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="tui-performUserActivityListSectionsModal__sections">
            <SectionsList
              :about-role="aboutRole"
              :activity-id="activity.subject.activity.id"
              :anonymous-responses="
                activity.subject.activity.anonymous_responses
              "
              :is-multi-section-active="
                activity.subject.activity.settings.multisection
              "
              :subject-sections="activity.sections"
              :view-url="viewUrl"
              @single-section-view-only="flagActivitySingleSectionViewOnly"
            />
          </div>
        </div>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Lozenge from 'tui/components/lozenge/Lozenge';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import SectionsList from 'mod_perform/components/user_activities/list/Sections';

// Util
import { INSTANCE_AVAILABILITY_STATUS_CLOSED } from 'mod_perform/constants';

export default {
  components: {
    Avatar,
    Lozenge,
    Modal,
    ModalContent,
    ModalPresenter,
    SectionsList,
  },

  props: {
    aboutRole: {
      required: true,
      type: Number,
    },
    // Activity data
    activity: {
      required: true,
      type: Object,
    },
    // If the modal is displayed
    open: {
      type: Boolean,
    },
    viewUrl: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      singleSectionViewOnlyActivities: [],
    };
  },

  computed: {
    /**
     * Is the availability of all sections closed
     *
     * @return {string}
     */
    activityOverallAvailabilityClosed() {
      return (
        this.activity.subject.availability_status ===
        INSTANCE_AVAILABILITY_STATUS_CLOSED
      );
    },

    /**
     * Get the overall progress of activity string
     *
     * @return {string}
     */
    activityOverallProgress() {
      if (this.activityOverdue) {
        return this.$str(
          'activity_overall_progress_status_overdue',
          'mod_perform'
        );
      }

      const status = this.activity.subject.progress_status;

      switch (status) {
        case 'NOT_STARTED':
          return this.$str(
            'activity_overall_progress_status_not_started',
            'mod_perform'
          );
        case 'IN_PROGRESS':
          return this.$str(
            'activity_overall_progress_status_in_progress',
            'mod_perform'
          );
        case 'COMPLETE':
          return this.$str(
            'activity_overall_progress_status_complete',
            'mod_perform'
          );
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'activity_overall_progress_status_not_applicable',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'activity_overall_progress_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Is the overall activity progress overdue
     *
     * @return {boolean}
     */
    activityOverdue() {
      return (
        this.activity.subject.due_on && this.activity.subject.due_on.is_overdue
      );
    },
  },

  methods: {
    closeModal() {
      this.$emit('request-close');
    },

    /**
     * Add to the list of activities that only have one section where current user has view-only access.
     *
     * @param {Number} activityId
     */
    flagActivitySingleSectionViewOnly(activityId) {
      this.singleSectionViewOnlyActivities.push(activityId);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "a11y_activity_created_at_label",
      "a11y_activity_type_label",
      "activity_availability_closed",
      "activity_overall_progress_status_complete",
      "activity_overall_progress_status_in_progress",
      "activity_overall_progress_status_not_applicable",
      "activity_overall_progress_status_not_started",
      "activity_overall_progress_status_not_submitted",
      "activity_overall_progress_status_overdue"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivityListSectionsModal {
  display: flex;
  flex-direction: column;

  & > * + * {
    margin-top: var(--gap-4);
  }

  &__heading {
    display: flex;
    padding-bottom: var(--gap-4);
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);

    & > * + * {
      margin-left: var(--gap-2);
    }
  }

  &__details {
    display: flex;
    @include tui-font-body-small();
    @include tui-separator-pipe();
    margin-top: var(--gap-2);
    color: var(--color-neutral-6);

    &-overallProgress {
      &--overdue {
        color: var(--color-prompt-alert);
      }
    }
  }

  &__overview {
    display: flex;
    flex-wrap: wrap;
    @include tui-font-body-small();
    color: var(--color-neutral-6);

    & > * {
      margin-right: var(--gap-2);
    }

    &-subject {
      color: var(--color-neutral-7);
      font-weight: bold;
    }
  }

  &__sections {
    overflow-y: auto;
  }

  &__title {
    margin: var(--gap-1) 0 0;
    @include tui-font-heading-x-small();

    &-closed {
      display: inline-flex;
      vertical-align: middle;
    }

    & > * {
      margin-right: var(--gap-1);
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performUserActivityListSectionsModal {
    max-height: 80vh;

    &__title {
      @include tui-font-heading-small();
    }
  }
}
</style>
