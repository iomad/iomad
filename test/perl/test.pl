# Copyright 1999-2014. Parallels IP Holdings GmbH. All Rights Reserved.
use ExtUtils::Installed;
my ($inst) = ExtUtils::Installed->new();
my (@modules) = $inst->modules();

print <<HTML;
Content-type: text/html

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="../../css/style.css" />
</head>
<body class="test-data">
<table cellspacing="0" cellpadding="0" border="0">
<tr class="subhead" align="Left"><th>Name</th><th>Value</th></tr>
HTML

for my $i ($[ .. $#modules) {
   my $version = $inst->version($modules[$i]) || "???";
   my $class = ($i % 2) ? "alt" : "normal";
   print <<HTML;
<tr class="$class"><td valign="top">$modules[$i]</td><td>$version</td></tr>
HTML
}

print <<HTML;
</table>
</body>
</html>
HTML
