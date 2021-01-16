Bennu - PHP iCalendar library
=============================

Bennu is an object-oriented library written in PHP that implements the
iCalendar standard (RFC 2445). It is easy to use, fully standards compliant,
and powerful. Applications can use Bennu to read and write iCalendar files,
making them compatible with programs like Microsoft Outlook, Apple iCal, and
Mozilla Sunbird.

Information
-----------

* Bennu version: based on upstream 0.1 from 2005, heavily customized since then
* Licence: GNU LGPL 2.1
* WWW: http://bennu.sourceforge.net/

Changelog
---------

1/ removed ereg functions deprecated as of php 5.3 (18 Nov 2009)
2/ replaced mbstring functions with moodle core_text (28 Nov 2011)
3/ replaced explode in iCalendar_component::unserialize() with preg_split to support various line breaks (20 Nov 2012)
4/ updated rfc2445_is_valid_value() to accept single part rrule as a valid value (16 Jun 2014)
5/ updated DTEND;TZID and DTSTAR;TZID values to support quotations (7 Nov 2014)
6/ MDL-49032: fixed rfc2445_fold() to fix incorrect RFC2445_WSP definition (16 Sep 2015)
7/ added timestamp_to_date function to support zero duration events (16 Sept 2015)
8/ Updated \iCalendar_event::invariant_holds() to allow for same dtstart and dtend timestamps (13 July 2017)
9/ MDL-60391: replace create_function() with lambda function for PHP 7.2 compatibility (13 Oct 2017)