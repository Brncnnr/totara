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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <FileIcon v-for="(file, i) in files" :key="i" :filename="file" />
  </div>
</template>

<script>
import FileIcon from 'tui/components/icons/files/compute/FileIcon';

export default {
  components: {
    FileIcon,
  },

  data() {
    return {
      files: [
        'filename.mp3',
        'application.zip',
        'filename.docx',
        'filename.xlsx',
        'filename.svg',
        'filename.mp4',
        'filename.ppt',
        'filename.pdf',
        'filename.txt',
      ],
    };
  },
};
</script>
