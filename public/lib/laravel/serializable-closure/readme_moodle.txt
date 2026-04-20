Description of Serializable Closure library import into Moodle.

Source: https://github.com/laravel/serializable-closure

This library is a dependency of php-di/php-di, used by Moodle's dependency
injection container (core\di).

To update:
- Download the desired release from https://github.com/laravel/serializable-closure/releases
- Delete the contents of the src/ directory completely and copy in the new one
- Copy composer.json, LICENSE.md and README.md from the zip root
- Edit public/lib/thirdpartylibs.xml and update the version

To test:
- vendor/bin/phpunit public/lib/tests/di_test.php

History:
- MDL-81671: Successfully upgraded 1.3.2 -> 1.3.3 (Moodle 4.5, Paul Holden)
- MDL-84180: Attempted 1.3.3 -> 2.0.x, closed Won't Fix -- blocked by php-di
             only supporting ^1.0 at the time. Andrew Lyons subsequently fixed
             php-di upstream (PHP-DI/PHP-DI PR #899), enabling 2.x support.
- MDL-86460: Upgraded 2.0.3 -> 2.0.10 (Moodle 5.2)
