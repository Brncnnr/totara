<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <Loader :loading="$apollo.loading">
    <InstanceInfoCard
      :title="$str('subject_instance_card_title', 'mod_perform')"
      :info-lines="infoLines"
      :show-all-link="showAllLink"
    />
  </Loader>
</template>

<script>
import subjectInstanceQuery from 'mod_perform/graphql/subject_instance_for_participant';
import InstanceInfoCard from 'mod_perform/components/manage_activity/participation/InstanceInfoCard';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    InstanceInfoCard,
    Loader,
  },
  props: {
    subjectInstanceId: {
      type: Number,
      required: true,
    },
    showAllLink: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      subjectInstance: null,
    };
  },
  computed: {
    infoLines() {
      if (this.subjectInstance === null) {
        return [];
      }

      let job_assignment_text = '';
      if (this.subjectInstance.job_assignment) {
        job_assignment_text = this.subjectInstance.job_assignment.fullname;
      }

      return [
        {
          label: this.$str(
            'instance_info_card_subject_full_name',
            'mod_perform'
          ),
          text: this.subjectInstance.subject_user.fullname,
        },
        {
          label: this.$str(
            'subject_instance_card_job_assignment',
            'mod_perform'
          ),
          text: job_assignment_text,
        },
        {
          label: this.$str(
            'subject_instance_card_instance_count',
            'mod_perform'
          ),
          text: this.subjectInstance.instance_count,
        },
        {
          label: this.$str('instance_info_card_creation_date', 'mod_perform'),
          text: this.subjectInstance.created_at,
        },
      ];
    },
  },
  apollo: {
    subjectInstance: {
      query: subjectInstanceQuery,
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
        };
      },
      update: data => data['mod_perform_subject_instance_for_participant'],
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "instance_info_card_creation_date",
      "instance_info_card_show_all_button",
      "instance_info_card_subject_full_name",
      "subject_instance_card_instance_count",
      "subject_instance_card_job_assignment",
      "subject_instance_card_title"
    ]
  }
</lang-strings>
