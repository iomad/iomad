#!/usr/bin/python

import fcgi, os, sys, cgi

count=0

while fcgi.isFCGI():
	req = fcgi.Accept()
	count = count+1
				
	req.out.write("Content-Type: text/html\n\n")
	req.out.write("""<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="../../css/style.css" />
	</head>
	<body class="test-data">
	<table cellspacing="0" cellpadding="0" border="0">
	<tr class="subhead"><th>Name</th><th>Value</th></tr>""")
	req.out.write('<tr class="normal"><td>%s</td><td>%s</td></tr>\n' % ("Request counter", count))
	names = req.env.keys()
	names.sort()
	cl = ('alt','normal')
	i= 0
	for name in names:
		if not name.find("HTTP") or not name.find("REQUEST"):
			req.out.write('<tr class="%s"><td>%s</td><td>%s</td></tr>\n' % (cl[i%2],
				name, cgi.escape(`req.env[name]`)))
			i = i+1

	req.out.write('</table>\n</body></html>\n')

	req.Finish()
