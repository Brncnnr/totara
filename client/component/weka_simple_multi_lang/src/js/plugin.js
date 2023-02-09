/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module weka_simple_multi_lang
 */
import { Plugin, PluginKey } from 'ext_prosemirror/state';

/**
 * @param {Function}  handleKeyDown
 * @return {Plugin}
 */
export default function({ handleKeyDown }) {
  const pluginKey = new PluginKey('weka_simple_multi_lang');

  return new Plugin({
    key: pluginKey,
    props: {
      handleKeyDown: handleKeyDown,
    },
  });
}
