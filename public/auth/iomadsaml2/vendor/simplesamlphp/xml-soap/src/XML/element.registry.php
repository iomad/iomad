<?php

declare(strict_types=1);

return [
    'http://schemas.xmlsoap.org/soap/envelope/' => [
        'Body' => '\SimpleSAML\SOAP\XML\env_200106\Body',
        'Envelope' => '\SimpleSAML\SOAP\XML\env_200106\Envelope',
        'Fault' => '\SimpleSAML\SOAP\XML\env_200106\Fault',
        'Header' => '\SimpleSAML\SOAP\XML\env_200106\Header',
    ],
    'http://www.w3.org/2003/05/soap-envelope' => [
        'Body' => '\SimpleSAML\SOAP\XML\env_200305\Body',
        'Envelope' => '\SimpleSAML\SOAP\XML\env_200305\Envelope',
        'Fault' => '\SimpleSAML\SOAP\XML\env_200305\Fault',
        'Header' => '\SimpleSAML\SOAP\XML\env_200305\Header',
        'NotUnderstood' => '\SimpleSAML\SOAP\XML\env_200305\NotUnderstood',
        'Upgrade' => '\SimpleSAML\SOAP\XML\env_200305\Upgrade',
    ],
];
