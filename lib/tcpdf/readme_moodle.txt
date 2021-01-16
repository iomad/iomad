Description of TCPDF library import 6.2.13
===========================================
* download library from https://github.com/tecnickcom/TCPDF/releases
* delete examples/, tools/ and tcpdf_import.php
* remove tcpdf_import.php from composer.json
* remove all fonts that were not already present
* visit http://127.0.0.1/lib/tests/other/pdflibtestpage.php and view the pdf
* check the status of https://github.com/tecnickcom/TCPDF/pull/74 and re-apply if not merged/closed

2017/10/02
----------
Upgrade to tcpdf_php5 TCPDF 6.2.13 (MDL-60237)
by Marina Glancy <marina@moodle.com>

* replaced the calls to function each() deprecated in PHP7.2

2015/09/29
----------
Upgrade to tcpdf_php5 TCPDF 6.2.12 (MDL-51534)
by Jun Pataleta <jun@moodle.com>

2015/03/23
----------
Upgrade to tcpdf_php5 TCPDF 6.2.6 (MDL-49522)
by Adrian Greeve <adrian@moodle.com>

2011/10/29
----------
Upgrade to tcpdf_php5 TCPDF 5.9.133 (MDL-29283)
by Petr Skoda

2009/11/19
----------
Upgrade to tcpdf_php5 TCPDF 4.8.014 (MDL-20888)
by David Mudrak <david.mudrak@gmail.com>

2009/07/20
----------
Upgrade to tcpdf_php5 TCPDF 4.6.020 (MDL-19876)
by David Mudrak <david.mudrak@gmail.com>

* deleted cache/ doc/ examples/ config/tcpdf_config_alt.php config/lang/ images/
* removed all fonts but the core ones (courier.php, helveticabi.php,
    helveticab.php, helveticai.php, helvetica.php, symbol.php, timesbi.php,
    timesb.php, timesi.php, times.php, zapfdingbats.php) and FreeFont
* FreeFont chosen as a default utf8 font distributed by default, all others will
    be downloadable from moodle.org via new UI
* removed font/utils/
* moving configuration to the lib/pdflib.php wrapper so we do not need to modify
    TCPDF at all. Credit to Chardelle Busch for this solution in MDL-17179

2008/07/29
----------
Upgrade to tcpdf_php5 TCPDF 4.0.015 (MDL-15055)
by David Mudrak <david.mudrak@gmail.com>

* deleted cache/ doc/ examples/
* modified config/tcpdf_config.php
    Default values for K_PATH_MAIN and K_PATH_URL are automatically set for
    the Moodle installation. K_PATH_CACHE set to Moodle datadir cache.
    K_PATH_URL_CACHE can't be mapped in Moodle as datadir should not be
    accessible directly. However, it doesn't seem to be used within the
    library code.
