/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 */

/* eslint-disable */

const stylelint = require('stylelint');

function createAtExtendPlugin() {
  const ruleName = 'totara/at-extend-only-placeholders';

  const messages = stylelint.utils.ruleMessages(ruleName, {
    rejected: '@extend may only be used with %placeholders',
  });

  const plugin = stylelint.createPlugin(ruleName, on => {
    return (root, result) => {
      if (!on) {
        return;
      }

      root.walkAtRules('extend', rule => {
        if (rule.params.trim()[0] !== '%') {
          stylelint.utils.report({
            ruleName,
            result,
            node: rule,
            message: messages.rejected,
          });
        }
      });
    };
  });

  plugin.ruleName = ruleName;
  plugin.messages = messages;
  return plugin;
}

module.exports = [createAtExtendPlugin()];
