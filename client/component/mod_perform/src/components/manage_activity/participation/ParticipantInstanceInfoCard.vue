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
      :title="$str('participant_instance_card_title', 'mod_perform')"
      :info-lines="infoLines"
      :show-all-link="showAllLink"
    />
  </Loader>
</template>

<script>
import participantInstanceQuery from 'mod_perform/graphql/participant_instance';
import InstanceInfoCard from 'mod_perform/components/manage_activity/participation/InstanceInfoCard';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    InstanceInfoCard,
    Loader,
  },
  props: {
    participantInstanceId: {
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
      participantInstance: null,
    };
  },
  computed: {
    infoLines() {
      if (this.participantInstance === null) {
        return [];
      }

      return [
        {
          label: this.$str(
            'participant_instance_card_participant_full_name',
            'mod_perform'
          ),
          text: this.participantInstance.participant
            ? this.participantInstance.participant.fullname
            : this.$str('hidden_anonymised', 'mod_perform'),
        },
        {
          label: this.$str(
            'instance_info_card_subject_full_name',
            'mod_perform'
          ),
          text: this.participantInstance.subject_instance.subject_user.fullname,
        },
        {
          label: this.$str(
            'participant_instance_card_relationship',
            'mod_perform'
          ),
          text: this.participantInstance.participant
            ? this.participantInstance.core_relationship.name
            : this.$str('hidden_anonymised', 'mod_perform'),
        },
        {
          label: this.$str('instance_info_card_creation_date', 'mod_perform'),
          text: this.participantInstance.created_at,
        },
      ];
    },
  },
  apollo: {
    participantInstance: {
      query: participantInstanceQuery,
      variables() {
        return {
          participant_instance_id: this.participantInstanceId,
        };
      },
      update: data => data['mod_perform_participant_instance'],
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "hidden_anonymised",
      "instance_info_card_subject_full_name",
      "instance_info_card_creation_date",
      "participant_instance_card_participant_full_name",
      "participant_instance_card_relationship",
      "participant_instance_card_title"
    ]
  }
</lang-strings>
