/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

const stylelint = require('stylelint');

const ruleName = 'tui/ascii-only';
const messages = stylelint.utils.ruleMessages(ruleName, {
  rejected: 'Non-ascii character',
});

const plugin = stylelint.createPlugin(ruleName, on => {
  return (root, result) => {
    if (!on) {
      return;
    }

    const reportFromIndex = index => {
      stylelint.utils.report({
        message: messages.rejected,
        node: root,
        index,
        result,
        ruleName,
      });
    };

    const css = root.source.input.css;
    // eslint-disable-next-line no-control-regex
    const match = /[^\x00-\x7F]/.exec(css);
    if (match) {
      reportFromIndex(match.index);
    }
  };
});

plugin.ruleName = ruleName;
plugin.messages = messages;

module.exports = plugin;
