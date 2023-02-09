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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @package totara_evidence
-->

<template>
  <div class="tui-linkedReviewAdminViewEvidence">
    <ParticipantContent
      :title="$str('admin_placeholder_title', 'totara_evidence')"
      :content="data"
      :type="$str('admin_placeholder_type', 'totara_evidence')"
    />
  </div>
</template>

<script>
import ParticipantContent from 'totara_evidence/components/performelement_linked_review/ParticipantContent';

export default {
  components: {
    ParticipantContent,
  },

  data() {
    return {
      data: {
        display_name: 'Example evidence',
        id: 0,
        type: {
          name: 'Evidence type',
        },
        content_type: '',
        created_at: 0,
        fields: [
          {
            label: this.$str('admin_placeholder_label_file', 'totara_evidence'),
            type: 'file',
            content: JSON.stringify({
              file_size: 12345678,
              file_name: 'filename.pdf',
              url: '',
              html: '',
            }),
          },
          {
            label: this.$str('admin_placeholder_label_date', 'totara_evidence'),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_date',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str(
              'admin_placeholder_label_location',
              'totara_evidence'
            ),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_location',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str('admin_placeholder_label_menu', 'totara_evidence'),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_menu',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str(
              'admin_placeholder_label_text_area',
              'totara_evidence'
            ),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_text_area',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str(
              'admin_placeholder_label_text_input',
              'totara_evidence'
            ),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_text_input',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str(
              'admin_placeholder_label_checkbox',
              'totara_evidence'
            ),
            type: '',
            content: JSON.stringify({
              html: this.$str(
                'admin_placeholder_field_checkbox',
                'totara_evidence'
              ),
            }),
          },
          {
            label: this.$str('admin_placeholder_label_url', 'totara_evidence'),
            type: '',
            content: JSON.stringify({
              html: this.$str('admin_placeholder_field_url', 'totara_evidence'),
            }),
          },
        ],
      },
    };
  },
};
</script>

<lang-strings>
{
  "totara_evidence": [
    "admin_placeholder_field_checkbox",
    "admin_placeholder_field_date",
    "admin_placeholder_field_location",
    "admin_placeholder_field_menu",
    "admin_placeholder_field_text_area",
    "admin_placeholder_field_text_input",
    "admin_placeholder_field_url",
    "admin_placeholder_label_checkbox",
    "admin_placeholder_label_date",
    "admin_placeholder_label_file",
    "admin_placeholder_label_location",
    "admin_placeholder_label_menu",
    "admin_placeholder_label_text_area",
    "admin_placeholder_label_text_input",
    "admin_placeholder_label_url",
    "admin_placeholder_title",
    "admin_placeholder_type"
  ]
}
</lang-strings>
