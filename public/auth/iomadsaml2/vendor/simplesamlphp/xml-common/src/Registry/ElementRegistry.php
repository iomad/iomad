<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Registry;

use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\IOException;
use Symfony\Component\Finder\Finder;

use function array_merge_recursive;
use function dirname;
use function file_exists;

final class ElementRegistry
{
    /** @var \SimpleSAML\XML\Registry\ElementRegistry|null $instance */
    private static ?ElementRegistry $instance = null;

    /** @var array<string, array<string, string>> */
    private array $registry = [];


    private function __construct()
    {
        // Initialize the registry with all the elements we know
        $classesDir = dirname(__FILE__, 6) . '/vendor/simplesamlphp/composer-xmlprovider-installer/classes';

        if (file_exists($classesDir) === true) {
            $finder = Finder::create()->files()->name('element.registry.*.php')->in($classesDir);
            if ($finder->hasResults()) {
                foreach ($finder as $file) {
                    $this->importFromFile($file->getPathName());
                }
            }
        }
    }


    public function importFromFile(string $file): void
    {
        if (file_exists($file) === true) {
            $elements = include($file);
            $this->registry = array_merge_recursive($this->registry, $elements);
        } else {
            throw new IOException('File not found.');
        }
    }


    public static function getInstance(): ElementRegistry
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }


    /**
     * Register a class that can process a certain XML-element.
     *
     * @param string $class The class name of a class extending AbstractElement.
     */
    public function registerElementHandler(string $class): void
    {
        Assert::subclassOf($class, AbstractElement::class);
        $className = AbstractElement::getClassName($class);
        $namespace = $class::NS;

        $this->registry[$namespace][$className] = $class;
    }


    /**
     * Search for a class that implements an $element in the given $namespace.
     *
     * Such classes must have been registered previously by calling registerElementHandler(), and they must
     * extend \SimpleSAML\XML\AbstractElement.
     *
     * @param string|null $namespace The namespace URI for the given element.
     * @param string $element The local name of the element.
     *
     * @return string|null The fully-qualified name of a class extending \SimpleSAML\XML\AbstractElement and
     * implementing support for the given element, or null if no such class has been registered before.
     */
    public function getElementHandler(?string $namespace, string $element): ?string
    {
        Assert::nullOrValidURI($namespace, InvalidDOMElementException::class);
        Assert::validNCName($element, InvalidDOMElementException::class);

        return $this->registry[$namespace][$element] ?? null;
    }
}
