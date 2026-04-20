# PHPComplex (markbaker/complex)

## Upgrade

1. Run the following commands:

```sh
installdir=$(mktemp -d)
cd "$installdir"
composer require markbaker/complex

cd -
rm -rf public/lib/phpspreadsheet/markbaker/complex/classes
cp -rf "$installdir"/vendor/markbaker/complex/classes public/lib/phpspreadsheet/markbaker/complex/classes
cp -f "$installdir"/vendor/markbaker/complex/composer.json public/lib/phpspreadsheet/markbaker/complex/composer.json
cp -f "$installdir"/vendor/markbaker/complex/license.md public/lib/phpspreadsheet/markbaker/complex/license.md

git add public/lib/phpspreadsheet/markbaker/complex
rm -rf "$installdir"
```

2. Review the changes.
3. Update the version in `public/lib/thirdpartylibs.xml`.
