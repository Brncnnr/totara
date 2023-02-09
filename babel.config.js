// Shim for Jest

/* Switch eslint config environment to 'node' to prevent 'module' definition error */
/* eslint-env node */

const babelConfigs = require('./client/tooling/configs/babel');

module.exports = api => {
    const isTest = api.env('test');
    const legacy = !!process.env.BABEL_LEGACY;

    if (isTest) {
        if (legacy) {
            return babelConfigs.legacy;
        } else {
            return babelConfigs.test;
        }
    }

    return {};
};
