Instructions to import/update guzzle library into Moodle:

Update Guzzle and associated libraries.

```
installdir=`mktemp -d`
cd "$installdir"
composer require guzzlehttp/guzzle kevinrob/guzzle-cache-middleware

cd -
rm -rf public/lib/guzzlehttp/guzzle public/lib/guzzlehttp/psr7 public/lib/guzzlehttp/promises public/lib/guzzlehttp/kevinrob/guzzlecache
cp -rf "$installdir"/vendor/guzzlehttp/guzzle public/lib/guzzlehttp/guzzle
cp -rf "$installdir"/vendor/guzzlehttp/psr7 public/lib/guzzlehttp/psr7
cp -rf "$installdir"/vendor/guzzlehttp/promises public/lib/guzzlehttp/promises
cp -rf "$installdir"/vendor/kevinrob/guzzle-cache-middleware public/lib/guzzlehttp/kevinrob/guzzlecache
rm -rf public/lib/guzzlehttp/kevinrob/guzzlecache/*.png

echo "See instructions in public/lib/guzzlehttp/readme_moodle.md" > public/lib/guzzlehttp/guzzle/readme_moodle.txt
echo "See instructions in public/lib/guzzlehttp/readme_moodle.md" > public/lib/guzzlehttp/promises/readme_moodle.txt
echo "See instructions in public/lib/guzzlehttp/readme_moodle.md" > public/lib/guzzlehttp/psr7/readme_moodle.txt
echo "See instructions in public/lib/guzzlehttp/readme_moodle.md" > public/lib/guzzlehttp/kevinrob/guzzlecache/readme_moodle.txt
git add public/lib/guzzlehttp/guzzle public/lib/guzzlehttp/psr7 public/lib/guzzlehttp/promises public/lib/guzzlehttp/kevinrob
```

Now update `public/lib/thirdpartylibs.xml`
