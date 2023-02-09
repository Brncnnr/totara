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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_core
-->

<script>
import Archive from 'tui/components/icons/files/Archive';
import Excel from 'tui/components/icons/files/Excel';
import File from 'tui/components/icons/files/File';
import ImageIcon from 'tui/components/icons/files/Image';
import Pdf from 'tui/components/icons/files/Pdf';
import Powerpoint from 'tui/components/icons/files/Powerpoint';
import Video from 'tui/components/icons/files/Video';
import Word from 'tui/components/icons/files/Word';
import Audio from 'tui/components/icons/files/Audio';

export default {
  functional: true,
  components: {
    Audio,
    Archive,
    Excel,
    File,
    ImageIcon,
    Pdf,
    Powerpoint,
    Video,
    Word,
  },

  props: {
    filename: String,
    alt: String,
    title: String,
    size: [String, Number],
    customClass: [String, Array, Object],
  },

  render(h, { props }) {
    let { filename } = props,
      extension,
      component = null;

    if (typeof filename !== 'undefined' && filename != null) {
      // File extension type will be the last bit of . after the file name.
      let parts = filename.split('.');
      extension = parts.pop();
    }

    switch (String.prototype.toLowerCase.call(extension)) {
      case 'pdf':
        component = Pdf;
        break;

      case 'docx':
        component = Word;
        break;

      case 'xlsx':
      case 'xlsm':
      case 'xltx':
      case 'xltm':
      case 'csv':
        component = Excel;
        break;

      case 'svg+xml':
      case 'svg':
      case 'png':
      case 'jpg':
      case 'jpeg':
        component = ImageIcon;
        break;

      case 'zip':
      case 'rar':
      case 'tar':
      case 'gz':
        component = Archive;
        break;

      case 'ppt':
        component = Powerpoint;
        break;

      case 'mp4':
      case 'mov':
      case 'avi':
      case 'mkv':
        component = Video;
        break;

      case 'mp3':
        component = Audio;
        break;

      case 'undefined':
      case null:
      default:
        component = File;
        break;
    }

    return h(component, {
      props: Object.assign({}, props),
    });
  },
};
</script>
