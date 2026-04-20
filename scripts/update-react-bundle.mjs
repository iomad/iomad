// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Script to update the react and react-dom bundles.
 *
 * @copyright  Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import chalk from 'chalk';
import fs from "fs-extra";
import path from "path";
import {
    createPackageReadme,
    download,
    getPackageVersion,
    getRootDir,
    updateThirdPartyLibsXml,
} from './lib/util.mjs';

const rootDir = getRootDir();
const outputdir = path.resolve(rootDir, 'lib', 'js', 'bundles');
const reactOutputDir = path.join(outputdir, 'react');
const reactDomOutputDir = path.join(outputdir, 'react-dom');

// Bundle config.
const TARGET = "es2022";
const REACT_VERSION = getPackageVersion('react');
const REACT_DOM_VERSION = getPackageVersion('react-dom');

// Bundles to download.
const bundles = [
  { packageName: "react", version: REACT_VERSION, fileName: "react" },
  { packageName: "react", version: REACT_VERSION, fileName: "jsx-runtime" },
  { packageName: "react", version: REACT_VERSION, fileName: "jsx-dev-runtime", dev: true },
  { packageName: "react-dom", version: REACT_DOM_VERSION, fileName: "react-dom" },
  { packageName: "react-dom", version: REACT_DOM_VERSION, fileName: "client" },
  { packageName: "react-dom", version: REACT_DOM_VERSION, fileName: "profiling", outputFileName: "client.development" },
].map((bundle) => ({
  ...bundle,
  url: `https://esm.sh/stable/${bundle.packageName}@${bundle.version}/${TARGET}/${bundle.fileName}${bundle.dev ? '.development' : ''}.bundle.mjs`
}));

/**
 * Update the react and react-dom bundles by downloading them from esm.sh and saving them in the lib folder.
 * Also updates the version in thirdpartylibs.xml.
 * The version is read from the npm-shrinkwrap.json file.
 */
async function init() {
  console.log(chalk.blue.bold.underline('Updating React bundles to version %s using esm.sh'), REACT_VERSION);

  console.log(chalk.blue('Removing old bundles...'));
  fs.removeSync(reactOutputDir, { recursive: true, force: true });
  fs.removeSync(reactDomOutputDir, { recursive: true, force: true });
  console.log(chalk.green('Old bundles removed ✓'));

  for (const bundle of bundles) {
    const fileDir = path.join(outputdir, bundle.packageName);
    const filePath = path.join(fileDir, `${bundle.outputFileName ?? bundle.fileName}.js`);
    console.log(chalk.green(`→ ${bundle.packageName}/${bundle.fileName} ✓`));
    await download(bundle.url, filePath, (filePath) => {
      let content = fs.readFileSync(filePath, 'utf-8');

      bundles.forEach((bundle) => {
        if (bundle.packageName === bundle.fileName) {
          // For the main react and react-dom bundles, we need to replace the import paths to remove the version number.
          // This is because the imports in the esm.sh bundles include the version number, but we want to use the unversioned paths in our lib folder.
          // For example, the import path in the bundle might be "/react@19.2.4/es2022/react.js", but we want it to be "react".
          content = content.replaceAll(
            `/stable/${bundle.packageName}@${bundle.version}/${TARGET}/${bundle.fileName}.mjs`,
            `${bundle.packageName}`
          );
        } else {
          content = content.replaceAll(
            `/stable/${bundle.packageName}@${bundle.version}/${TARGET}/${bundle.fileName}.mjs`,
            `${bundle.packageName}/${bundle.fileName}`
          );
        }
      });

      fs.writeFileSync(filePath, content);
    });
  }

  // Create readme files in the package folders.
  console.log(chalk.green(`Creating readme_moodle.txt files ✓`));
  createPackageReadme(reactOutputDir, 'react');
  createPackageReadme(reactDomOutputDir, 'react-dom');

  // Update the version in thirdpartylibs.xml.
  console.log(chalk.green(`Updating thirdpartylibs.xml files ✓`));
  updateThirdPartyLibsXml(path.join(rootDir, 'lib'), 'js/bundles/react', 'react', REACT_VERSION);
  updateThirdPartyLibsXml(path.join(rootDir, 'lib'), 'js/bundles/react-dom', 'react-dom', REACT_DOM_VERSION);

  console.log("\nAll bundles saved to " + outputdir.replace(rootDir, '[ROOT]') + chalk.green(" ✓"));
  console.log("Done!");
};

init().catch((err) => {
  console.error("Download failed:", err.message);
  process.exit(1);
});
