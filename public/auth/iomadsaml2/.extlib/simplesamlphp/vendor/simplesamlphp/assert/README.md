# Assert

![Build Status](https://github.com/simplesamlphp/assert/workflows/CI/badge.svg?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/assert/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/assert/?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/assert/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/assert)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/assert/coverage.svg)](https://shepherd.dev/github/simplesamlphp/assert)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/assert/level.svg)](https://shepherd.dev/github/simplesamlphp/assert)

## Background

A wrapper around webmozart/assert to make it useful beyond checking method
arguments. One of the major reasons to come up with this fork was our
requirement to be able to throw custom exceptions, instead of _everything_
being thrown as a generic `InvalidArgumentException`.

Using a `__callStatic` wrapper we are able to wrap the webmozart-methods
allowing for an extra `exception` parameter, and catch the
`InvalidArgumentException` by the original library, then throw the desired
exception, or fall back to our custom `AssertionFailedException`.

In practise, this means that _every_ assertion provided by the original library
can be used an provided with an additional parameter. If you provide it, and it
translates into a `Throwable` class, that is what will be thrown as soon as the
assertion fails. If you don't pass the the extra parameter, we will throw the
more generic `AssertionFailedException` (which in our opinion is still better
than the even _more_ generic `InvalidArgumentException`).

We also felt that `InvalidArgumentException` is incorrect to use in this case.
This exception was intended by PHP to be thrown when a function parameter is of
the wrong type. Our custom `AssertionFailedException` therefore inherits from
`UnexpectedValueException` which is intended to verify values against valid
value sets, possibly during the internal computations of a function. We deem
this much more appropriate for use in assertions.

## Custom Assertions

Another reason to fork is the ability to add a few custom assertions that may
only make sense for XML / SAML2 related things.

Currently this library provides the following additional assertions:

### Assertions

Method                                         | Description
-----------------------------------------------|-----------------------------------------------------------------------------
`stringPlausibleBase64($value, $message = '')` | Check that a value is plausibly base64  
`validDateTime($value, $message = '')`         | Check that a value is a valid ISO8601 compliant DateTime
`validDateTimeZulu($value, $message = '')`     | Check that a value is a valid ISO8601 compliant DateTime in the UTC timezone
`notInArray($value, $values, $message = '')`   | Check that a value is _NOT_ one of a list of values
`validURI($value, $message = '')`              | Check that a value is a valid RFC3986 URI
`validURL($value, $message = '')`              | Check that a value is a valid RFC2396 URL
`validURN($value, $message = '')`              | Check that a value is a valid RFC8141 URN
`validNCName($value, $message = '')`           | Check that a value is a valid xs:NCName
`validQName($value, $message = '')`            | Check that a value is a valid xs:QName
`validDuration($value, $message = '')`         | Check that a value is a xs:duration
