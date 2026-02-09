<?php
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language file.
 *
 * @package   theme_iomadmoon
 * @copyright 2022 - 2023 Marcin Czaja (https://rosea.io)
 * @license   Commercial https://themeforest.net/licenses
 */

defined('MOODLE_INTERNAL') || die();

$string['bootswatch'] = 'Bootswatch';
$string['bootswatch_desc'] = 'A bootswatch is a set of Bootstrap variables and css to style Bootstrap';
$string['choosereadme'] = '<div class="rui-block-testimonials-item mt-2 mb-4"><div class="rui-block-testimonials--quote w-100 mb-0">"Good design is as little design as possible"</div><p class="rui-block-testimonials--author ml-2">Dieter Rams</p></div>
<p class="small ml-2">Premium Moodle Theme designed by RoseaThemes.</p>
<div class="mt-4 ml-2"><h4>Support</h4><p>Need help with theme customization?<br />or you want to report a bug?</p></div><a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/" target="_blank" class="btn btn-sm btn-primary m-2">Online Documentation</a><a href="https://roseathemes.ticksy.com" target="_blank" class="btn btn-sm btn-secondary m-2">Dedicated ticket system</a>';
$string['currentinparentheses'] = '(current)';
$string['configtitle'] = 'iomadmoon';
$string['hgeneralnav'] = 'Main navgation items';
$string['hgeneralnav_desc'] = 'Here you can turn off elements of the main navigation.';
$string['generalsettings'] = 'General settings';

$string['nobootswatch'] = 'None';
$string['pluginname'] = 'Moon for IOMAD (1.5.2)';
$string['privacy:metadata'] = 'The Moon theme does not store any personal data about any user.';
$string['rawscss'] = 'Raw SCSS';
$string['rawscss_desc'] = 'Use this field to provide SCSS or CSS code which will be injected at the end of the style sheet.';
$string['rawscsspre'] = 'Raw initial SCSS';
$string['rawscsspre_desc'] = 'In this field you can provide initialising SCSS code, it will be injected before everything else. Most of the time you will use this setting to define variables.';

$string['empty_desc'] = '';

// Navigation improvements.
$string['myactivecourses'] = 'My active courses';
$string['coursesections'] = 'Course sections';

// Sidebar.
$string['settingssidebar-nav'] = 'Sidebar - Navigation';
$string['settingssidebar'] = 'Sidebar';
$string['hcustomsidebarlogo'] = 'Sidebar Logo';
$string['hcustomsidebarlogo_desc'] = '';
$string['hcustomcontent'] = 'Custom Content';
$string['hcustomcontent_desc'] = '';
$string['customsidebarlogo'] = 'Sidebar Logo';
$string['customsidebarlogo_desc'] = '';
$string['customsidebardmlogo'] = 'Sidebar Logo <span class="badge badge-xs badge-dark mx-2">Dark Mode</span>';
$string['customsidebardmlogo_desc'] = 'Additional logo for dark mode e.g white version';
$string['customstcontent'] = 'Custom Text (Top)';
$string['customstcontent_desc'] = 'Custom content between logo and main navigation.';
$string['customsmcontent'] = 'Custom Text (Middle)';
$string['customsmcontent_desc'] = 'Custom content between main navigation and "My Courses"';

$string['hcourseindexdrawer'] = 'Course Index Drawer';
$string['hcourseindexdrawer_desc'] = '';
$string['customcitcontent'] = 'Course Index Drawer (Custom HTML Area - Top)';
$string['customcitcontent_desc'] = '';
$string['customcibcontent'] = 'Course Index Drawer (Custom HTML Area - Bottom)';
$string['customcibcontent_desc'] = '';

$string['customnavitems'] = 'Custom Navigation Items';
$string['customnavitems_desc'] = 'You can add additional navigation item or custom HTML content. Down below you can find a sample code snippet of the navigation item.
<pre id="pre--1" class="rui-pre">&lt;li class="rui-sidebar-nav-item" id="yui_3_17_2_1_1659561578352_26557"&gt;&lt;a href="#" id="itemContentBank" class="rui-sidebar-nav-item-link "&gt;&lt;span class="rui-sidebar-nav-icon"&gt;&lt;svg width="20" height="20" fill="none" viewBox="0 0 24 24"&gt;&lt;path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.25 17.25V9.75C19.25 8.64543 18.3546 7.75 17.25 7.75H4.75V17.25C4.75 18.3546 5.64543 19.25 6.75 19.25H17.25C18.3546 19.25 19.25 18.3546 19.25 17.25Z"&gt;&lt;/path&gt;&lt;path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 7.5L12.5685 5.7923C12.2181 5.14977 11.5446 4.75 10.8127 4.75H6.75C5.64543 4.75 4.75 5.64543 4.75 6.75V11"&gt;&lt;/path&gt;&lt;/svg&gt;&lt;/span&gt;&lt;span class="rui-sidebar-nav-text"&gt;Content bank&lt;/span&gt;&lt;/a&gt;&lt;/li&gt;</pre>
SVG icons:
<ul>
<li><a href="https://iconic.app/" target="_blank">iconic</a></li>
<li><a href="https://fontawesome.com/icons" target="_blank">FontAwesome</a></li>
<li><a href="https://css.gg/" target="_blank">css.gg</a></li>
<li><a href="https://coolicons.cool/" target="_blank">coolicons</a></li>
</ul>';
$string['customsfcontent'] = 'Custom Text (Bottom)';
$string['customsfcontent_desc'] = 'Custom content under "My Courses" section. <br /><div class="mt-3 mb-2">Sample code snippet <span class="badge badge-sq badge-warning">remember to switch Atto editor to HTML view</span></div><pre class="rui-pre">&#x3C;hr class=&#x22;hr-small&#x22;&#x3E;
&#x3C;div class=&#x22;ml-2&#x22;&#x3E;
    &#x3C;span class=&#x22;d-inline-flex mb-2&#x22;&#x3E;
        &#x3C;span class=&#x22;mr-2 ml-1&#x22;&#x3E;&#x3C;svg width=&#x22;28&#x22; height=&#x22;28&#x22; fill=&#x22;none&#x22; viewBox=&#x22;0 0 24 24&#x22;&#x3E;
                &#x3C;path stroke=&#x22;currentColor&#x22; stroke-linecap=&#x22;round&#x22; stroke-linejoin=&#x22;round&#x22; stroke-width=&#x22;1.5&#x22; d=&#x22;M6.75 6.75C6.75 5.64543 7.64543 4.75 8.75 4.75H15.25C16.3546 4.75 17.25 5.64543 17.25 6.75V19.25L12 14.75L6.75 19.25V6.75Z&#x22;&#x3E;&#x3C;/path&#x3E;
            &#x3C;/svg&#x3E;&#x3C;/span&#x3E;
        &#x3C;p class=&#x22;rui-block-text rui-block-text--2&#x22;&#x3E;Learn more about the theme&#x3C;br&#x3E;&#x3C;a href=&#x22;#&#x22; target=&#x22;_blank&#x22;&#x3E;Documentation page&#x3C;/a&#x3E;.
        &#x3C;/p&#x3E;
    &#x3C;/span&#x3E;
    &#x3C;div class=&#x22;ml-5&#x22;&#x3E;
        &#x3C;p class=&#x22;rui-block-text--light rui-block-text--3&#x22;&#x3E;Space (2.5.13) is fully compatible with moodle 4.0 and later. Theme is not compatible with Space 1.x.&#x3C;/p&#x3E;&#x3C;a href=&#x22;#&#x22; class=&#x22;mt-2 rui-block-text--3&#x22; target=&#x22;_blank&#x22;&#x3E;Get this theme for just $99&#x3C;/a&#x3E;
    &#x3C;/div&#x3E;
&#x3C;/div&#x3E;</pre>';
$string['turnoffsidebarfp'] = 'Turn off the sidebar (Front Page)';
$string['turnoffsidebarfp_desc'] = '';
$string['turnoffsidebardashboard'] = 'Turn off the sidebar (Dashboard)';
$string['turnoffsidebardashboard_desc'] = '';
$string['turnoffsidebarcourse'] = 'Turn off the sidebar (Course Page)';
$string['turnoffsidebarcourse_desc'] = '';
$string['turnoffsidebarincourse'] = 'Turn off the sidebar (In-course Page)';
$string['turnoffsidebarincourse_desc'] = '';
$string['turnoffsidebarreport'] = 'Turn off the sidebar (Report Page)';
$string['turnoffsidebarreport_desc'] = '';
$string['turnoffsidebarstandard'] = 'Turn off the sidebar (Standard Page)';
$string['turnoffsidebarstandard_desc'] = 'e.g Calendar, Private Files';
$string['turnoffsidebaradmin'] = 'Turn off the sidebar (Admin Page)';
$string['turnoffsidebaradmin_desc'] = '';
$string['hturnoffsidebar'] = 'Turn off the sidebar';
$string['hturnoffsidebar_desc'] = 'Customize the sidebar visibility on particular pages.';
$string['hsidebarcolors'] = 'Sidebar Color Customization';
$string['hsidebarcolors_desc'] = '';
$string['colordrawerbg'] = 'Sidebar Background';
$string['colordrawertext'] = 'Sidebar Text';
$string['colordrawernavcontainer'] = 'Sidebar Navigation Box';
$string['colordrawernavbtntext'] = 'Sidebar Button Text';
$string['colordrawernavbtnicon'] = 'Sidebar Button Icon';
$string['colordrawernavbtniconh'] = 'Sidebar Button Icon (Hover)';
$string['colordrawernavbtntexth'] = 'Sidebar Button Text (Hover)';
$string['colordrawernavbtnbgh'] = 'Sidebar Button Background (Hover/Active)';
$string['colordrawernavbtntextlight'] = 'Sidebar Button Text (Light)';
$string['colordrawerscrollbar'] = 'Sidebar Scrollbar Color';
$string['colordrawerlink'] = 'Sidebar Link Color';
$string['colordrawerlinkh'] = 'Sidebar Link Color (Hover)';

// Privacy.
$string['privacy:metadata:preference:sidebaropen'] = 'The user\'s preference for hiding or showing the right sidebar.';
$string['privacy:metadata:preference:darkmodeon'] = 'The user\'s preference for dark mode';
$string['privacy:rightdrawerclosed'] = 'The current preference for the navigation drawer is closed.';
$string['privacy:rightdraweropen'] = 'The current preference for the navigation drawer is open.';
$string['privacy:darkmodeoff'] = 'The current preference for the dark mode is off.';
$string['privacy:darkmodeon'] = 'The current preference for the dark mode is on.';
$string['privacy:metadata:preference:draweropennav'] = 'The user\'s preference for hiding or showing the drawer menu navigation.';
$string['privacy:drawernavclosed'] = 'The current preference for the navigation drawer is closed.';
$string['privacy:drawernavopen'] = 'The current preference for the navigation drawer is open.';

$string['totop'] = 'Go to top';

$string['region-fpblockst'] = 'Frontpage Blocks Area #1';
$string['region-fpblocksb'] = 'Frontpage Blocks Area #2';
$string['region-side-pre'] = 'Hidden Right Sidebar';
$string['region-dtopblocks'] = 'Dashboard Top Blocks';
$string['region-dbottomblocks'] = 'Dashboard Bottom Blocks';
$string['region-dleftblocks'] = 'Dashboard Left Blocks';
$string['region-dmiddleblocks'] = 'Dashboard Middle Blocks';
$string['region-drightblocks'] = 'Dashboard Right Blocks';
$string['region-sidecourseblocks'] = 'Right Sidebar Blocks';
$string['region-ctopbl'] = 'Course - Top Blocks';
$string['region-cbottombl'] = 'Course - Bottom Blocks';
$string['region-cstopbl'] = 'Course Sections - Top Blocks';
$string['region-csbottombl'] = 'Course Sections - Bottom Blocks';
$string['region-sidebartb'] = 'Sidebar - Top Blocks';
$string['region-sidebarbb'] = 'Sidebar - Bottom Blocks';

$string['hidenodesprimarynavigationsetting'] = 'Hide nodes in primary navigation';
$string['hidenodesprimarynavigationsetting_desc'] = 'With this setting, you can hide one or multiple nodes from the primary navigation.';

$string['topbareditmode'] = 'Edit mode button on the top bar';
$string['topbareditmode_desc'] = '<img src="{$a->siteurl}/theme/iomadmoon/doc/topbar-editmode.png" class="mt-2 rounded" style="max-height: 100px; width: auto; max-width: 100%;" alt="Edit mode" />';

$string['topbargradient'] = 'Tob Bar Gradient';
$string['topbargradient_desc'] = 'Optional, gradient background. Gradient colors depends on company branding colors.';

$string['turnoffdashboardlink'] = 'Turn off Dashboard Link (User Menu)';
$string['turnoffdashboardlink_desc'] = '<p>You can turn off Dashboard Link inside the User menu.</p><img src="{$a->siteurl}/theme/iomadmoon/doc/user-menu-dashboard.png" class="mt-2 rounded" style="max-height: 300px; width: auto; max-width: 100%;" alt="Dashboard Link" />';
$string['darkmodetheme'] = 'Dark Mode Switcher';
$string['darkmodetheme_desc'] = '';
$string['darkmodefirst'] = 'Only Dark Mode UI';
$string['darkmodefirst_desc'] = 'Force Dark Interface Only. When enabled the Dark Mode Switcher will not be displayed.';
$string['sdarkmode'] = 'Dark Mode (Button Title/Tooltip)';
$string['sdarkmode_desc'] = '<span class="badge-sq badge-info mb-2">FAQ</span> <ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['slightmode'] = 'Light Mode (Button Title/Tooltip)';
$string['slightmode_desc'] = '<span class="badge-sq badge-info mb-2">FAQ</span> <ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';

$string['themeauthor'] = 'Theme Author';
$string['themeauthor_desc'] = 'Show information about the author of the theme - in the source code.';

$string['thiscourse'] = 'Course Sections';
$string['nothiscourse'] = 'We cannot identify any course sections or topics';

// Edit Button Text.
$string['editon'] = 'Turn Edit On';
$string['editoff'] = 'Turn Edit Off';
$string['left'] = 'Left';
$string['center'] = 'Center';
$string['right'] = 'Right';


// Custom Alert.
$string['alertsettings'] = 'Custom Alert';
$string['displaycustomalert'] = 'Display Custom Alert';
$string['displaycustomalert_desc'] = '';
$string['closecustomalert'] = 'Close Custom Alert Permanently';
$string['closecustomalert_desc'] = '';
$string['customalerthtml'] = 'Custom Alert Content';
$string['customalerthtml_desc'] = '';
$string['customalertid'] = 'Custom Alert ID';
$string['customalertid_desc'] = 'Change the ID to show the alert again.';

// SEO.
$string['seosettings'] = 'SEO';
$string['seometadesc'] = 'Meta Description';
$string['seometadesc_desc'] = '';
$string['seoappletouchicon'] = 'Apple Touch Icon';
$string['seoappletouchicon_desc'] = '';
$string['seothemecolor'] = 'SEO Theme Color';
$string['seothemecolor_desc'] = 'The theme-color value for the name attribute of the <meta> element indicates a suggested color that user agents should use to customize the display of the page or of the surrounding user interface. <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/meta/name/theme-color" target="_blank">Learn more about theme-color tag</a>';
$string['seomanifestjson'] = 'Manifest JSON';
$string['seomanifestjson_desc'] = '';

// Course Card.
$string['cccteachers'] = 'Display teachers section (Globaly)';
$string['cccteachers_desc'] = 'Display teachers list on the main course page and on the cards (course/index.php)';

$string['courselistview'] = 'Simple list (Course List)';
$string['courselistview_desc'] = 'Display a simple list instead default course cards. Only on the /course/index.php<br />
<div class="w-100 mt-2"><img src="{$a->siteurl}/theme/iomadmoon/doc/course-simple-list.png" class="img-fluid rounded w-100" style="max-width: 500px;" alt="Space Moodle Theme" /></div>';

$string['cccsummary'] = 'Display Course Summary';
$string['cccsummary_desc'] = '';

$string['cccfooter'] = 'Display "Get access" buttons';
$string['cccfooter_desc'] = '';

$string['stringaccess'] = 'Get access';
$string['stringaccess_desc'] = '';

$string['cccdteachers'] = 'Show Teachers Section (Avatars)';
$string['cccdteachers_desc'] = '';

$string['cccdfooter'] = 'Show Course Card Footer <small>(Get access)</small>';
$string['cccdfooter_desc'] = '';

$string['coursecarddesclimit'] = 'Course Card Description - Text Limit';
$string['coursecarddesclimit_desc'] = '<span class="badge-sm badge-secondary"><strong class="mr-1">Example (text length - letters):</strong> 120</span>';

$string['maxcoursecardtextheight'] = 'Course Card Description - Max Height';
$string['maxcoursecardtextheight_desc'] = '<span class="badge-sm badge-secondary"><strong class="mr-1">Default:</strong> 127px</span>';

// Course Index Page.
$string['ipcoursesummary'] = 'Display course summary (Main Course Page)';
$string['ipcoursesummary_desc'] = '';

$string['courselangbadge'] = 'Course Language Badge';
$string['courselangbadge_desc'] = '<p>(Course - Settings - Appearance - Force Language)</p><img src="{$a->siteurl}/theme/iomadmoon/doc/course-lang-badge.jpg" class="img-fluid mt-2 rounded" width="100%" alt="Course Simple List" />';

// Headings.
$string['hlogin'] = 'Login Page';
$string['hlogin_desc'] = 'Login page customization. You can select three layouts, add background and more.';
$string['hsignup'] = 'Sign up Page';
$string['hsignup_desc'] = 'Customization of the sign up page.';
$string['hcoursecard'] = 'Course Card';
$string['hcoursecard_desc'] = 'Customize course card e.g Get access label.';
$string['loginidprovtop'] = 'Display list of identity providers before the login form';
$string['loginidprovtop_desc'] = '';

// Settings - Advanced.
$string['advancedsettings'] = 'Advanced settings';
$string['googleanalytics'] = 'Google Analytics V4 Code';
$string['googleanalyticsdesc'] = 'Please enter your Google Analytics V4 code to enable analytics on your website. The code format should be like [G-XXXXXXXXXX].<br />To meet all GPDR rules, the code will be visible only for logged-in users. Don\'t forget to add a cookie popup with information about cookie collecting.';
$string['favicon'] = 'Custom favicon';
$string['favicon_desc'] = 'Upload your own favicon. It should be an <strong>.ico</strong> file. <a href="https://www.favicon-generator.org/" target="_blank">Favicon generator #1</a>';
$string['favicon16'] = 'Custom favicon (16x16)';
$string['favicon32'] = 'Custom favicon (32x32)';
$string['favicon32'] = 'Custom favicon (32x32)';
$string['faviconsafaritab'] = 'Safari Pinned Tab (svg)';
$string['faviconsafaritabcolor'] = 'Safari Pinned Tab (color)';
$string['fontfilessetting'] = 'Font files';
$string['fontfilessetting_desc'] = 'With this dialogue you can upload own font files. The uplaod is resricted to the font files of type .eot, .woff, .woff2, .ttf and .svg. <br/>
<br /><h5>Step by step: <a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/theme-settings/custom-fonts" target="_blank" class="badge badge-xs badge-info">Documentation <svg class="ml-2" width="12" height="12" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.75 6.75L19.25 12L13.75 17.25"></path><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H4.75"></path></svg></a></h5>
<ol>
<li>Convert Google Font Files to: *.eot, *.woff, .*woff2, *.svg. <br /><a href="https://cloudconvert.com/ttf-to-woff" target="_blank">Online Font Converter</a></li>
<li>To be able to use the uploaded fonts within this theme, you have to add related code to your "Raw SCSS" area in the tab "Advanced Tab".</li>
<li>HTML code to add (Advanced - Raw SCSS) <a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/theme-settings/custom-fonts" class="badge badge-xs badge-info" target="_blank">Code snippet <svg class="ml-2" width="12" height="12" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.75 6.75L19.25 12L13.75 17.25"></path><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H4.75"></path></svg></a></li>
</ol>';

// Settings - Login page.
$string['settingslogin'] = 'Login Page';
$string['setloginlayout'] = 'Login Page Image Position';
$string['setloginlayout_desc'] = '';
$string['loginlayout1'] = '#1 - Box - Background image (middle)';
$string['loginlayout2'] = '#2 - Box - Image on the right (middle)';
$string['loginlayout3'] = '#3 - Box - Image on the left (middle)';
$string['loginlayout4'] = '#4 - Full height (left)';
$string['loginlayout5'] = '#5 - Full height (right)';
$string['loginhtmlcontent1'] = 'HTML Content #1';
$string['loginhtmlcontent1_desc'] = 'Custom HTML content<br />
<ul>
<li>for layout #1: before the logo</li>
<li>for layout #2, #3: before the login box</li>
</ul>';
$string['loginhtmlcontent2'] = 'HTML Content #2';
$string['loginhtmlcontent2_desc'] = 'Custom HTML content<br />
<ul>
<li>for layout #1: after HTML content #2</li>
<li>for layout #2, #3: next to the login form</li>
</ul>';
$string['loginhtmlcontent3'] = 'HTML Content #3';
$string['loginhtmlcontent3_desc'] = 'Custom HTML content<br />
<ul>
<li>for layout #1: after the login form</li>
<li>for layout #2, #3: under the login box</li>
</ul>';
$string['logincustomfooterhtml'] = 'Custom Footer HTML or JS';
$string['logincustomfooterhtml_desc'] = '';
$string['loginhtmlblockbottom'] = 'Custom HTML Block (under the login form)';
$string['loginhtmlblockbottom_desc'] = '';
$string['loginfootercontent'] = 'Footer Content';
$string['loginfootercontent_desc'] = 'Custom HTML content<br />
<ul>
<li>for layout #1: after the login form and HTML Content #2</li>
<li>for layout #2, #3: under the login box</li>
</ul>';
$string['loginbg'] = 'Login Page Background';
$string['loginbg_desc'] = '';
$string['loginbgcolor'] = 'Login Background Color (Image Container)';
$string['loginbgcolor_desc'] = '';
$string['customloginlogo'] = 'Custom Logo on the Login Page';
$string['customloginlogo_desc'] = '<strong>Recommendation:</strong> SVG files or png files with transparent background.';
$string['customlogindmlogo'] = 'Custom Logo on the Login Page <span class="badge badge-xs badge-dark mx-2">Dark Mode</span>';
$string['customlogindmlogo_desc'] = '<strong>Recommendation:</strong> SVG files or png files with transparent background.';
$string['colorloginbgtext'] = 'Text Color';
$string['colorloginbgtext_desc'] = 'Color of the text like "Create an account"';
$string['loginintrotext'] = 'Log in - Introduction';
$string['loginintrotext_desc'] = '';
$string['stringca'] = 'Label next to "Create an account" button.';
$string['stringca_desc'] = 'Label displays on the Sign in page';
$string['stringbacktologin'] = 'Label next to "Log in"';
$string['stringbacktologin_desc'] = 'Label displays on the Sign up page';
$string['signuptext'] = 'Sign up Content';
$string['signuptext_desc'] = '';
$string['signupintrotext'] = 'Sign up - Introduction';
$string['signupintrotext_desc'] = '';
$string['loginlogooutside'] = 'Logo outside the container';
$string['loginlogooutside_desc'] = '';
$string['customsignupoutside'] = 'Sign up link outside the container';
$string['customsignupoutside_desc'] = 'Display the sign-up link on the right top corner. If disabled, the sign-up button will be displayed under the log-in button.';
$string['hideforgotpassword'] = 'Hide remember password link';
$string['hideforgotpassword_desc'] = '';
$string['logininfobox'] = 'Additional content under the password input field.';
$string['logininfobox_desc'] = 'Code snippet:<pre class="rui-pre"><code>&#x3C;div class=&#x22;mt-3&#x22;&#x3E;&#x3C;p class=&#x22;small&#x22;&#x3E;Sample text&#x3C;/p&#x3E;&#x3C;/div&#x3E;</code></pre>
<a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/theme-settings/login-page#available-content-fields-areas" class="btn btn-sm btn-success" target="_blank">Documentation - Login Page Customization</a>';

// Repeatable.
$string['none'] = 'None';
$string['haccordionend'] = '';
$string['haccordionend_desc'] = '';
$string['blockintrotitle'] = 'Intro Title';
$string['blockintrotitle_desc'] = '';
$string['blockintrocontent'] = 'Intro Content';
$string['blockintrocontent_desc'] = '';
$string['blockhtmlcontent'] = 'HTML Content';
$string['blockhtmlcontent_desc'] = '<a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/theme-settings/front-page-blocks" target="_blank" class="btn btn-sm btn-info">HTML Code Snippets Library</a>';
$string['blockfootercontent'] = 'Footer Content';
$string['blockfootercontent_desc'] = '';
$string['turnon'] = 'Turn on';
$string['turnon_desc'] = '';

// Settings - Course page.
$string['settingscourses'] = 'Course Page';
$string['setcourseimage'] = 'Course image (Main Course Page)';
$string['yes'] = 'Yes';
$string['courseimagecontent'] = 'Inside the content';
$string['courseprogressbar'] = 'Display Course Progress Bar (Globaly)';
$string['courseprogressbar_desc'] = 'Course progress bar displays on the course index sidebar<br /><div class="w-100 mt-2"><img src="{$a->siteurl}/theme/iomadmoon/doc/course-progress.png" class="img-fluid rounded w-100" style="max-width: 500px;" alt="IOMAD Premium Moodle Theme" /></div>';

// Settings - Top Bar.
$string['settingstopbar'] = 'Top Bar';
$string['topbarheight'] = 'Custom Top Bar Height (e.g 59px)';
$string['topbarheight_desc'] = 'You can either use the default value or specify your own height. The padding will be automatically generated.';
$string['showmycoursesbox'] = 'Show My Courses Box';
$string['showmycoursesbox_desc'] = 'Turn on/off "My Courses" box on the left sidebar';
$string['hmycoursesbtn'] = 'My Courses';
$string['hmycoursesbtn_desc'] = 'Customize the sidebar "My Courses" area.';
$string['mycourseswrapperheight'] = 'My Courses Wrapper Height (px)';
$string['mycourseswrapperheight_desc'] = '';
$string['stringmycourses'] = 'My Courses';
$string['stringmycourses_desc'] = '<ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['hidedetails'] = 'Hide Details Link';
$string['hidedetails_desc'] = '';
$string['stringdetails'] = 'Details';
$string['stringdetails_desc'] = '<p>Link to My Courses Page.</p><div class="mt-3 mb-2">Sample code snippet</div><pre class="rui-pre">&lt;span&gt;My courses overview&lt;/span&gt;&lt;svg width="18" height="18" fill="none" viewBox="0 0 24 24"&gt;
&lt;path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.75 6.75L19.25 12L13.75 17.25"&gt;&lt;/path&gt;
&lt;path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 12H4.75"&gt;&lt;/path&gt;
&lt;/svg&gt;</pre><ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['stringallcourses'] = 'List of all available courses';
$string['stringallcourses_desc'] = 'Leave this field empty if you want to hide this button.
<br />This button depends on user permissions - moodle/category:viewcourselist.
<br />From \'Site administration / Users / Permissions / Define roles\'
<a href="https://docs.moodle.org/400/en/Course_list_viewer_role" target="_blank">Learn more about the course list viewer role</a>
<ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['stringnocourses'] = 'You are not enrolled in any courses.';
$string['stringnocourses_desc'] = '<ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['topbarcustomhtml'] = 'Custom HTML Area';
$string['topbarcustomhtml_desc'] = '
<br /><img src="{$a->siteurl}/theme/iomadmoon/doc/topbar-custom-html.png" class="img-fluid rounded my-3" style="max-height: 100px; width: auto; max-width: 100%;" alt="Space Moodle Theme" />
<div class="mt-3 mb-2">Sample code snippet
<span class="badge badge-sq badge-warning">remember to switch Atto editor to HTML view</span></div>
<pre class="rui-pre">&lt;div class=&quot;d-inline-flex align-items-center&quot;&gt;
&lt;p&gt;Space (2.5.18) is here!&lt;br&gt;&lt;a href=&quot;#&quot; target=&quot;_blank&quot;&gt;Get this theme today!&lt;/a&gt;
&lt;/p&gt;
&lt;/div&gt;</pre>';

$string['stringshowhidden'] = 'Show hidden courses';
$string['stringshowhidden_desc'] = 'For users who get permission to see hidden courses.<ul class="mt-2"><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';
$string['stringshowonlyinprogress'] = 'Only courses in progress';
$string['stringshowonlyinprogress_desc'] = '<ul><li><span>Displaying text in multiple languages </span><a class="underline--anim" href="https://docs.moodle.org/400/en/Multi-language_content_filter" target="_blank">Learn more</a></li></ul>';

$string['htopbarcolors'] = 'Colors Customization';
$string['htopbarcolors_desc'] = 'Options for color customization.';
$string['colortopbarbg'] = 'Topbar Background';
$string['colortopbartext'] = 'Text';
$string['colortopbarlink'] = 'Link Color';
$string['colortopbarlink_desc'] = 'Link color inside "Custom HTML Area"';
$string['colortopbarlinkhover'] = 'Link (Hover)';
$string['colortopbarbtn'] = 'Button Background';
$string['colortopbarbtntext'] = 'Button Text';
$string['colortopbarbtnhover'] = 'Button Background Hover';
$string['colortopbarbtnhovertext'] = 'Button Hover Text';

// Settings - Dashboard.
$string['settingsdashboard'] = 'Dashboard';
$string['setdashboardlayout'] = 'Dashboard Page Layout';
$string['setdashboardlayout_desc'] = '<img src="{$a->siteurl}/theme/iomadmoon/doc/dashboard-layout.jpg" class="img-fluid rounded my-3 w-100" alt="space Moodle Theme" />';
$string['dashboardlayout1'] = 'Layout #1';
$string['dashboardlayout2'] = 'Layout #2';
$string['dashboardlayout3'] = 'Layout #3';
$string['customdcolsize'] = 'Custom Dashboard Column Width (Sidebar Left/Right)';
$string['customdcolsize_desc'] = '<span class="mb-2 badge-sq badge-danger">Only for developers</span><br />Default size: 320px.<br />Works only with layout #2, #3<br />You can use: col-lg (50%), col-lg-3 (33%), col-lg-4 (25%) or add own custom class and custom CSS to Advanced - Raw SCSS';

$string['dashboardblock1'] = 'Dashboard HTML Block #1 (On the top)';
$string['dashboardblock1_desc'] = '';
$string['dashboardblock2'] = 'Dashboard HTML Block #2 (On the bottom)';
$string['dashboardblock2_desc'] = '';

$string['settingsmycourses'] = 'My Courses';
$string['mycoursesblock1'] = 'My Courses HTML Block #1 (On the top)';
$string['mycoursesblock1_desc'] = '';
$string['mycoursesblock2'] = 'My Courses HTML Block #2 (On the bottom)';
$string['mycoursesblock2_desc'] = '';

// Settings - Footer.
$string['settingsfooter'] = 'Footer';
$string['footercenter'] = 'Center Alignment for Footer';
$string['footercenter_desc'] = 'All footer elements will be centered instead of aligned to left or right (in RTL layouts).';
$string['footerblock1'] = 'Footer Block #1';
$string['footerblock1_desc'] = '';
$string['footerblock2'] = 'Footer Block #2';
$string['footerblock2_desc'] = '';
$string['footerblock3'] = 'Footer Block #3';
$string['footerblock3_desc'] = '';
$string['footercopy'] = 'Footer Copy';
$string['footercopy_desc'] = '';
$string['hfootercolors'] = 'Footer Color Customization';
$string['hfootercolors_desc'] = '';
$string['colorfooterbg'] = 'Footer Background Color';
$string['colorfootertext'] = 'Footer Text Color';
$string['colorfooterlink'] = 'Footer Link Color';
$string['colorfooterlinkhover'] = 'Footer Link Hover Color';
$string['colorfooterborder'] = 'Footer Border Color';
$string['footerbgimg'] = 'Footer Background (Image)';
$string['footerbgimg_desc'] = 'Footer Background (Image)';
$string['footercustomcss'] = 'Footer Custom CSS';
$string['footercustomcss_desc'] = 'Customize background via custom CSS. <a href="https://css-tricks.com/almanac/properties/b/background/" target="_blank">Learn more about CSS background properties</a>';
$string['showfooterbuttons'] = 'Display Footer Buttons';
$string['showfooterbuttons_desc'] = 'Default Moodle buttons on the footer like: Moodle Doc, Data retention summary, etc.';
$string['hfooterblocks'] = 'Footer Custom Blocks';
$string['hfooterblocks_desc'] = '';
$string['hfootersocial'] = 'Footer Social Icons';
$string['hfootersocial_desc'] = '';
$string['showsociallist'] = 'Display footer social list';
$string['showsociallist_desc'] = 'Check this field to show up social icons list.';
$string['website'] = 'Website Title';
$string['website_desc'] = 'Main company Website Title';
$string['cwebsiteurl'] = 'Website URL';
$string['cwebsiteurl_desc'] = 'Main company Website';
$string['mobile'] = 'Mobile';
$string['mobile_desc'] = 'Enter Mobile Number';
$string['mail'] = 'E-Mail';
$string['mail_desc'] = 'Enter E-Mail ID';
$string['facebook'] = 'Facebook URL';
$string['facebook_desc'] = 'Enter the URL of your Facebook. (i.e http://www.facebook.com/moodlehq)';
$string['customsocialicon'] = 'More icons';
$string['customsocialicon_desc'] = '<br>More icons you can find here:
<ul>
<li><a class="underline--anim" href="https://fontawesome.com/icons" target="_blank">FontAwesome</a></li>
<li><a class="underline--anim" href="https://iconic.app/" target="_blank">iconic</a></li>
<li><a class="underline--anim" href="https://css.gg/" target="_blank">css.gg</a></li>
<li><a class="underline--anim" href="https://coolicons.cool/" target="_blank">coolicons</a></li>
</ul>
<br><strong>Sample code snippet (remember to switch Atto editor to HTML view): </strong> <pre class="rui-pre"><code>&#x3C;li&#x3E;
&#x3C;a href=&#x22;#URL&#x22; target=&#x22;_blank&#x22; class=&#x22;youtube&#x22;&#x3E;
    &#x3C;svg width=&#x22;30&#x22; height=&#x22;30&#x22; fill=&#x22;none&#x22; viewBox=&#x22;0 0 24 24&#x22;&#x3E;
        &#x3C;path stroke=&#x22;currentColor&#x22; stroke-linecap=&#x22;round&#x22; stroke-linejoin=&#x22;round&#x22; stroke-width=&#x22;1.5&#x22; d=&#x22;M4.75 6.75C4.75 5.64543 5.64543 4.75 6.75 4.75H17.25C18.3546 4.75 19.25 5.64543 19.25 6.75V17.25C19.25 18.3546 18.3546 19.25 17.25 19.25H6.75C5.64543 19.25 4.75 18.3546 4.75 17.25V6.75Z&#x22;&#x3E;&#x3C;/path&#x3E;
        &#x3C;path stroke=&#x22;currentColor&#x22; stroke-linecap=&#x22;round&#x22; stroke-linejoin=&#x22;round&#x22; stroke-width=&#x22;1.5&#x22; d=&#x22;M15.25 12L9.75 8.75V15.25L15.25 12Z&#x22;&#x3E;&#x3C;/path&#x3E;
    &#x3C;/svg&#x3E;
&#x3C;/a&#x3E;
&#x3C;/li&#x3E;</code></pre>';
$string['twitter'] = 'Twitter URL';
$string['twitter_desc'] = 'Enter the URL of your twitter. (i.e http://www.twitter.com/moodlehq)';
$string['linkedin'] = 'Linkedin URL';
$string['linkedin_desc'] = 'Enter the URL of your LinkedIn. (i.e http://www.linkedin.com/moodlehq)';
$string['youtube'] = 'Youtube URL';
$string['youtube_desc'] = 'Enter the URL of your Youtube. (i.e https://www.youtube.com/user/moodlehq)';
$string['instagram'] = 'Instagram URL';
$string['instagram_desc'] = 'Enter the URL of your Instagram. (i.e https://www.instagram.com/moodlehq)';

// Content Builder.
$string['scbsettings'] = 'Blocks Order';
$string['block0'] = 'Block #0 <span class="mt-1 small d-block text-light w-100">Main Moodle Content</span><span class="badge-xs badge-light mt-2">Go to the settings - <a href="{$a->siteurl}/admin/settings.php?section=frontpagesettings" target="_blank">Edit</a></span>';
$string['block0_desc'] = '';
$string['block01'] = 'Block #01 <span class="mt-1 small d-block text-light w-100">Front Page Blocks Area #1</span>';
$string['block01_desc'] = '';
$string['block02'] = 'Block #02 <span class="mt-1 small d-block text-light w-100">Front Page Blocks Area #2</span>';
$string['block02_desc'] = '';
$string['block1'] = 'Block #1 <span class="mt-1 small d-block text-light w-100">Hero Slider</span>';
$string['block1_desc'] = '';
$string['block2'] = 'Block #2 <span class="mt-1 small d-block text-light w-100">Hero Video</span>';
$string['block2_desc'] = '';
$string['block3'] = 'Block #3 <span class="mt-1 small d-block text-light w-100">Hero Image</span>';
$string['block3_desc'] = '';
$string['block4'] = 'Block #4 <span class="mt-1 small d-block text-light w-100">FAQ</span>';
$string['block4_desc'] = '';
$string['block5'] = 'Block #5 <span class="mt-1 small d-block text-light w-100">Content</span>';
$string['block5_desc'] = '';
$string['block6'] = 'Block #6 <span class="mt-1 small d-block text-light w-100">Testimonials</span>';
$string['block6_desc'] = '';
$string['block7'] = 'Block #7 <span class="mt-1 small d-block text-light w-100">Grid Content #1</span>';
$string['block7_desc'] = '';
$string['block8'] = 'Block #8 <span class="mt-1 small d-block text-light w-100">Grid Content #2</span>';
$string['block8_desc'] = '';
$string['block9'] = 'Block #9 <span class="mt-1 small d-block text-light w-100">Grid Content #3</span>';
$string['block9_desc'] = '';
$string['block10'] = 'Block #10 <span class="mt-1 small d-block text-light w-100">Logotypes</span>';
$string['block10_desc'] = '';
$string['block11'] = 'Block #11 <span class="mt-1 small d-block text-light w-100">Grid Content #4</span>';
$string['block11_desc'] = '';
$string['block12'] = 'Block #12 <span class="mt-1 small d-block text-light w-100">Stats #1</span>';
$string['block12_desc'] = '';
$string['block13'] = 'Block #13 <span class="mt-1 small d-block text-light w-100">CTA #1</span>';
$string['block13_desc'] = '';
$string['block14'] = 'Block #14 <span class="mt-1 small d-block text-light w-100">CTA #2</span>';
$string['block14_desc'] = '';
$string['block15'] = 'Block #15 <span class="mt-1 small d-block text-light w-100">Grid Content #5</span>';
$string['block15_desc'] = '';
$string['block16'] = 'Block #16 <span class="mt-1 small d-block text-light w-100">Content</span>';
$string['block16_desc'] = '';
$string['block17'] = 'Block #17 <span class="mt-1 small d-block text-light w-100">Stats #2</span>';
$string['block17_desc'] = '';
$string['block18'] = 'Block #18 <span class="mt-1 small d-block text-light w-100">Team</span>';
$string['block18_desc'] = '';
$string['block19'] = 'Block #19 <span class="mt-1 small d-block text-light w-100">CTA #3</span>';
$string['block19_desc'] = '';
$string['block20'] = 'Block #20 <span class="mt-1 small d-block text-light w-100">Hero Slider #2</span>';
$string['block20_desc'] = '';
$string['block21'] = 'Block #21 <span class="mt-1 small d-block text-light w-100">Grid Content #6</span>';
$string['block21_desc'] = '';
$string['block22'] = 'Block #22 <span class="mt-1 small d-block text-light w-100">Categories List #1</span>';
$string['block22_desc'] = '';
$string['block23'] = 'Block #23 <span class="mt-1 small d-block text-light w-100">Categories List #2</span>';
$string['block23_desc'] = '';

$string['displayblockhr'] = 'Show Block Separator (hr)';
$string['displayblockhr_desc'] = '';

// Block 1.
$string['settingsblock1'] = 'Block #1 (Hero Slider #1)';
$string['displayblock1_desc'] = '<small>Script: <a href="https://swiperjs.com/" target="_blank">Swiper</a>. MIT Licensed, v7.0.8 released on October 4, 2021</small>';
$string['hblock1slide'] = 'Slide';
$string['hblock1slide_desc'] = '';
$string['block1count'] = 'Slider count';
$string['block1count_desc'] = '';
$string['block1slideimg'] = 'Slide Image';
$string['block1slideimg_desc'] = '';
$string['block1slidetitle'] = 'Slide Heading';
$string['block1slidetitle_desc'] = '';
$string['block1slidesubheading'] = 'Slide Sub-Heading';
$string['block1slidesubheading_desc'] = '';
$string['block1slidecaption'] = 'Slide Caption';
$string['block1slidecaption_desc'] = '<br />If you want to display or hide some elements for non-logged in users use dedicated class names:<br /><ul><li>For non-logged in users: <strong>hidefornotloggedin</strong></li><li>For logged in users: <strong>hideforloggedin</strong></li></ul>';
$string['block1slidecss'] = 'Slide Custom CSS';
$string['block1slidecss_desc'] = '<a href="https://css-tricks.com/almanac/properties/b/background/" target="_blank">Learn more about CSS background properties</a>';
$string['showblock1sliderwrapper'] = 'Show Colorized Content Wrapper';
$string['showblock1sliderwrapper_desc'] = '';
$string['block1sliderwrapperbg'] = 'Content Wrapper Color';
$string['block1sliderwrapperbg_desc'] = '';
$string['block1wrapperalign'] = 'Content Wrapper Alignment';
$string['block1wrapperalign_desc'] = '';

// Block 2.
$string['settingsblock2'] = 'Block #2 (Hero Video)';
$string['displayblock2_desc'] = '<small>Script: <strong>vidbg.js v2.1</strong> is licensed under The MIT License.</small>';
$string['showblock2wrapper'] = 'Show Colorized Content Wrapper';
$string['showblock2wrapper_desc'] = '';
$string['block2wrapperbg'] = 'Content Wrapper Color';
$string['block2wrapperbg_desc'] = '';
$string['block2wrapperalign'] = 'Content Wrapper Alignment';
$string['block2wrapperalign_desc'] = '';
$string['block2videoposter'] = 'Video Background<br />(poster)';
$string['block2videoposter_desc'] = '';
$string['block2videomp4'] = 'Video Background<br />(mp4)';
$string['block2videomp4_desc'] = '';
$string['block2videowebm'] = 'Video Background<br />(webm)';
$string['block2videowebm_desc'] = '';
$string['block2herotitle'] = 'Heading';
$string['block2herotitle_desc'] = '';
$string['block2herosubheading'] = 'Sub-Heading';
$string['block2herosubheading_desc'] = '';
$string['block2herocaption'] = 'Caption';
$string['block2herocaption_desc'] = '<br />If you want to display or hide some elements for non-logged in users use dedicated class names:<br /><ul><li>For non-logged in users: <strong>hidefornotloggedin</strong></li><li>For logged in users: <strong>hideforloggedin</strong></li></ul>';

// Block 3.
$string['settingsblock3'] = 'Block #3 (Hero Image)';
$string['displayblock3_desc'] = '';
$string['showblock3wrapper'] = 'Show Colorized Content Wrapper';
$string['showblock3wrapper_desc'] = '';
$string['block3wrapperbg'] = 'Content Wrapper Color';
$string['block3wrapperbg_desc'] = '';
$string['block3wrapperalign'] = 'Content Wrapper Alignment';
$string['block3wrapperalign_desc'] = '';
$string['block3img'] = 'Hero Image';
$string['block3img_desc'] = '';
$string['block3videowebm_desc'] = '';
$string['block3herotitle'] = 'Heading';
$string['block3herotitle_desc'] = '';
$string['block3herosubheading'] = 'Sub-Heading';
$string['block3herosubheading_desc'] = '';
$string['block3herocaption'] = 'Caption';
$string['block3herocaption_desc'] = '<br />If you want to display or hide some elements for non-logged in users use dedicated class names:<br /><ul><li>For non-logged in users: <strong>hidefornotloggedin</strong></li><li>For logged in users: <strong>hideforloggedin</strong></li></ul>';

// Block 4.
$string['settingsblock4'] = 'Block #4 (FAQ)';
$string['displayblock4_desc'] = '';
$string['hblock4'] = 'Custom HTML';
$string['hblock4_desc'] = 'You can use HTML to display accordion items or just add it using simple form below.';
$string['hblock4_2'] = 'FAQ Items';
$string['hblock4_2_desc'] = 'Add FAQ items manually.';
$string['block4count'] = 'Number of items';
$string['block4count_desc'] = 'Number of items';
$string['block4answer'] = 'Answer';
$string['block4answer_desc'] = '';
$string['block4question'] = 'Question';
$string['block4question_desc'] = '';

// Block 5.
$string['hblock5item'] = 'Item';
$string['hblock5item_desc'] = '';
$string['settingsblock5'] = 'Block #5 (Content)';
$string['displayblock5_desc'] = '';
$string['block5slidesperrow'] = 'Sldies per row';
$string['block5slidesperrow_desc'] = '';
$string['block5count'] = 'Number of items';
$string['block5count_desc'] = '';
$string['block5itemimg'] = 'Image';
$string['block5itemimg_desc'] = '<span class="badge-sq badge-light">Recommended logo size: 270px x 130px.</span><br /><small class="mx-3">Please also add a inner margin 30px.</small>';
$string['block5itemtitle'] = 'Title (Alt)';
$string['block5itemtitle_desc'] = '';
$string['block5itemurl'] = 'URL with (https:// or http://)';
$string['block5itemurl_desc'] = '';

// Block 6.
$string['settingsblock6'] = 'Block #6 (Testimonials)';
$string['displayblock6_desc'] = '';

// Block 7.
$string['settingsblock7'] = 'Block #7 (Grid Content #1)';
$string['displayblock7_desc'] = '';

// Block 8.
$string['settingsblock8'] = 'Block #8 (Grid Content #2)';
$string['displayblock8_desc'] = '';

// Block 9.
$string['settingsblock9'] = 'Block #9 (Grid Content #3)';
$string['displayblock9_desc'] = '';

// Block 10.
$string['settingsblock10'] = 'Block #10 (Logotypes)';
$string['displayblock10_desc'] = '';

// Block 11.
$string['settingsblock11'] = 'Block #11 (Grid Content #4)';
$string['displayblock11_desc'] = '';

// Block 12.
$string['settingsblock12'] = 'Block #12 (Stats #1)';
$string['displayblock12_desc'] = '';

// Block 13.
$string['settingsblock13'] = 'Block #13 (CTA #1)';
$string['displayblock13_desc'] = '';
$string['block13bg'] = 'Background Image';
$string['block13bg_desc'] = '';
$string['block13bgcolor_desc'] = 'You can use custom CSS for solid background or gradient.';
$string['block13customcss'] = 'Custom CSS (to set up backgroud properties)';
$string['block13customcss_desc'] = 'Tutorial: https://css-tricks.com/almanac/properties/b/background/';
$string['block13textalign'] = 'Text alignment';
$string['block13textalign_desc'] = '';

// Block 14.
$string['settingsblock14'] = 'Block #14 (CTA #2)';
$string['displayblock14_desc'] = '';
$string['block14bg'] = 'Background Image';
$string['block14bg_desc'] = '';
$string['block14customcss'] = 'Custom CSS (to set up backgroud properties)';
$string['block14customcss_desc'] = 'Tutorial: https://css-tricks.com/almanac/properties/b/background/';
$string['block14textalign'] = 'Text alignment';
$string['block14textalign_desc'] = '';

// Block 15.
$string['settingsblock15'] = 'Block #15 (Grid Content #5)';
$string['displayblock15_desc'] = '';

// Block 16.
$string['settingsblock16'] = 'Block #16 (Content)';
$string['displayblock16_desc'] = '';

// Block 17.
$string['settingsblock17'] = 'Block #17 (Stats #2)';
$string['displayblock17_desc'] = '';

// Block 18.
$string['settingsblock18'] = 'Block #18 (Team)';
$string['displayblock18_desc'] = '';

$string['block18htmlcontent_desc'] = 'Team #2 <br /><pre class="rui-pre">&lt;!-- Start - Block - Team #3 --&gt;
&lt;div class="wrapper-fw rui-block-team-3 rui-grid-layout"&gt;
    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Adam Smith&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;Senior Coordinator for Faculty Support&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;
        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;

    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Christa McAuliffe&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;Program Assistant, Middle East Professional Learning Initiative&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;
        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;

    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Helen Keller&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;IT Service Center Support Technician&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;
        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;
    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Mark Twain&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;Audio Visual Technology Infrastructure Specialist&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;
    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Mark Twain&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;Audio Visual Technology Infrastructure Specialist&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;
    &lt;!-- Start item --&gt;
    &lt;div class="rui-block-team-item text-left px-3 text-center"&gt;
        &lt;img src="https://assets.rosea.io/themes/team-1-1.jpg" alt="Team #1" width="320" height="380" class="img-fluid atto_image_button_middle"&gt;
        &lt;h4 class="lead-5 mt-3 mb-0"&gt;Mark Twain&lt;/h4&gt;
        &lt;div class="rui-block-text--3 rui-block-text--light"&gt;Audio Visual Technology Infrastructure Specialist&lt;/div&gt;
        &lt;ul class="rui-social-list mt-2"&gt;
            &lt;li&gt;
                &lt;a href="#" target="_blank" class="facebook"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M2.00195 12.002C2.00312 16.9214 5.58036 21.1101 10.439 21.881V14.892H7.90195V12.002H10.442V9.80204C10.3284 8.75958 10.6845 7.72064 11.4136 6.96698C12.1427 6.21332 13.1693 5.82306 14.215 5.90204C14.9655 5.91417 15.7141 5.98101 16.455 6.10205V8.56104H15.191C14.7558 8.50405 14.3183 8.64777 14.0017 8.95171C13.6851 9.25566 13.5237 9.68693 13.563 10.124V12.002H16.334L15.891 14.893H13.563V21.881C18.8174 21.0506 22.502 16.2518 21.9475 10.9611C21.3929 5.67041 16.7932 1.73997 11.4808 2.01722C6.16831 2.29447 2.0028 6.68235 2.00195 12.002Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="twitter"&gt;
                    &lt;svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"&gt;
                        &lt;path d="M19.995 6.68799C20.8914 6.15208 21.5622 5.30823 21.882 4.31399C21.0397 4.81379 20.118 5.16587 19.157 5.35499C17.8246 3.94552 15.7135 3.60251 14.0034 4.51764C12.2933 5.43277 11.4075 7.37948 11.841 9.26999C8.39062 9.09676 5.17598 7.4669 2.99702 4.78599C1.85986 6.74741 2.44097 9.25477 4.32502 10.516C3.64373 10.4941 2.97754 10.3096 2.38202 9.97799C2.38202 9.99599 2.38202 10.014 2.38202 10.032C2.38241 12.0751 3.82239 13.8351 5.82502 14.24C5.19308 14.4119 4.53022 14.4372 3.88702 14.314C4.45022 16.0613 6.06057 17.2583 7.89602 17.294C6.37585 18.4871 4.49849 19.1342 2.56602 19.131C2.22349 19.1315 1.88123 19.1118 1.54102 19.072C3.50341 20.333 5.78739 21.0023 8.12002 21C11.3653 21.0223 14.484 19.7429 16.7787 17.448C19.0734 15.1531 20.3526 12.0342 20.33 8.78899C20.33 8.60299 20.3257 8.41799 20.317 8.23399C21.1575 7.62659 21.8828 6.87414 22.459 6.01199C21.676 6.35905 20.8455 6.58691 19.995 6.68799Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

            &lt;li&gt;
                &lt;a href="#" target="_blank" class="linkedin"&gt;
                    &lt;svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"&gt;
                        &lt;path d="M19.039,19.043H16.078V14.4c0-1.106-.023-2.53-1.544-2.53-1.544,0-1.78,1.2-1.78,2.449v4.722H9.792V9.5h2.845v1.3h.039a3.12,3.12,0,0,1,2.808-1.542c3,0,3.556,1.975,3.556,4.546v5.238ZM6.447,8.194A1.72,1.72,0,1,1,8.168,6.473,1.719,1.719,0,0,1,6.447,8.194ZM7.932,19.043H4.963V9.5H7.932ZM20.521,2H3.476A1.458,1.458,0,0,0,2,3.441V20.559A1.458,1.458,0,0,0,3.476,22H20.518A1.463,1.463,0,0,0,22,20.559V3.441A1.464,1.464,0,0,0,20.518,2Z" fill="currentColor"&gt;&lt;/path&gt;
                    &lt;/svg&gt;
                &lt;/a&gt;
            &lt;/li&gt;

        &lt;/ul&gt;
    &lt;/div&gt;
    &lt;!-- End item --&gt;

&lt;/div&gt;
&lt;!-- End - Block - Team #3 --&gt;</pre>';

// Block 19.
$string['settingsblock19'] = 'Block #19 (CTA #3)';
$string['displayblock19_desc'] = '';
$string['block19bg'] = 'Background Image';
$string['block19bg_desc'] = '';
$string['block19customcss'] = 'Custom CSS (to set up background properties)';
$string['block19customcss_desc'] = 'Tutorial: https://css-tricks.com/almanac/properties/b/background/';
$string['block19textalign'] = 'Text alignment';
$string['block19textalign_desc'] = '';

// Block 20 - TNS Slider.
$string['settingsblock20'] = 'Block #20 (Hero Slider #2)';
$string['displayblock20_desc'] = 'Slider<br /><small>Script: <a href="https://ganlanyuan.github.io/tiny-slider/demo/" target="_blank">Tiny Slider</a>. MIT Licensed</small>';
$string['imgslidesonly'] = 'Images Only (fully responsive)';
$string['imgslidesonly_desc'] = '';
$string['sliderintervalenabled'] = 'Enable slider interval';
$string['sliderintervalenabled_desc'] = 'Turn on slider auto play.';
$string['sliderloop'] = 'Enable slider loop';
$string['sliderloop_desc'] = '';
$string['sliderclickable'] = 'Enable clickable slider';
$string['sliderclickable_desc'] = 'Each slide will be clickable.';
$string['rtlslider'] = 'Enable RTL Slider';
$string['rtlslider_desc'] = '';
$string['sliderinterval'] = 'Slider interval';
$string['sliderinterval_desc'] = 'Units: 1000 -> 1s.';
$string['sliderfrontpage'] = 'Show slideshow in frontpage';
$string['sliderfrontpage_desc'] = 'If enabled, the slideshow will be showed in the frontpage page replacing the header image.';
$string['slidercount'] = 'Slider count';
$string['slidercount_desc'] = '<div class="badge badge-sq badge-light">Select how many items you want to add then click "Save changes" to load the input fields.</div>';
$string['sliderimage'] = 'Slider picture <span class="badge badge-xs badge-info">1320px x 600px</spanl>';
$string['sliderimage_desc'] = 'Add an image for your slide.';
$string['slidertitle'] = 'Slide Title';
$string['slidertitle_desc'] = 'Add the slide\'s title.<br/>If you want to add URL just add <pre class="rui-pre">&lt;a href="YOUR URL"&gt;LINK LABEL&lt;/a&gt;</pre>';
$string['slidersubtitle'] = 'Slide Subtitle';
$string['slidersubtitle_desc'] = 'Add the slide\'s sub title.<br/>If you want to add URL just add <pre class="rui-pre">&lt;a href="YOUR URL"&gt;LINK LABEL&lt;/a&gt;</pre>';
$string['slidercaption'] = 'Slide Description';
$string['slidercaption_desc'] = 'Add a description for your slides.<br/>If you want to add buttons below just add this code:<br/><pre class="rui-pre">&lt;div class="row mt-5 justify-content-center"&gt;
&lt;a href="http://URL" class="my-2 mr-2 ml-2 btn btn-primary"&gt;Sign in&lt;/a&gt;
&lt;a href="https://URL2" class="my-2 mr-2 ml-2 btn btn-secondary"&gt;Buy this theme&lt;/a&gt;
&lt;/div&gt;</pre><br /><hr class="hr-bold" />';
$string['sliderurl'] = 'Slide URL';
$string['sliderurl_desc'] = 'With https:// or http://';
$string['sliderhtml'] = 'Slide HTML Content';
$string['sliderhtml_desc'] = '';
$string['sliderheight'] = 'Slide Height (on mobile)';
$string['sliderheight_desc'] = 'You can define a height of each slide on mobile view';
$string['slidebackdrop'] = 'Slide Backdrop';
$string['slidebackdrop_desc'] = 'Turn on background under the content.';
$string['herologo'] = 'Hero Logo';
$string['herologo_desc'] = '';
$string['herologotxt'] = 'Hero Logo (Text)';
$string['herologotxt_desc'] = 'alt attribute';

// Block 21.
$string['settingsblock21'] = 'Block #21 (Grid Content #6)';
$string['displayblock21_desc'] = '';

// Block 22.
$string['settingsblock22'] = 'Block #22 (Categories List #1)';
$string['displayblock22_desc'] = '';

// Block 22.
$string['settingsblock23'] = 'Block #23 (Categories List #2)';
$string['displayblock23_desc'] = '<span class="badge badge-warning">Important! Don\'t forget to turn on FontAwesome (General)</span>';
$string['FPHTMLCustomCategoryIcon'] = 'Icon';
$string['FPHTMLCustomCategoryIcon_desc'] = 'More icons you can find here: <a href="https://fontawesome.com/icons">FontAwesome</a>.
<br><strong>Code snippet:</strong> <pre><code>&lt;i class=&quot;fas fa-align-left m-b-2&quot;&gt;&lt;/i&gt;</code></pre>';

$string['FPHTMLCustomCategoryHeading'] = 'Heading';
$string['FPHTMLCustomCategoryHeading_desc'] = '';

$string['FPHTMLCustomCategoryContent'] = 'Content';
$string['FPHTMLCustomCategoryContent_desc'] = '';

$string['FPHTMLCustomCategoryBlockHTML1'] = 'Content Col #1 (left)';
$string['FPHTMLCustomCategoryBlockHTML1_desc'] = '
<div class="mt-3 mb-2">Sample code snippet <span class="badge badge-sq badge-warning">remember to switch Atto editor to HTML view</span></div><br><strong>Code snippet: </strong> <pre><code>&lt;ul class=&quot;c-courses-list&quot;&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=9&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=8&quot;&gt;Introduction to Open Education&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=7&quot;&gt;Digital Literacy&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=6&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=9&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=8&quot;&gt;Introduction to Open Education&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=7&quot;&gt;Digital Literacy&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=6&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;</code></pre>';

$string['FPHTMLCustomCategoryBlockHTML2'] = 'Content Col #2 (right)';
$string['FPHTMLCustomCategoryBlockHTML2_desc'] = '
<div class="mt-3 mb-2">Sample code snippet <span class="badge badge-sq badge-warning">remember to switch Atto editor to HTML view</span></div><br><strong>Code snippet: </strong> <pre><code>&lt;ul class=&quot;c-courses-list&quot;&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=9&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=8&quot;&gt;Introduction to Open Education&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=7&quot;&gt;Digital Literacy&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=6&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=9&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=8&quot;&gt;Introduction to Open Education&lt;/a&gt;&lt;br&gt;&lt;span class=&quot;small&quot;&gt;&lt;i class=&quot;mr-1 far fa-calendar-alt&quot;&gt;&lt;/i&gt; January - June  &lt;i class=&quot;ml-3 mr-1 fas fa-graduation-cap&quot;&gt;&lt;/i&gt; Teacher: Adam Smith&lt;/span&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=7&quot;&gt;Digital Literacy&lt;/a&gt;&lt;/li&gt;
      &lt;li&gt;&lt;a href=&quot;course/view.php?id=6&quot;&gt;Celebrating Cultures&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;</code></pre>';

$string['FPHTMLCustomCategoryBlockHTML3'] = 'HTML Block (Under the left side content)';
$string['FPHTMLCustomCategoryBlockHTML3_desc'] = '';

// Block 0 - Main Moodle Content.
$string['settingsblock0'] = 'Block #0';
$string['displayblock0_desc'] = '';
$string['settingsblock24'] = 'Block #01';
$string['displayblock0_desc'] = '';
$string['settingsblock25'] = 'Block #02';
$string['displayblock0_desc'] = '';

// Customization.
$string['settingscustomization'] = 'Customization';
$string['hfontsettings'] = 'Fonts Settings';
$string['hfontsettings_desc'] = 'Customize Font Properties';
$string['hgooglefont'] = 'Google Font';
$string['hgooglefont_desc'] = 'Google Fonts is a library of 1,284 free licensed font families.
<a href="https://fonts.google.com" target="_blank">Google Fonts Library</a>.
<br /><small>If you want to use self-hosted Google Fonts or any other custom font go to Advanced tab - Font files</small>';
$string['googlefonturl'] = 'Google Font URL';
$string['googlefonturl_desc'] = "Leave empty if you don't want to use Google Font.";
$string['fontbody'] = 'Font Name (Body)';
$string['fontbody_desc'] = "";
$string['fontheadings'] = 'Font Name (Headings)';
$string['fontheadings_desc'] = "Leave empty if you don't use additional font for headings.";
$string['fontweightregular'] = 'Font weight: Regular';
$string['fontweightregular_desc'] = 'e.g 400';
$string['fontweightmedium'] = 'Font weight: Medium';
$string['fontweightmedium_desc'] = 'e.g 500, 600';
$string['fontweightbold'] = 'Font weight: Bold';
$string['fontweightbold_desc'] = 'e.g 700, 800, 900';
$string['fontweightheadings'] = 'Font weight (Headings)';
$string['fontweightheadings_desc'] = 'e.g 700, 800, 900';

$string['hgeneral'] = 'General';
$string['hgeneral_desc'] = 'Background and border color, button radius';
$string['colorbodybg'] = 'Body Background Color';
$string['colorborder'] = 'Border Color (Global)';
$string['btnborderradius'] = 'Button Border Radius (px)';
$string['btnborderradius_desc'] = '
All roundedness will be generated automatically by SCSS.
If you want to change the roules just use code below to: Advanced settings - Raw initial SCSS 
<pre>$btn-border-radius: 5px !default;
$btn-border-radius-xl: $btn-border-radius * 12 !default;
$btn-border-radius-lg: $btn-border-radius * 8 !default;
$btn-border-radius-md: $btn-border-radius - 2px !default;
$btn-border-radius-sm: $btn-border-radius - 3px !default;</pre>';

$string['topbarlogoareaon'] = 'Turn on a logo area';
$string['topbarlogoareaon_desc'] = 'You can show or hide logo area on the top bar.
You can upload an image, use <a href="#admin-customlogotxt">custom text</a> or
<a href="{$a->siteurl}/admin/settings.php?section=frontpagesettings" target="_blank">default moodle site name (shortname).</a>';
$string['customlogo'] = 'Logo (Top Bar)';
$string['customlogo_desc'] = '';
$string['customdmlogo'] = 'Logo (Top Bar) <span class="badge badge-xs badge-dark mx-2">Dark Mode</span>';
$string['customdmlogo_desc'] = '';
$string['customlogotxt'] = 'Company/Site Name';
$string['customlogotxt_desc'] = '';
$string['customlogoandname'] = 'Display Text Next to The Logo';
$string['customlogoandname_desc'] = 'The company or site name must be added.
<br /><img src="{$a->siteurl}/theme/iomadmoon/doc/logo-text.png" class="img-fluid rounded my-3" style="max-height: 100px; width: auto; max-width: 100%;" alt="Space Moodle Theme" />';

$string['hcolorstxt'] = 'Text Colors <span class="badge badge-sm badge-light mx-2">Not recommended to change</span>';
$string['hcolorstxt_desc'] = '<p>Compatible with WCAG Principles.</p><p>Change only when you really need to.</p>';
$string['colorheadings'] = 'Headings';
$string['colorbody'] = 'Text Color';
$string['colorbodysecondary'] = 'Text Color (Secondary)';
$string['colorbodylight'] = 'Text Color (Light)';
$string['colorlink'] = 'Link Color';
$string['colorlinkhover'] = 'Link Color (Hover)';

$string['hcolorsgrays'] = 'Grays <span class="badge badge-sm badge-light mx-2">Not recommended to change</span>';
$string['hcolorsgrays_desc'] = '<p>I used gray shades compatible with WCAG Principles.</p>
<p>Change only when you really need to.</p><br /><div class="d-inline-flex flex-wrap">
<div class="sqcolor bg-gray-100 bg--desc m-1"></div><div class="sqcolor bg-gray-200 bg--desc m-1"></div>
<div class="sqcolor bg-gray-300 bg--desc m-1"></div><div class="sqcolor bg-gray-400 bg--desc m-1"></div>
<div class="sqcolor bg-gray-500 bg--desc m-1"></div><div class="sqcolor bg-gray-600 bg--desc m-1"></div>
<div class="sqcolor bg-gray-700 bg--desc m-1"></div><div class="sqcolor bg-gray-800 bg--desc m-1"></div>
<div class="sqcolor bg-gray-900 bg--desc m-1"></div></div>';
$string['colorgray100'] = 'Gray 100';
$string['colorgray200'] = 'Gray 200';
$string['colorgray300'] = 'Gray 300<br /><small class="mx-2">e.g main borders color</small>';
$string['colorgray400'] = 'Gray 400';
$string['colorgray500'] = 'Gray 500';
$string['colorgray600'] = 'Gray 600';
$string['colorgray700'] = 'Gray 700';
$string['colorgray800'] = 'Gray 800';
$string['colorgray900'] = 'Gray 900';
$string['colorgray_desc'] = '';

$string['hcolorsprimary'] = 'Primary colors';
$string['hcolorsprimary_desc'] = 'Primary buttons, top bar, all important/primary UI elements.
<br /><div class="d-inline-flex flex-wrap"><div class="sqcolor bg-primary-100 bg--desc m-1"></div>
<div class="sqcolor bg-primary-200 bg--desc m-1"></div><div class="sqcolor bg-primary-300 bg--desc m-1"></div>
<div class="sqcolor bg-primary-400 bg--desc m-1"></div><div class="sqcolor bg-primary-500 bg--desc m-1"></div>
<div class="sqcolor bg-primary-600 bg--desc m-1"></div><div class="sqcolor bg-primary-700 bg--desc m-1"></div>
<div class="sqcolor bg-primary-800 bg--desc m-1"></div><div class="sqcolor bg-primary-900 bg--desc m-1"></div>
</div>';
$string['colorprimary100'] = 'Primary 100';
$string['colorprimary200'] = 'Primary 200';
$string['colorprimary300'] = 'Primary 300';
$string['colorprimary400'] = 'Primary 400';
$string['colorprimary500'] = 'Primary 500';
$string['colorprimary600'] = 'Primary 600 - Main Theme Color';
$string['colorprimary700'] = 'Primary 700';
$string['colorprimary800'] = 'Primary 800';
$string['colorprimary900'] = 'Primary 900';
$string['colorprimary_desc'] = '<span class="badge badge-sq badge-warning">To generate automatically the colour palette just set up the "Main Theme Color - 600"</span>
<p class="mt-2"><small>If you want to change any color from the palette just add custom HEX color value to the field.</small></p>';
$string['color_desc'] = '';

$string['hdmgeneral'] = 'General <span class="badge badge-xs badge-dark mx-2">Dark Mode</span>';
$string['hdmgeneral_desc'] = 'Background and border color, button radius';
$string['dmcolorbodybg'] = 'Body Background Color';
$string['dmcolorborder'] = 'Border Color (Global)';

$string['hdmcolorstxt'] = 'Text Colors <span class="badge badge-xs badge-dark mx-2">Dark Mode</span> <span class="badge badge-sm badge-light mx-2">Not recommended to change</span>';
$string['hdmcolorstxt_desc'] = '<p>Compatible with WCAG Principles.</p><p>Change only when you really need to.</p>';
$string['dmcolorheadings'] = 'Headings';
$string['dmcolorbody'] = 'Text Color';
$string['dmcolorbodysecondary'] = 'Text Color (Secondary)';
$string['dmcolorbodylight'] = 'Text Color (Light)';
$string['dmcolorlink'] = 'Link Color';
$string['dmcolorlinkhover'] = 'Link Color (Hover)';

$string['hdmcolorsgrays'] = 'Grays <span class="badge badge-xs badge-dark mx-2">Dark Mode</span> <span class="badge badge-sm badge-light mx-2">Not recommended to change</span>';
$string['hdmcolorsgrays_desc'] = '<p>I used gray shades compatible with WCAG Principles.</p>
<p>Change only when you really need to.</p><br /><div class="d-inline-flex flex-wrap">
<div class="sqcolor bg-dm-gray-100 bg--desc m-1"></div><div class="sqcolor bg-dm-gray-200 bg--desc m-1"></div>
<div class="sqcolor bg-dm-gray-300 bg--desc m-1"></div><div class="sqcolor bg-dm-gray-400 bg--desc m-1"></div>
<div class="sqcolor bg-dm-gray-500 bg--desc m-1"></div><div class="sqcolor bg-dm-gray-600 bg--desc m-1"></div>
<div class="sqcolor bg-dm-gray-700 bg--desc m-1"></div><div class="sqcolor bg-dm-gray-800 bg--desc m-1"></div>
<div class="sqcolor bg-dm-gray-900 bg--desc m-1"></div></div>';
$string['dmcolorgray100'] = 'Gray 100';
$string['dmcolorgray200'] = 'Gray 200';
$string['dmcolorgray300'] = 'Gray 300<br /><small class="mx-2">e.g main borders color</small>';
$string['dmcolorgray400'] = 'Gray 400';
$string['dmcolorgray500'] = 'Gray 500';
$string['dmcolorgray600'] = 'Gray 600';
$string['dmcolorgray700'] = 'Gray 700';
$string['dmcolorgray800'] = 'Gray 800';
$string['dmcolorgray900'] = 'Gray 900';
$string['dmcolorgray_desc'] = '';

$string['hdmcolorsprimary'] = 'Primary Buttons <span class="badge badge-xs badge-dark mx-2">Dark Mode</span>';
$string['hdmcolorsprimary_desc'] = 'Primary buttons, top bar, all important/primary UI elements.
<br /><div class="d-inline-flex flex-wrap"><div class="sqcolor bg-primary-100 bg--desc m-1"></div>
<div class="sqcolor bg-primary-200 bg--desc m-1"></div><div class="sqcolor bg-primary-300 bg--desc m-1"></div>
<div class="sqcolor bg-primary-400 bg--desc m-1"></div><div class="sqcolor bg-primary-500 bg--desc m-1"></div>
<div class="sqcolor bg-primary-600 bg--desc m-1"></div><div class="sqcolor bg-primary-700 bg--desc m-1"></div>
<div class="sqcolor bg-primary-800 bg--desc m-1"></div><div class="sqcolor bg-primary-900 bg--desc m-1"></div>
</div>';
$string['dmcolorprimary100'] = 'Primary 100';
$string['dmcolorprimary200'] = 'Primary 200';
$string['dmcolorprimary300'] = 'Primary 300';
$string['dmcolorprimary400'] = 'Primary 400';
$string['dmcolorprimary500'] = 'Primary 500';
$string['dmcolorprimary600'] = 'Primary 600 - Main Theme Color';
$string['dmcolorprimary700'] = 'Primary 700';
$string['dmcolorprimary800'] = 'Primary 800';
$string['dmcolorprimary900'] = 'Primary 900';
$string['dmcolorprimary_desc'] = '<span class="badge badge-sq badge-warning">To generate automatically the colour palette just set up the "Main Theme Color - 600"</span>
<p class="mt-2"><small>If you want to change any color from the palette just add custom HEX color value to the field.</small></p>';
$string['dmcolor_desc'] = '';

$string['additionalclass'] = 'Additional Class Name';
$string['additionalclass_desc'] = '<strong class="badge badge-warning mr-2">Only for developers.</strong><span>You can add multiple class names e.g class1 class2 class3 </span>';

$string['hintro'] = '<img src="{$a->siteurl}/theme/iomadmoon/doc/iomadmoon-icon.png" class="img-fluid rounded my-3" width="80" height="80" alt="IOMAD Premium Moodle Theme" /><div class="lead-3">Moon for IOMAD 4.3</div>';
$string['hintro_desc'] = '
<div class="mt-1 small">by <a href="https://rosea.io">RoseaThemes</a></div><hr class="mt-3" />
<div class="mt-3"><span class="badge badge-primary"><a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/changelog" target="_blank">Version: 1.5.2</a></span></div>
<div class="mt-4"><h3 class="lead-4 mb-2">Need help with theme customization?
<br />Or you want to report a bug?</h3>Just let me know.
Open <a href="https://roseathemes.ticksy.com" target="_blank">a ticket</a> or contact me via support form on the ThemeForest item page.</div>
<a href="https://rosea.gitbook.io/moon-iomad-moodle-theme/" target="_blank" class="btn btn-sm btn-dark mt-3">Online documentation</a>
';

// Credits: BoostCampus.
// Setting: Show hint for switched role setting.
$string['showswitchedroleincoursesetting'] = 'Show hint for switched role';
$string['showswitchedroleincoursesetting_desc'] = 'Enabling this option shows a hint in the course header if the user switched roles in the course. It also provides a link to switch back within the course page.
<br /><img src="{$a->siteurl}/theme/iomadmoon/doc/hints.png" class="img-fluid rounded my-3" style="max-height: 200px; width: auto; max-width: 100%;" alt="space Moodle Theme" />';
$string['switchedroleto'] = 'You are viewing this course currently with the role: <strong>{$a->role}</strong>';
// Setting: Show hint for hidden course.
$string['showhintcoursehiddensetting'] = 'Show hint in hidden courses';
$string['showhintcoursehiddensetting_desc'] = 'A hint appears in the course header when the course is hidden, indicating its visibility state without checking the settings.';
$string['showhintcoursehiddengeneral'] = 'This course is currently <strong>hidden</strong>. Only enrolled teachers can access this course when hidden.';
$string['showhintcoursehiddensettingslink'] = 'You can change the visibility in the <a href="{$a->url}">course settings</a>.';
// Setting: Show hint for guest access.
$string['showhintcoursguestaccesssetting'] = 'Show hint for guest access';
$string['showhintcourseguestaccesssetting_desc'] = 'When guests access the course, a hint appears in the header. If self-enrollment is available, a link to that page is also provided.';
$string['showhintcourseguestaccessgeneral'] = 'You are currently viewing this course as <strong>{$a->role}</strong>.';
$string['showhintcourseguestaccessgeneralinfo'] = 'Please log in to check how to enrol into the course and get full access.';
$string['showhintcourseguestaccesslink'] = 'To have full access to the course, you can <a href="{$a->url}">self enrol into this course</a>.';
// Setting: Show hint for unrestricted self enrolment.
$string['showhintcourseselfenrolsetting'] = 'Show hint for self enrolment without enrolment key';
$string['showhintcourseselfenrolsetting_desc'] = 'A hint appears in the course header when enrolment without enrolment key is possible.';
$string['showhintcourseselfenrolstartcurrently'] = 'This course is currently visible to everyone and <strong>self enrolment without an enrolment key</strong> is possible.';
$string['showhintcourseselfenrolstartfuture'] = 'This course is currently visible to everyone and <strong>self enrolment without an enrolment key</strong> is planned to become possible.';
$string['showhintcourseselfenrolunlimited'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment indefinitely.';
$string['showhintcourseselfenroluntil'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment until {$a->until}.';
$string['showhintcourseselfenrolfrom'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment from {$a->from} on.';
$string['showhintcourseselfenrolsince'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment currently.';
$string['showhintcourseselfenrolfromuntil'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment from {$a->from} until {$a->until}.';
$string['showhintcourseselfenrolsinceuntil'] = 'The <strong>{$a->name}</strong> enrolment instance allows unrestricted self enrolment until {$a->until}.';
$string['showhintcourseselfenrolinstancecallforaction'] = 'If you don\'t want any Moodle user to have access to this course freely, please restrict the self enrolment settings.';

// Moodle 4.
$string['showfooter'] = 'Show footer';
$string['privacy:metadata:preference:draweropenblock'] = 'The user\'s preference for hiding or showing the drawer with blocks.';
$string['privacy:metadata:preference:draweropenindex'] = 'The user\'s preference for hiding or showing the drawer with course index.';
$string['privacy:metadata:preference:draweropennav'] = 'The user\'s preference for hiding or showing the drawer menu navigation.';
$string['privacy:drawerindexclosed'] = 'The current preference for the index drawer is closed.';
$string['privacy:drawerindexopen'] = 'The current preference for the index drawer is open.';
$string['privacy:drawerblockclosed'] = 'The current preference for the block drawer is closed.';
$string['privacy:drawerblockopen'] = 'The current preference for the block drawer is open.';
$string['privacy:drawernavclosed'] = 'The current preference for the navigation drawer is closed.';
$string['privacy:drawernavopen'] = 'The current preference for the navigation drawer is open.';
$string['unaddableblocks'] = 'Unneeded blocks';
$string['unaddableblocks_desc'] = 'The blocks specified are not needed when using this theme and will not be listed in the \'Add a block\' menu.';

// Moon (1.x).
$string['hcoursenavitems'] = 'Course Navigation Items';
$string['hcoursenavitems_desc'] = 'Turn on/off any navigation items like: Participants, Grades, Competencies, Badges.';
$string['isitemongrades'] = 'Grade';
$string['isitemongrades_desc'] = '';
$string['isitemonparticipants'] = 'Participants';
$string['isitemonparticipants_desc'] = '';
$string['isitemonbadges'] = 'Badges';
$string['isitemonbadges_desc'] = '';
$string['isitemoncompetencies'] = 'Competencies';
$string['isitemoncompetencies_desc'] = '';

$string['hmainnav'] = 'Main Navigation Items';
$string['hmainnav_desc'] = 'Customization of the Main Navigation Items';
$string['isitemonsitehome'] = 'Site home';
$string['isitemonsitehome_desc'] = '';
$string['isitemondashboard'] = 'Dashboard';
$string['isitemondashboard_desc'] = '';
$string['isitemoncalendar'] = 'Calendar';
$string['isitemoncalendar_desc'] = '';
$string['isitemonprivatefiles'] = 'Private files';
$string['isitemonprivatefiles_desc'] = '';
$string['isitemoncontentbank'] = 'Content bank';
$string['isitemoncontentbank_desc'] = '';
$string['isitemonmycourses'] = 'My courses';
$string['isitemonmycourses_desc'] = '';

$string['empty_desc'] = '';
$string['iconcustomitem_desc'] = 'SVG Inline <br><strong>Sample SVG Icon</strong>
<pre class="rui-pre"><code>&lt;svg width="20" height="20" fill="none"
viewBox="0 0 24 24"&gt; &lt;path stroke="currentColor" stroke-linecap="round"
stroke-linejoin="round" stroke-width="1.5" d="M4.75 6.75C4.75 5.64543 5.64543
4.75 6.75 4.75H17.25C18.3546 4.75 19.25 5.64543 19.25 6.75V17.25C19.25 18.3546
18.3546 19.25 17.25 19.25H6.75C5.64543 19.25 4.75 18.3546 4.75
17.25V6.75Z"&gt;&lt;/path&gt; &lt;path stroke="currentColor"
stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
d="M9.75 8.75V19"&gt;&lt;/path&gt; &lt;path stroke="currentColor"
stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
d="M5 8.25H19"&gt;&lt;/path&gt; &lt;/svg&gt;</code></pre>
<br>More icons you can find here:
<ul>
<li><a class="underline--anim" href="https://fontawesome.com/icons" target="_blank">FontAwesome</a></li>
<li><a class="underline--anim" href="https://iconic.app/" target="_blank">iconic</a></li>
<li><a class="underline--anim" href="https://css.gg/" target="_blank">css.gg</a></li>
<li><a class="underline--anim" href="https://coolicons.cool/" target="_blank">coolicons</a></li>
</ul>';
$string['urlcustomitem_desc'] = 'External links with https:// or http://.<br />Interial links e.g /mod/forum/view.php?id=207';

$string['possitehome'] = 'Site home (Position)';
$string['posdashboard'] = 'Dashboard (Position)';
$string['posprivatefiles'] = 'Private Files (Position)';
$string['poscalendar'] = 'Calendar (Position)';
$string['poscontentbank'] = 'Content Bank (Position)';
$string['posmycourses'] = 'My Courses (Position)';

$string['hcustomitem1'] = 'Custom Item #1';
$string['iscustomitem1on'] = 'Custom Item #1';
$string['labelcustomitem1'] = 'Custom Item #1 (Label)';
$string['poscustomitem1'] = 'Custom Item #1 (Position)';
$string['iconcustomitem1'] = 'Custom Item #1 (Icon)';
$string['urlcustomitem1'] = 'Custom Item #1 (URL)';

$string['hcustomitem2'] = 'Custom Item #2';
$string['iscustomitem2on'] = 'Custom Item #2';
$string['labelcustomitem2'] = 'Custom Item #2 (Label)';
$string['poscustomitem2'] = 'Custom Item #2 (Position)';
$string['iconcustomitem2'] = 'Custom Item #2 (Icon)';
$string['urlcustomitem2'] = 'Custom Item #2 (URL)';

$string['hcustomitem3'] = 'Custom Item #3';
$string['iscustomitem3on'] = 'Custom Item #3';
$string['labelcustomitem3'] = 'Custom Item #3 (Label)';
$string['poscustomitem3'] = 'Custom Item #3 (Position)';
$string['iconcustomitem3'] = 'Custom Item #3 (Icon)';
$string['urlcustomitem3'] = 'Custom Item #3 (URL)';

$string['hcustomitem4'] = 'Custom Item #4';
$string['iscustomitem4on'] = 'Custom Item #4';
$string['labelcustomitem4'] = 'Custom Item #4 (Label)';
$string['poscustomitem4'] = 'Custom Item #4 (Position)';
$string['iconcustomitem4'] = 'Custom Item #4 (Icon)';
$string['urlcustomitem4'] = 'Custom Item #4 (URL)';

$string['hcustomitem5'] = 'Custom Item #5';
$string['iscustomitem5on'] = 'Custom Item #5';
$string['labelcustomitem5'] = 'Custom Item #5 (Label)';
$string['poscustomitem5'] = 'Custom Item #5 (Position)';
$string['iconcustomitem5'] = 'Custom Item #5 (Icon)';
$string['urlcustomitem5'] = 'Custom Item #5 (URL)';


$string['navposition_desc'] = '';

$string['hvpcss'] = 'H5P CSS';
$string['hvpcss_desc'] = 'Custom CSS code to be applied to H5P activities';

$string['hidecourseindexnav'] = 'Hide Course Index Navigation';
$string['hidecourseindexnav_desc'] = 'Hide or show the Course Index drawer navigation.';

$string['blockbg'] = 'Background Image';
$string['blockbg_desc'] = '';
$string['blockcustomcss'] = 'Custom CSS';
$string['blockcustomcss_desc'] = '<p class="mb-3">Tutorial:
<a class="underline--anim" href="https://css-tricks.com/almanac/properties/b/background/" target="_blank">css-tricks - background properties</a>.</p>
<p class="mt-3">Sample code:</p>
<pre>background-position: bottom center; background-size: 100%; background-color: #000; background-repeat: no-repeat;</pre>
<p class="mt-3">Background Gradient Generator - <a class="underline--anim" href="https://mycolor.space/gradient" target="_blank">mycolor.space/gradient</a></p>';
$string['blocktextalign'] = 'Text alignment';
$string['blocktextalign_desc'] = '';
$string['blocktitle'] = 'Heading';
$string['blocktitle_desc'] = '';
$string['blocktitlesize'] = 'Heading Size';
$string['blocktitlesize_desc'] = 'Normal: 40px , Large: 60px , Extra large: 80px';
$string['blocktitleweight'] = 'Heading Font Weight';
$string['blocktitleweight_desc'] = '';
$string['blocktitlecolor'] = 'Heading Color';
$string['blocktitlecolor_desc'] = '';

$string['blockfw'] = 'Full-width container';
$string['blockfw_desc'] = '';

$string['defaultcourseimg'] = 'Default Course Image';
$string['defaultcourseimg_desc'] = 'Add a default course image cover.';

// Space 2.5.
$string['backtotop'] = 'Back to top button';
$string['backtotop_desc'] = '';

$string['showcustomfields'] = 'Show custom fields';
$string['showcustomfields_desc'] = '';

$string['hlabelsidebar'] = 'Sidebar Label (Customization)';
$string['hlabelsidebar_desc'] = '';

$string['labelsidebaropened'] = 'Sidebar Label (Opened)';
$string['labelsidebaropened_desc'] = 'Learn how to add multi-language content. <a href="https://docs.moodle.org/402/en/Multi-language_content_filter"> Learn more</a>';

$string['labelsidebarclosed'] = 'Sidebar Label (Closed)';
$string['labelsidebarclosed_desc'] = 'Learn how to add multi-language content. <a href="https://docs.moodle.org/402/en/Multi-language_content_filter"> Learn more</a>
<br /><img src="{$a->siteurl}/theme/iomadmoon/doc/sidebar-labels.png" class="rounded mt-3" style="max-width: 500px;max-height:150px;width:100%;" />';

$string['secnavgroupitem'] = 'Include Groups Item to the Secondary Navigation';
$string['secnavgroupitem_desc'] = 'For users who have capabilities such as moodle/course:managegroups. <br /><img src="{$a->siteurl}/theme/iomadmoon/doc/secondary-nav-course-tmpl.png" class="rounded mt-3" style="max-width:500px;max-height:150px;width:100%;" />';

$string['settingscoursesnav'] = 'Course Page - Secondary Navigation';

$string['secnavitems'] = 'Turn on additional menu items';
$string['secnavitems_desc'] = 'Select and save to turn on additional options for customizing secondary navigation.';

$string['hsecnavitem'] = 'Item';
$string['hsecnavitem_desc'] = '';

$string['secnavitemscount'] = 'Number of custom items';
$string['secnavitemscount_desc'] = '';

$string['secnavcustomnavlabel'] = 'Label';
$string['secnavcustomnavlabel_desc'] = '';

$string['secnavcustomnavurl'] = 'URL';
$string['secnavcustomnavurl_desc'] = 'To add course ID to the URL, just use {{courseID}}. eg. https://localhost/group/index.php?id={{courseID}}';

$string['secnavuserrole'] = 'Select user role';
$string['secnavuserrole_desc'] = 'Add restrictions depends on user roles. [ Role name (role ID) ]';
$string['secnavuserrole0'] = 'None';
$string['secnavuserrole1'] = 'Admin (1) / Manager (2) / Course Creator (3) / Editing Teachers (4)';
$string['secnavuserroles'] = 'Select user role';
$string['secnavuserroles_desc'] = 'Add restrictions depends on user roles.';

$string['rolemanager'] = 'Manager';
$string['rolecoursecreator'] = 'Course Creator';
$string['roleeditingteacher'] = 'Teacher (editing)';
$string['rolenoneditingteacher'] = 'Teacher (non-editing)';
$string['roleguest'] = 'Guest';
$string['rolestudent'] = 'Student';
$string['roleauthenticateduser'] = 'Authenticated user';

$string['backtotopbutton'] = 'Go to top';

$string['displayfilterhiddenchbx'] = 'Display Hidden Courses Toggle';
$string['displayfilterhiddenchbx_desc'] = '';

$string['forcefwvideo'] = 'Force full-width video';
$string['forcefwvideo_desc'] = 'Turn off this option to revert to the default Moodle video size settings.
<br /><img src="{$a->siteurl}/theme/iomadmoon/doc/video-size.jpg" class="img-fluid rounded my-3" style="max-height: 200px; width: auto; max-width: 100%;" alt="Space Moodle Theme" />';

$string['logoiconbox'] = 'Force rounded logo with a border';
$string['logoiconbox_desc'] = '';

$string['topbarcompanyleft'] = 'Display company logo on the left';
$string['topbarcompanyleft_desc'] = '<img src="{$a->siteurl}/theme/iomadmoon/doc/company-logo.png" class="mt-2 rounded" style="max-height: 209px; width: auto; max-width: 100%;" alt="Company Logo" />';

$string['hidemainlogo'] = 'Hide Main Page Logo';
$string['hidemainlogo_desc'] = 'The main page logo will only be visible on the front page. It will not be displayed anywhere else.';

$string['accessiblelinks'] = 'Accesible Links (Beta version)';
$string['accessiblelinks_desc'] = 'Meets WCAG 2.0 requirements for lebel A.';

$string['accessiblebtnfocus'] = 'Accesible Buttons (Beta version)';
$string['accessiblebtnfocus_desc'] = 'Additional border on focus. Meets WCAG 2.0 requirements';

$string['ecoursesummary'] = 'Display course summary (Enrollment Page)';
$string['ecoursesummary_desc'] = 'To use custom enrollment content you have to add a custom course field (Site administration - Courses - Default settings - Course custom fields) shortname: <strong>enrolldesc</strong>';