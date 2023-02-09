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

  @author Marco Song <marco.song@totaralearning.com>
  @package mod_perform
-->

<template>
  <InformationModal :open="open" :title="title" @close="$emit('close')">
    <div>
      <p>{{ description }}</p>
      <ul>
        <li v-for="(item, key) in activitySections" :key="key">
          {{ item }}
        </li>
      </ul>
    </div>
  </InformationModal>
</template>

<script>
import InformationModal from 'tui/components/modal/InformationModal';

export default {
  components: {
    InformationModal,
  },

  props: {
    title: String,
    description: String,
    activitySections: Array,
    open: Boolean,
  },
};
</script>
