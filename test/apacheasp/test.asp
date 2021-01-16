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
<% my $class %>
<% my $vars = $Request->ServerVariables() %>
<% for (sort keys %{$vars}) { %>
	<% next unless /^HTTP_|^REQUEST_/ %>
	<% $class = ($class ne 'normal')? 'normal': 'alt' %>
	<tr class="<%=$class%>">
		<td><%=$_%></td>
		<td><%=$vars->{$_}%></td>
	</tr>
<% } %>
</table>
</body>
</html>
