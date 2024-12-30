import globals from "globals";
import pluginJs from "@eslint/js";
import tseslint from "typescript-eslint";
import pluginReact from "eslint-plugin-react";


/** @type {import('eslint').Linter.Config[]} */
export default [
    {
        files: ["**/*.{ts,tsx}"],
        languageOptions: {globals: globals.browser},
        rules: {
            semi: ["error", "never"]
        },
    },
    {
        ignores: ["public", "vendor", "webpack.config.js"]
    },
    {
        settings: {
            react: {
                version: "detect"
            }
        }
    },
    pluginJs.configs.recommended,
    ...tseslint.configs.recommended,
    pluginReact.configs.flat.recommended,
];