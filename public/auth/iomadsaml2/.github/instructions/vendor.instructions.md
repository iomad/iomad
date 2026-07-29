When bumping simplesaml as a dependency we need to review the following
Make sure we have committed the full vendor folder, so it can be installed as expected
Make sure that the version of simplesamlphp installed is the one listed in thirdpartylibs.xml
Make sure all the patches in the patches folder have successfully applied
Make sure there has been a version bump in the main version.php file
