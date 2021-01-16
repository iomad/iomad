<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// Moodle configuration file                                             //
//                                                                       //
// This file should be renamed "config.php" in the top-level directory   //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
unset($CFG);  // Ignore this line
global $CFG;  // This is necessary here for PHPUnit execution
$CFG = new stdClass();

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
// First, you need to configure the database where all Moodle data       //
// will be stored.  This database must already have been created         //
// and a username/password created to access it.                         //

$CFG->dbtype    = 'pgsql';      // 'pgsql', 'mariadb', 'mysqli', 'mssql', 'sqlsrv' or 'oci'
$CFG->dblibrary = 'native';     // 'native' only at the moment
$CFG->dbhost    = 'localhost';  // eg 'localhost' or 'db.isp.com' or IP
$CFG->dbname    = 'moodle';     // database name, eg moodle
$CFG->dbuser    = 'username';   // your database username
$CFG->dbpass    = 'password';   // your database password
$CFG->prefix    = 'mdl_';       // prefix to use for all table names
$CFG->dboptions = array(
    'dbpersist' => false,       // should persistent database connections be
                                //  used? set to 'false' for the most stable
                                //  setting, 'true' can improve performance
                                //  sometimes
    'dbsocket'  => false,       // should connection via UNIX socket be used?
                                //  if you set it to 'true' or custom path
                                //  here set dbhost to 'localhost',
                                //  (please note mysql is always using socket
                                //  if dbhost is 'localhost' - if you need
                                //  local port connection use '127.0.0.1')
    'dbport'    => '',          // the TCP port number to use when connecting
                                //  to the server. keep empty string for the
                                //  default port
    'dbhandlesoptions' => false,// On PostgreSQL poolers like pgbouncer don't
                                // support advanced options on connection.
                                // If you set those in the database then
                                // the advanced settings will not be sent.
    'dbcollation' => 'utf8mb4_unicode_ci', // MySQL has partial and full UTF-8
                                // support. If you wish to use partial UTF-8
                                // (three bytes) then set this option to
                                // 'utf8_unicode_ci', otherwise this option
                                // can be removed for MySQL (by default it will
                                // use 'utf8mb4_unicode_ci'. This option should
                                // be removed for all other databases.
);


//=========================================================================
// 2. WEB SITE LOCATION
//=========================================================================
// Now you need to tell Moodle where it is located. Specify the full
// web address to where moodle has been installed.  If your web site
// is accessible via multiple URLs then choose the most natural one
// that your students would use.  Do not include a trailing slash
//
// If you need both intranet and Internet access please read
// http://docs.moodle.org/en/masquerading

$CFG->wwwroot   = 'http://example.com/moodle';


//=========================================================================
// 3. DATA FILES LOCATION
//=========================================================================
// Now you need a place where Moodle can save uploaded files.  This
// directory should be readable AND WRITEABLE by the web server user
// (usually 'nobody' or 'apache'), but it should not be accessible
// directly via the web.
//
// - On hosting systems you might need to make sure that your "group" has
//   no permissions at all, but that "others" have full permissions.
//
// - On Windows systems you might specify something like 'c:\moodledata'

$CFG->dataroot  = '/home/example/moodledata';


//=========================================================================
// 4. DATA FILES PERMISSIONS
//=========================================================================
// The following parameter sets the permissions of new directories
// created by Moodle within the data directory.  The format is in
// octal format (as used by the Unix utility chmod, for example).
// The default is usually OK, but you may want to change it to 0750
// if you are concerned about world-access to the files (you will need
// to make sure the web server process (eg Apache) can access the files.
// NOTE: the prefixed 0 is important, and don't use quotes.

$CFG->directorypermissions = 02777;


//=========================================================================
// 5. DIRECTORY LOCATION  (most people can just ignore this setting)
//=========================================================================
// A very few webhosts use /admin as a special URL for you to access a
// control panel or something.  Unfortunately this conflicts with the
// standard location for the Moodle admin pages.  You can work around this
// by renaming the admin directory in your installation, and putting that
// new name here.  eg "moodleadmin".  This should fix all admin links in Moodle.
// After any change you need to visit your new admin directory
// and purge all caches.

$CFG->admin = 'admin';


//=========================================================================
// 6. OTHER MISCELLANEOUS SETTINGS (ignore these for new installations)
//=========================================================================
//
// These are additional tweaks for which no GUI exists in Moodle yet.
//
// Starting in PHP 5.3 administrators should specify default timezone
// in PHP.ini, you can also specify it here if needed.
// See details at: http://php.net/manual/en/function.date-default-timezone-set.php
// List of time zones at: http://php.net/manual/en/timezones.php
//     date_default_timezone_set('Australia/Perth');
//
// Change the key pair lifetime for Moodle Networking
// The default is 28 days. You would only want to change this if the key
// was not getting regenerated for any reason. You would probably want
// make it much longer. Note that you'll need to delete and manually update
// any existing key.
//      $CFG->mnetkeylifetime = 28;
//
// Not recommended: Set the following to true to allow the use
// off non-Moodle standard characters in usernames.
//      $CFG->extendedusernamechars = true;
//
// Allow user passwords to be included in backup files. Very dangerous
// setting as far as it publishes password hashes that can be unencrypted
// if the backup file is publicy available. Use it only if you can guarantee
// that all your backup files remain only privacy available and are never
// shared out from your site/institution!
//      $CFG->includeuserpasswordsinbackup = true;
//
// Completely disable user creation when restoring a course, bypassing any
// permissions granted via roles and capabilities. Enabling this setting
// results in the restore process stopping when a user attempts to restore a
// course requiring users to be created.
//     $CFG->disableusercreationonrestore = true;
//
// Keep the temporary directories used by backup and restore without being
// deleted at the end of the process. Use it if you want to debug / view
// all the information stored there after the process has ended. Note that
// those directories may be deleted (after some ttl) both by cron and / or
// by new backup / restore invocations.
//     $CFG->keeptempdirectoriesonbackup = true;
//
// Modify the restore process in order to force the "user checks" to assume
// that the backup originated from a different site, so detection of matching
// users is performed with different (more "relaxed") rules. Note that this is
// only useful if the backup file has been created using Moodle < 1.9.4 and the
// site has been rebuilt from scratch using backup files (not the best way btw).
// If you obtain user conflicts on restore, rather than enabling this setting
// permanently, try restoring the backup on a different site, back it up again
// and then restore on the target server.
//    $CFG->forcedifferentsitecheckingusersonrestore = true;
//
// Force the backup system to continue to create backups in the legacy zip
// format instead of the new tgz format. Does not affect restore, which
// auto-detects the underlying file format.
//    $CFG->usezipbackups = true;
//
// Prevent stats processing and hide the GUI
//      $CFG->disablestatsprocessing = true;
//
// Setting this to true will enable admins to edit any post at any time
//      $CFG->admineditalways = true;
//
// These variables define DEFAULT block variables for new courses
// If this one is set it overrides all others and is the only one used.
//      $CFG->defaultblocks_override = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//
// These variables define the specific settings for defined course formats.
// They override any settings defined in the formats own config file.
//      $CFG->defaultblocks_site = 'site_main_menu,course_list:course_summary,calendar_month';
//      $CFG->defaultblocks_social = 'participants,search_forums,calendar_month,calendar_upcoming,social_activities,recent_activity,course_list';
//      $CFG->defaultblocks_topics = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//      $CFG->defaultblocks_weeks = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//
// These blocks are used when no other default setting is found.
//      $CFG->defaultblocks = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//
// You can specify a different class to be created for the $PAGE global, and to
// compute which blocks appear on each page. However, I cannot think of any good
// reason why you would need to change that. It just felt wrong to hard-code the
// the class name. You are strongly advised not to use these to settings unless
// you are absolutely sure you know what you are doing.
//      $CFG->moodlepageclass = 'moodle_page';
//      $CFG->moodlepageclassfile = "$CFG->dirroot/local/myplugin/mypageclass.php";
//      $CFG->blockmanagerclass = 'block_manager';
//      $CFG->blockmanagerclassfile = "$CFG->dirroot/local/myplugin/myblockamanagerclass.php";
//
// Seconds for files to remain in caches. Decrease this if you are worried
// about students being served outdated versions of uploaded files.
//     $CFG->filelifetime = 60*60*6;
//
// Some web servers can offload the file serving from PHP process,
// comment out one the following options to enable it in Moodle:
//     $CFG->xsendfile = 'X-Sendfile';           // Apache {@see https://tn123.org/mod_xsendfile/}
//     $CFG->xsendfile = 'X-LIGHTTPD-send-file'; // Lighttpd {@see http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file}
//     $CFG->xsendfile = 'X-Accel-Redirect';     // Nginx {@see http://wiki.nginx.org/XSendfile}
// If your X-Sendfile implementation (usually Nginx) uses directory aliases specify them
// in the following array setting:
//     $CFG->xsendfilealiases = array(
//         '/dataroot/' => $CFG->dataroot,
//         '/cachedir/' => '/var/www/moodle/cache',    // for custom $CFG->cachedir locations
//         '/localcachedir/' => '/var/local/cache',    // for custom $CFG->localcachedir locations
//         '/tempdir/'  => '/var/www/moodle/temp',     // for custom $CFG->tempdir locations
//         '/filedir'   => '/var/www/moodle/filedir',  // for custom $CFG->filedir locations
//     );
//
// YUI caching may be sometimes improved by slasharguments:
//     $CFG->yuislasharguments = 1;
// Some servers may need a special rewrite rule to work around internal path length limitations:
// RewriteRule (^.*/theme/yui_combo\.php)(/.*) $1?file=$2
//
//
// Following settings may be used to select session driver, uncomment only one of the handlers.
//   Database session handler (not compatible with MyISAM):
//      $CFG->session_handler_class = '\core\session\database';
//      $CFG->session_database_acquire_lock_timeout = 120;
//
//   File session handler (file system locking required):
//      $CFG->session_handler_class = '\core\session\file';
//      $CFG->session_file_save_path = $CFG->dataroot.'/sessions';
//
//   Memcached session handler (requires memcached server and extension):
//      $CFG->session_handler_class = '\core\session\memcached';
//      $CFG->session_memcached_save_path = '127.0.0.1:11211';
//      $CFG->session_memcached_prefix = 'memc.sess.key.';
//      $CFG->session_memcached_acquire_lock_timeout = 120;
//      $CFG->session_memcached_lock_expire = 7200;       // Ignored if PECL memcached is below version 2.2.0
//      $CFG->session_memcached_lock_retry_sleep = 150;   // Spin-lock retry sleeptime (msec). Only effective
//                                                        // for tuning php-memcached 3.0.x (PHP 7)
//
//   Redis session handler (requires redis server and redis extension):
//      $CFG->session_handler_class = '\core\session\redis';
//      $CFG->session_redis_host = '127.0.0.1';
//      $CFG->session_redis_port = 6379;  // Optional.
//      $CFG->session_redis_database = 0;  // Optional, default is db 0.
//      $CFG->session_redis_auth = ''; // Optional, default is don't set one.
//      $CFG->session_redis_prefix = ''; // Optional, default is don't set one.
//      $CFG->session_redis_acquire_lock_timeout = 120;
//      $CFG->session_redis_lock_expire = 7200;
//      Use the igbinary serializer instead of the php default one. Note that phpredis must be compiled with
//      igbinary support to make the setting to work. Also, if you change the serializer you have to flush the database!
//      $CFG->session_redis_serializer_use_igbinary = false; // Optional, default is PHP builtin serializer.
//
//   Memcache session handler (requires memcached server and memcache extension):
//      $CFG->session_handler_class = '\core\session\memcache';
//      $CFG->session_memcache_save_path = '127.0.0.1:11211';
//      $CFG->session_memcache_acquire_lock_timeout = 120;
//      ** NOTE: Memcache extension has less features than memcached and may be
//         less reliable. Use memcached where possible or if you encounter
//         session problems. **
//
// Please be aware that when selecting either Memcached or Memcache for sessions that it is advised to use a dedicated
// memcache server. The memcache and memcached extensions do not provide isolated environments for individual uses.
// Using the same server for other purposes (MUC for example) can lead to sessions being prematurely removed should
// the other uses of the server purge the cache.
//
// Following setting allows you to alter how frequently is timemodified updated in sessions table.
//      $CFG->session_update_timemodified_frequency = 20; // In seconds.
//
// If this setting is set to true, then Moodle will track the IP of the
// current user to make sure it hasn't changed during a session.  This
// will prevent the possibility of sessions being hijacked via XSS, but it
// may break things for users coming using proxies that change all the time,
// like AOL.
//      $CFG->tracksessionip = true;
//
// The following lines are for handling email bounces.
//      $CFG->handlebounces = true;
//      $CFG->minbounces = 10;
//      $CFG->bounceratio = .20;
// The next lines are needed both for bounce handling and any other email to module processing.
// mailprefix must be EXACTLY four characters.
// Uncomment and customise this block for Postfix
//      $CFG->mailprefix = 'mdl+'; // + is the separator for Exim and Postfix.
//      $CFG->mailprefix = 'mdl-'; // - is the separator for qmail
//      $CFG->maildomain = 'youremaildomain.com';
//
// Enable when setting up advanced reverse proxy load balancing configurations,
// it may be also necessary to enable this when using port forwarding.
//      $CFG->reverseproxy = true;
//
// Enable when using external SSL appliance for performance reasons.
// Please note that site may be accessible via http: or https:, but not both!
//      $CFG->sslproxy = true;
//
// This setting will cause the userdate() function not to fix %d in
// date strings, and just let them show with a zero prefix.
//      $CFG->nofixday = true;
//
// This setting will make some graphs (eg user logs) use lines instead of bars
//      $CFG->preferlinegraphs = true;
//
// This setting allows you to specify a class to rewrite outgoing urls
// enabling 'clean urls' in conjunction with an apache / nginx handler.
// The handler must implement \core\output\url_rewriter.
//      $CFG->urlrewriteclass = '\local_cleanurls\url_rewriter';
//
// Enabling this will allow custom scripts to replace existing moodle scripts.
// For example: if $CFG->customscripts/course/view.php exists then
// it will be used instead of $CFG->wwwroot/course/view.php
// At present this will only work for files that include config.php and are called
// as part of the url (index.php is implied).
// Some examples are:
//      http://my.moodle.site/course/view.php
//      http://my.moodle.site/index.php
//      http://my.moodle.site/admin            (index.php implied)
// Custom scripts should not include config.php
// Warning: Replacing standard moodle scripts may pose security risks and/or may not
// be compatible with upgrades. Use this option only if you are aware of the risks
// involved.
// Specify the full directory path to the custom scripts
//      $CFG->customscripts = '/home/example/customscripts';
//
// Performance profiling
//
//   If you set Debug to "Yes" in the Configuration->Variables page some
//   performance profiling data will show up on your footer (in default theme).
//   With these settings you get more granular control over the capture
//   and printout of the data
//
//   Capture performance profiling data
//   define('MDL_PERF'  , true);
//
//   Capture additional data from DB
//   define('MDL_PERFDB'  , true);
//
//   Print to log (for passive profiling of production servers)
//   define('MDL_PERFTOLOG'  , true);
//
//   Print to footer (works with the default theme)
//   define('MDL_PERFTOFOOT', true);
//
//   Enable earlier profiling that causes more code to be covered
//   on every request (db connections, config load, other inits...).
//   Requires extra configuration to be defined in config.php like:
//   profilingincluded, profilingexcluded, profilingautofrec,
//   profilingallowme, profilingallowall, profilinglifetime
//       $CFG->earlyprofilingenabled = true;
//
// Force displayed usernames
//   A little hack to anonymise user names for all students.  If you set these
//   then all non-teachers will always see these for every person.
//       $CFG->forcefirstname = 'Bruce';
//       $CFG->forcelastname  = 'Simpson';
//
// The following setting will turn on username logging into Apache log. For full details regarding setting
// up of this function please refer to the install section of the document.
//     $CFG->apacheloguser = 0; // Turn this feature off. Default value.
//     $CFG->apacheloguser = 1; // Log user id.
//     $CFG->apacheloguser = 2; // Log full name in cleaned format. ie, Darth Vader will be displayed as darth_vader.
//     $CFG->apacheloguser = 3; // Log username.
// To get the values logged in Apache's log, add to your httpd.conf
// the following statements. In the General part put:
//     LogFormat "%h %l %{MOODLEUSER}n %t \"%r\" %s %b \"%{Referer}i\" \"%{User-Agent}i\"" moodleformat
// And in the part specific to your Moodle install / virtualhost:
//     CustomLog "/your/path/to/log" moodleformat
//
// Alternatively for other webservers such as nginx, you can instead have the username sent via a http header
// 'X-MOODLEUSER' which can be saved in the logfile and then stripped out before being sent to the browser:
//     $CFG->headerloguser = 0; // Turn this feature off. Default value.
//     $CFG->headerloguser = 1; // Log user id.
//     $CFG->headerloguser = 2; // Log full name in cleaned format. ie, Darth Vader will be displayed as darth_vader.
//     $CFG->headerloguser = 3; // Log username.
//
// CAUTION: Use of this option will expose usernames in the Apache / nginx log,
// If you are going to publish your log, or the output of your web stats analyzer
// this will weaken the security of your website.
//
// Email database connection errors to someone.  If Moodle cannot connect to the
// database, then email this address with a notice.
//
//     $CFG->emailconnectionerrorsto = 'your@emailaddress.com';
//
// Set the priority of themes from highest to lowest. This is useful (for
// example) in sites where the user theme should override all other theme
// settings for accessibility reasons. You can also disable types of themes
// (other than site)  by removing them from the array. The default setting is:
//      $CFG->themeorder = array('course', 'category', 'session', 'user', 'site');
// NOTE: course, category, session, user themes still require the
// respective settings to be enabled
//
// It is possible to add extra themes directory stored outside of $CFG->dirroot.
// This local directory does not have to be accessible from internet.
//
//     $CFG->themedir = '/location/of/extra/themes';
//
// It is possible to specify different cache and temp directories, use local fast filesystem
// for normal web servers. Server clusters MUST use shared filesystem for cachedir!
// Localcachedir is intended for server clusters, it does not have to be shared by cluster nodes.
// The directories must not be accessible via web.
//
//     $CFG->tempdir = '/var/www/moodle/temp';        // Directory MUST BE SHARED by all clsuter nodes.
//     $CFG->cachedir = '/var/www/moodle/cache';      // Directory MUST BE SHARED by all cluster nodes, locking required.
//     $CFG->localcachedir = '/var/local/cache';      // Intended for local node caching.
//
// Some filesystems such as NFS may not support file locking operations.
// Locking resolves race conditions and is strongly recommended for production servers.
//     $CFG->preventfilelocking = false;
//
// Site default language can be set via standard administration interface. If you
// want to have initial error messages for eventual database connection problems
// localized too, you have to set your language code here.
//
//     $CFG->lang = 'yourlangcode'; // for example 'cs'
//
// When Moodle is about to perform an intensive operation it raises PHP's memory
// limit. The following setting should be used on large sites to set the raised
// memory limit to something higher.
// The value for the settings should be a valid PHP memory value. e.g. 512M, 1G
//
//     $CFG->extramemorylimit = '1024M';
//
// Moodle 2.4 introduced a new cache API.
// The cache API stores a configuration file within the Moodle data directory and
// uses that rather than the database in order to function in a stand-alone manner.
// Using altcacheconfigpath you can change the location where this config file is
// looked for.
// It can either be a directory in which to store the file, or the full path to the
// file if you want to take full control. Either way it must be writable by the
// webserver.
//
//     $CFG->altcacheconfigpath = '/var/www/shared/moodle.cache.config.php
//
// Use the following flag to completely disable the Available update notifications
// feature and hide it from the server administration UI.
//
//      $CFG->disableupdatenotifications = true;
//
// Use the following flag to completely disable the installation of plugins
// (new plugins, available updates and missing dependencies) and related
// features (such as cancelling the plugin installation or upgrade) via the
// server administration web interface.
//
//      $CFG->disableupdateautodeploy = true;
//
// Use the following flag to disable the warning on the system notifications page
// about present development libraries. This flag will not disable the warning within
// the security overview report. Use this flag only if you really have prohibited web
// access to the development libraries in your webserver configuration.
//
//      $CFG->disabledevlibdirscheck = true;
//
// Use the following flag to disable modifications to scheduled tasks
// whilst still showing the state of tasks.
//
//      $CFG->preventscheduledtaskchanges = true;
//
// As of version 2.4 Moodle serves icons as SVG images if the users browser appears
// to support SVG.
// For those wanting to control the serving of SVG images the following setting can
// be defined in your config.php.
// If it is not defined then the default (browser detection) will occur.
//
// To ensure they are always used when available:
//      $CFG->svgicons = true;
//
// To ensure they are never used even when available:
//      $CFG->svgicons = false;
//
// Some administration options allow setting the path to executable files. This can
// potentially cause a security risk. Set this option to true to disable editing
// those config settings via the web. They will need to be set explicitly in the
// config.php file
//      $CFG->preventexecpath = true;
//
// Use the following flag to set userid for noreply user. If not set then moodle will
// create dummy user and use -ve value as user id.
//      $CFG->noreplyuserid = -10;
//
// As of version 2.6 Moodle supports admin to set support user. If not set, all mails
// will be sent to supportemail.
//      $CFG->supportuserid = -20;
//
// Moodle 2.7 introduces a locking api for critical tasks (e.g. cron).
// The default locking system to use is DB locking for Postgres, and file locking for
// MySQL, Oracle and SQLServer. If $CFG->preventfilelocking is set, then the default
// will always be DB locking. It can be manually set to one of the lock
// factory classes listed below, or one of your own custom classes implementing the
// \core\lock\lock_factory interface.
//
//      $CFG->lock_factory = "auto";
//
// The list of available lock factories is:
//
// "\\core\\lock\\file_lock_factory" - File locking
//      Uses lock files stored by default in the dataroot. Whether this
//      works on clusters depends on the file system used for the dataroot.
//
// "\\core\\lock\\db_record_lock_factory" - DB locking based on table rows.
//
// "\\core\\lock\\postgres_lock_factory" - DB locking based on postgres advisory locks.
//
// Settings used by the lock factories
//
// Location for lock files used by the File locking factory. This must exist
// on a shared file system that supports locking.
//      $CFG->lock_file_root = $CFG->dataroot . '/lock';
//
// Moodle 2.9 allows administrators to customise the list of supported file types.
// To add a new filetype or override the definition of an existing one, set the
// customfiletypes variable like this:
//
// $CFG->customfiletypes = array(
//     (object)array(
//         'extension' => 'frog',
//         'icon' => 'archive',
//         'type' => 'application/frog',
//         'customdescription' => 'Amphibian-related file archive'
//     )
// );
//
// The extension, icon, and type fields are required. The icon field can refer to
// any icon inside the pix/f folder. You can also set the customdescription field
// (shown above) and (for advanced use) the groups, string, and defaulticon fields.
//
// Upgrade key
//
// If the upgrade key is defined here, then the value must be provided every time
// the site is being upgraded though the web interface, regardless of whether the
// administrator is logged in or not. This prevents anonymous access to the upgrade
// screens where the real authentication and authorization mechanisms can not be
// relied on.
//
// It is strongly recommended to use a value different from your real account
// password.
//
//      $CFG->upgradekey = 'put_some_password-like_value_here';
//
//=========================================================================
// 7. SETTINGS FOR DEVELOPMENT SERVERS - not intended for production use!!!
//=========================================================================
//
// Force a debugging mode regardless the settings in the site administration
// @error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
// @ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
// $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
// $CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!
//
// You can specify a comma separated list of user ids that that always see
// debug messages, this overrides the debug flag in $CFG->debug and $CFG->debugdisplay
// for these users only.
// $CFG->debugusers = '2';
//
// Prevent theme caching
// $CFG->themedesignermode = true; // NOT FOR PRODUCTION SERVERS!
//
// Enable verbose debug information during fetching of email messages from IMAP server.
// $CFG->debugimap = true;
//
// Prevent JS caching
// $CFG->cachejs = false; // NOT FOR PRODUCTION SERVERS!
//
// Restrict which YUI logging statements are shown in the browser console.
// For details see the upstream documentation:
//   http://yuilibrary.com/yui/docs/api/classes/config.html#property_logInclude
//   http://yuilibrary.com/yui/docs/api/classes/config.html#property_logExclude
// $CFG->yuiloginclude = array(
//     'moodle-core-dock-loader' => true,
//     'moodle-course-categoryexpander' => true,
// );
// $CFG->yuilogexclude = array(
//     'moodle-core-dock' => true,
//     'moodle-core-notification' => true,
// );
//
// Set the minimum log level for YUI logging statements.
// For details see the upstream documentation:
//   http://yuilibrary.com/yui/docs/api/classes/config.html#property_logLevel
// $CFG->yuiloglevel = 'debug';
//
// Prevent core_string_manager application caching
// $CFG->langstringcache = false; // NOT FOR PRODUCTION SERVERS!
//
// When working with production data on test servers, no emails or other messages
// should ever be send to real users
// $CFG->noemailever = true;    // NOT FOR PRODUCTION SERVERS!
//
// Divert all outgoing emails to this address to test and debug emailing features
// $CFG->divertallemailsto = 'root@localhost.local'; // NOT FOR PRODUCTION SERVERS!
//
// Except for certain email addresses you want to let through for testing. Accepts
// a comma separated list of regexes.
// $CFG->divertallemailsexcept = 'tester@dev.com, fred(\+.*)?@example.com'; // NOT FOR PRODUCTION SERVERS!
//
// Uncomment if you want to allow empty comments when modifying install.xml files.
// $CFG->xmldbdisablecommentchecking = true;    // NOT FOR PRODUCTION SERVERS!
//
// Since 2.0 sql queries are not shown during upgrade by default.
// Please note that this setting may produce very long upgrade page on large sites.
// $CFG->upgradeshowsql = true; // NOT FOR PRODUCTION SERVERS!
//
// Add SQL queries to the output of cron, just before their execution
// $CFG->showcronsql = true;
//
// Force developer level debug and add debug info to the output of cron
// $CFG->showcrondebugging = true;
//
//=========================================================================
// 8. FORCED SETTINGS
//=========================================================================
// It is possible to specify normal admin settings here, the point is that
// they can not be changed through the standard admin settings pages any more.
//
// Core settings are specified directly via assignment to $CFG variable.
// Example:
//   $CFG->somecoresetting = 'value';
//
// Plugin settings have to be put into a special array.
// Example:
//   $CFG->forced_plugin_settings = array('pluginname'  => array('settingname' => 'value', 'secondsetting' => 'othervalue'),
//                                        'otherplugin' => array('mysetting' => 'myvalue', 'thesetting' => 'thevalue'));
// Module default settings with advanced/locked checkboxes can be set too. To do this, add
// an extra config with '_adv' or '_locked' as a suffix and set the value to true or false.
// Example:
//   $CFG->forced_plugin_settings = array('pluginname'  => array('settingname' => 'value', 'settingname_locked' => true, 'settingname_adv' => true));
//
//=========================================================================
// 9. PHPUNIT SUPPORT
//=========================================================================
// $CFG->phpunit_prefix = 'phpu_';
// $CFG->phpunit_dataroot = '/home/example/phpu_moodledata';
// $CFG->phpunit_directorypermissions = 02777; // optional
// $CFG->phpunit_profilingenabled = true; // optional to profile PHPUnit runs.
//
//
//=========================================================================
// 10. SECRET PASSWORD SALT
//=========================================================================
// A site-wide password salt is no longer used in new installations.
// If upgrading from 2.6 or older, keep all existing salts in config.php file.
//
// $CFG->passwordsaltmain = 'a_very_long_random_string_of_characters#@6&*1';
//
// You may also have some alternative salts to allow migration from previously
// used salts.
//
// $CFG->passwordsaltalt1 = '';
// $CFG->passwordsaltalt2 = '';
// $CFG->passwordsaltalt3 = '';
// ....
// $CFG->passwordsaltalt19 = '';
// $CFG->passwordsaltalt20 = '';
//
//
//=========================================================================
// 11. BEHAT SUPPORT
//=========================================================================
// Behat test site needs a unique www root, data directory and database prefix:
//
// $CFG->behat_wwwroot = 'http://127.0.0.1/moodle';
// $CFG->behat_prefix = 'bht_';
// $CFG->behat_dataroot = '/home/example/bht_moodledata';
//
// You can override default Moodle configuration for Behat and add your own
// params; here you can add more profiles, use different Mink drivers than Selenium...
// These params would be merged with the default Moodle behat.yml, giving priority
// to the ones specified here. The array format is YAML, following the Behat
// params hierarchy. More info: http://docs.behat.org/guides/7.config.html
// Example:
//   $CFG->behat_config = array(
//       'Mac-Firefox' => array(
//           'suites' => array (
//               'default' => array(
//                   'filters' => array(
//                      'tags' => '~@_file_upload'
//                   ),
//               ),
//           ),
//           'extensions' => array(
//               'Behat\MinkExtension' => array(
//                   'selenium2' => array(
//                       'browser' => 'firefox',
//                       'capabilities' => array(
//                           'platform' => 'OS X 10.6',
//                           'version' => 20
//                       )
//                   )
//               )
//           )
//       ),
//       'Mac-Safari' => array(
//           'extensions' => array(
//               'Behat\MinkExtension' => array(
//                   'selenium2' => array(
//                       'browser' => 'safari',
//                       'capabilities' => array(
//                           'platform' => 'OS X 10.8',
//                           'version' => 6
//                       )
//                   )
//               )
//           )
//       )
//   );
// You can also use the following config to override default Moodle configuration for Behat.
// This config is limited to default suite and will be supported in later versions.
// It will have precedence over $CFG->behat_config.
// $CFG->behat_profiles = array(
//     'phantomjs' => array(
//         'browser' => 'phantomjs',
//         'tags' => '~@_file_upload&&~@_alert&&~@_bug_phantomjs',
//         'wd_host' => 'http://127.0.0.1:4443/wd/hub',
//         'capabilities' => array(
//             'platform' => 'Linux',
//             'version' => 2.1
//         )
//     ),
// );
//
// You can force the browser session (not user's sessions) to restart after N seconds. This could
// be useful if you are using a cloud-based service with time restrictions in the browser side.
// Setting this value the browser session that Behat is using will be restarted. Set the time in
// seconds. Is not recommended to use this setting if you don't explicitly need it.
// Example:
//   $CFG->behat_restart_browser_after = 7200;     // Restarts the browser session after 2 hours
//
// All this page's extra Moodle settings are compared against a white list of allowed settings
// (the basic and behat_* ones) to avoid problems with production environments. This setting can be
// used to expand the default white list with an array of extra settings.
// Example:
//   $CFG->behat_extraallowedsettings = array('somecoresetting', ...);
//
// You should explicitly allow the usage of the deprecated behat steps, otherwise an exception will
// be thrown when using them. The setting is disabled by default.
// Example:
//   $CFG->behat_usedeprecated = true;
//
// Including feature files from directories outside the dirroot is possible if required. The setting
// requires that the running user has executable permissions on all parent directories in the paths.
// Example:
//   $CFG->behat_additionalfeatures = array('/home/developer/code/wipfeatures');
//
// You can make behat save several dumps when a scenario fails. The dumps currently saved are:
// * a dump of the DOM in it's state at the time of failure; and
// * a screenshot (JavaScript is required for the screenshot functionality, so not all browsers support this option)
// Example:
//   $CFG->behat_faildump_path = '/my/path/to/save/failure/dumps';
//
// You can specify db, selenium wd_host etc. for behat parallel run by setting following variable.
// Example:
//   $CFG->behat_parallel_run = array (
//       array (
//           'dbtype' => 'mysqli',
//           'dblibrary' => 'native',
//           'dbhost' => 'localhost',
//           'dbname' => 'moodletest',
//           'dbuser' => 'moodle',
//           'dbpass' => 'moodle',
//           'behat_prefix' => 'mdl_',
//           'wd_host' => 'http://127.0.0.1:4444/wd/hub',
//           'behat_wwwroot' => 'http://127.0.0.1/moodle',
//           'behat_dataroot' => '/home/example/bht_moodledata'
//       ),
//   );
//
// To change name of behat parallel run site, define BEHAT_PARALLEL_SITE_NAME and parallel run sites will be suffixed
// with this value
// Example:
//   define('BEHAT_PARALLEL_SITE_NAME', 'behatparallelsite');
//
// Command line output for parallel behat install is limited to 80 chars, if you are installing more then 4 sites and
// want to expand output to more then 80 chars, then define BEHAT_MAX_CMD_LINE_OUTPUT
// Example:
//   define('BEHAT_MAX_CMD_LINE_OUTPUT', 120);
//
// Behat feature files will be distributed randomly between the processes by default. If you have timing file or want
// to create timing file then define BEHAT_FEATURE_TIMING_FILE with path to timing file. It will be updated for each
// run with latest time taken to execute feature.
// Example:
//   define('BEHAT_FEATURE_TIMING_FILE', '/PATH_TO_TIMING_FILE/timing.json');
//
// If you don't have timing file and want some stable distribution of features, then you can use step counts to
// distribute the features. You can generate step file by executing php admin/tool/behat/cli/util.php --updatesteps
// this will update step file which is defined by BEHAT_FEATURE_STEP_FILE.
// Example:
//   define('BEHAT_FEATURE_STEP_FILE', '/PATH_TO_FEATURE_STEP_COUNT_FILE/stepcount.json');
//
// Feature distribution for each process is displayed as histogram. you can disable it by setting
// BEHAT_DISABLE_HISTOGRAM
// Example:
//   define('BEHAT_DISABLE_HISTOGRAM', true);
//
//=========================================================================
// 12. DEVELOPER DATA GENERATOR
//=========================================================================
//
// The developer data generator tool is intended to be used only in development or testing sites and
// it's usage in production environments is not recommended; if it is used to create JMeter test plans
// is even less recommended as JMeter needs to log in as site course users. JMeter needs to know the
// users passwords but would be dangerous to have a default password as everybody would know it, which would
// be specially dangerouse if somebody uses this tool in a production site, so in order to prevent unintended
// uses of the tool and undesired accesses as well, is compulsory to set a password for the users
// generated by this tool, but only in case you want to generate a JMeter test. The value should be a string.
// Example:
//   $CFG->tool_generator_users_password = 'examplepassword';
//
//=========================================================================
// 13. SYSTEM PATHS (You need to set following, depending on your system)
//=========================================================================
// Ghostscript path.
// On most Linux installs, this can be left as '/usr/bin/gs'.
// On Windows it will be something like 'c:\gs\bin\gswin32c.exe' (make sure
// there are no spaces in the path - if necessary copy the files 'gswin32c.exe'
// and 'gsdll32.dll' to a new folder without a space in the path)
//      $CFG->pathtogs = '/usr/bin/gs';
//
// Path to du.
// Probably something like /usr/bin/du. If you enter this, pages that display
// directory contents will run much faster for directories with a lot of files.
//      $CFG->pathtodu = '';
//
// Path to aspell.
// To use spell-checking within the editor, you MUST have aspell 0.50 or later
// installed on your server, and you must specify the correct path to access the
// aspell binary. On Unix/Linux systems, this path is usually /usr/bin/aspell,
// but it might be something else.
//      $CFG->aspellpath = '';
//
// Path to dot.
// Probably something like /usr/bin/dot. To be able to generate graphics from
// DOT files, you must have installed the dot executable and point to it here.
// Note that, for now, this only used by the profiling features
// (Development->Profiling) built into Moodle.
//      $CFG->pathtodot = '';
//
// Path to unoconv.
// Probably something like /usr/bin/unoconv. Used as a fallback to convert between document formats.
// Unoconv is used convert between file formats supported by LibreOffice.
// Use a recent version of unoconv ( >= 0.7 ), older versions have trouble running from a webserver.
//      $CFG->pathtounoconv = '';
//
//=========================================================================
// 14. ALTERNATIVE FILE SYSTEM SETTINGS
//=========================================================================
//
// Alternative file system.
// Since 3.3 it is possible to override file_storage and file_system API and use alternative storage systems (e.g. S3,
// Rackspace Cloud Files, Google Cloud Storage, Azure Storage, etc.).
// To set the alternative file storage system in config.php you can use the following setting, providing the
// alternative system class name that will be auto-loaded by file_storage API.
//
//      $CFG->alternative_file_system_class = '\\local_myfilestorage\\file_system';
//
//=========================================================================
// ALL DONE!  To continue installation, visit your main page with a browser
//=========================================================================

require_once(__DIR__ . '/lib/setup.php'); // Do not edit

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
