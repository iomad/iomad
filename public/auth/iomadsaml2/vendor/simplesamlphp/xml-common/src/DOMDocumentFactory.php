<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMDocument;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\{IOException, RuntimeException, UnparseableXMLException};

use function file_get_contents;
use function func_num_args;
use function libxml_clear_errors;
use function libxml_set_external_entity_loader;
use function libxml_use_internal_errors;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
final class DOMDocumentFactory
{
    /**
     * @var non-negative-int
     * TODO: Add LIBXML_NO_XXE to the defaults when PHP 8.4.0 + libxml 2.13.0 become generally available
     */
    public const DEFAULT_OPTIONS = \LIBXML_COMPACT | \LIBXML_NONET | \LIBXML_NSCLEAN;


    /**
     * @param string $xml
     * @param non-negative-int $options
     *
     * @return \DOMDocument
     */
    public static function fromString(
        string $xml,
        int $options = self::DEFAULT_OPTIONS,
    ): DOMDocument {
        libxml_set_external_entity_loader(null);
        Assert::notWhitespaceOnly($xml);
        Assert::notRegex(
            $xml,
            '/<(\s*)!(\s*)DOCTYPE/',
            'Dangerous XML detected, DOCTYPE nodes are not allowed in the XML body',
            RuntimeException::class,
        );

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        // If LIBXML_NO_XXE is available and option not set
        if (func_num_args() === 1 && defined('LIBXML_NO_XXE')) {
            $options |= \LIBXML_NO_XXE;
        }

        $domDocument = self::create();
        $loaded = $domDocument->loadXML($xml, $options);

        libxml_use_internal_errors($internalErrors);

        if (!$loaded) {
            $error = libxml_get_last_error();
            libxml_clear_errors();

            throw new UnparseableXMLException($error);
        }

        libxml_clear_errors();

        foreach ($domDocument->childNodes as $child) {
            Assert::false(
                $child->nodeType === \XML_DOCUMENT_TYPE_NODE,
                'Dangerous XML detected, DOCTYPE nodes are not allowed in the XML body',
                RuntimeException::class,
            );
        }

        return $domDocument;
    }


    /**
     * @param string $file
     * @param non-negative-int $options
     *
     * @return \DOMDocument
     */
    public static function fromFile(
        string $file,
        int $options = self::DEFAULT_OPTIONS,
    ): DOMDocument {
        error_clear_last();
        $xml = @file_get_contents($file);
        if ($xml === false) {
            $e = error_get_last();
            $error = $e['message'] ?? "Check that the file exists and can be read.";

            throw new IOException("File '$file' was not loaded;  $error");
        }

        Assert::notWhitespaceOnly($xml, sprintf('File "%s" does not have content', $file), RuntimeException::class);
        return (func_num_args() < 2) ? static::fromString($xml) : static::fromString($xml, $options);
    }


    /**
     * @param string $version
     * @param string $encoding
     * @return \DOMDocument
     */
    public static function create(string $version = '1.0', string $encoding = 'UTF-8'): DOMDocument
    {
        return new DOMDocument($version, $encoding);
    }
}
