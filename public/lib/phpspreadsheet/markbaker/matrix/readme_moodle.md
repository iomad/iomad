# PHPMatrix (markbaker/matrix)

## Upgrade

1. Run the following commands:

```sh
installdir=$(mktemp -d)
cd "$installdir"
composer require markbaker/matrix

cd -
rm -rf public/lib/phpspreadsheet/markbaker/matrix/classes
cp -rf "$installdir"/vendor/markbaker/matrix/classes public/lib/phpspreadsheet/markbaker/matrix/classes
cp -f "$installdir"/vendor/markbaker/matrix/composer.json public/lib/phpspreadsheet/markbaker/matrix/composer.json
cp -f "$installdir"/vendor/markbaker/matrix/license.md public/lib/phpspreadsheet/markbaker/matrix/license.md

git add public/lib/phpspreadsheet/markbaker/matrix
rm -rf "$installdir"
```

2. Review the changes.
3. Update the version in `public/lib/thirdpartylibs.xml`.
