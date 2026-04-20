import chalk from 'chalk';
import fs from "fs-extra";
import path from "path";
import {
  createPackageReadme,
  getPackageVersion,
  getRootDir,
  updateThirdPartyLibsXml,
} from './lib/util.mjs';

/**
 * Update the @moodlehq/design-system bundle and tokens in the lib and public folders.
 * Also updates the version in thirdpartylibs.xml files.
 * The version is read from the npm-shrinkwrap.json file.
 */
async function init() {
  const rootDir = getRootDir();

  const DS_VERSION = getPackageVersion('@moodlehq/design-system');
  const nodeModuleRoot = path.join(rootDir, 'node_modules', '@moodlehq', 'design-system');
  const bundleRoot = path.join(rootDir, 'lib', 'js', 'bundles', 'design-system');
  const themeRoot = path.join(rootDir, 'public', 'theme', 'boost');
  const themeDesignSystemRoot = path.join(themeRoot, 'scss', 'design-system');

  console.log(chalk.blue.bold.underline('Updating @moodlehq/design-system bundle to version %s from Node Modules'), DS_VERSION);
  console.log(chalk.blue('Removing old bundles...'));
  fs.removeSync(bundleRoot, { recursive: true, force: true });
  fs.removeSync(themeDesignSystemRoot, { recursive: true, force: true });
  console.log(chalk.green('Old bundles removed ✓'));

  // Copy the JS bundles to the lib folder.
  fs.copySync(
    path.join(nodeModuleRoot, 'dist'),
    path.join(bundleRoot),
  );
  console.log(chalk.green(`→ @moodlehq/design-system:${DS_VERSION} JS bundles ✓`));

  // Copy tokens into the themes.
  fs.copySync(
    path.join(nodeModuleRoot, 'tokens', 'scss'),
    path.join(themeDesignSystemRoot, 'tokens', 'scss'),
  );
  console.log(chalk.green(`→ @moodlehq/design-system:${DS_VERSION} tokens ✓`));

  // Create readme files in the package folders.
  console.log(chalk.green(`→ Creating readme_moodle.txt files ✓`));
  createPackageReadme(bundleRoot, '@moodlehq/design-system');
  createPackageReadme(themeDesignSystemRoot, '@moodlehq/design-system');

  // And update the version in thirdpartylibs.xml.
  console.log(chalk.green(`→ Updating thirdpartylibs.xml files ✓`));
  updateThirdPartyLibsXml(path.join(rootDir, 'lib'), 'js/bundles/design-system', '@moodlehq/design-system', DS_VERSION);
  updateThirdPartyLibsXml(themeRoot, 'scss/design-system', '@moodlehq/design-system', DS_VERSION);

  console.log("\nAll bundles saved" + chalk.green(" ✓"));
  console.log("Done!");
}

init().catch((err) => {
  console.error("Download failed:", err.message);
  process.exit(1);
});
