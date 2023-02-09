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
 * @module totara_core
 */

const { parseQuery } = require('loader-utils');

const { escapeRegExp } = require('../lib/common');
const { vueFolders } = require('../lib/resolution');

const themeOverrideRegex = new RegExp(
  '(client\\/component\\/theme_[^/]+)\\/src\\/(' +
    vueFolders.map(x => escapeRegExp(x)).join('|') +
    ')\\/overrides\\/(.*).vue$'
);

/**
 * TUI-specific Vue transforms
 *
 * - Add `__hasBlocks` property to component options
 * - Add `tui.processOverride()` calls to components in `(vue folder)/overrides` folders
 *
 * @param {string} source
 * @param {?SourceMap} map
 */
module.exports = function(source, map) {
  const params =
    this.resourceQuery && this.resourceQuery[0] == '?'
      ? parseQuery(this.resourceQuery)
      : {};

  // if the query has a type field, this is a language block request
  if (!params.type) {
    const scriptImport = /import script from ".*?(\?.*?)"/.exec(source);
    const scriptQuery = scriptImport ? parseQuery(scriptImport[1]) : null;

    const hasBlocks = {
      script: !!scriptImport,
      template: /import { render, staticRenderFns } from/.test(source),
    };

    let code =
      `\ncomponent.options.__hasBlocks = ` + `${JSON.stringify(hasBlocks)};`;

    if (scriptQuery && scriptQuery.extends) {
      code += '\ncomponent.options.__extends = true;';
    }

    // process theme overrides
    const themeOverrideMatch = themeOverrideRegex.exec(
      this.resourcePath.replace(/\\/g, '/')
    );
    // skip if we're not loading an override component or if the file is not in a subfolder
    if (themeOverrideMatch && themeOverrideMatch[3].indexOf('/') !== -1) {
      const folder = themeOverrideMatch[2];
      let parentName = themeOverrideMatch[3];
      const sepIndex = parentName.indexOf('/');
      parentName =
        parentName.substring(0, sepIndex) +
        '/' +
        folder +
        parentName.substring(sepIndex);

      code +=
        '\ntui._processOverride(component.exports, ' +
        JSON.stringify(parentName) +
        ');';
    }

    let pos = source.indexOf('\n/* hot reload */');
    if (pos === -1) {
      pos = source.lastIndexOf('\nexport default');
    }

    if (pos !== -1) {
      source = source.substring(0, pos) + code + source.substring(pos);
    }
  }
  this.callback(null, source, map);
};
