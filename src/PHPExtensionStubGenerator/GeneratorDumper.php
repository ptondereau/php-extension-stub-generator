<?php
declare(strict_types=1);

namespace PHPExtensionStubGenerator;

use Generator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Reflection\ClassReflection;
use ReflectionExtension;
use PHPExtensionStubGenerator\ZendCode\ {
    FunctionGenerator, FunctionReflection
};

class GeneratorDumper
{
    private DocBlockGenerator $docBlockGenerator;

    public function __construct(private ReflectionExtension $reflectionExtension)
    {
        $this->docBlockGenerator = new DocBlockGenerator('auto generated file by PHPExtensionStubGenerator');
    }

    public function getGenerates(): Generator
    {
        yield from $this->generateConstants();
        yield from $this->generateFunctions();
        yield from $this->generateClasses();
    }

    public function setDocBlockGenerator(DocBlockGenerator $docBlockGenerator) : void
    {
        $this->docBlockGenerator = $docBlockGenerator;
    }

    public function generateConstants() : Generator
    {
        $reflectionConstants = $this->reflectionExtension->getConstants();

        $declaredNamespaces = [];
        foreach ($reflectionConstants as $constant => $value) {
            $c = preg_split('#\\\#', $constant);

            // has namespace ?
            if (count($c) > 1) {
                list($namespaces, $constName) = array_chunk($c, count($c)-1);
                $constName = current($constName);

                $namespace = implode('\\', $namespaces);
                if (!isset($declaredNamespaces[$namespace])) {
                    $declaredNamespaces[$namespace] = true;
                    yield "namespace {$namespace};";
                }

                $encodeValue = is_string($value) ? sprintf('"%s"', $value) : $value;
                yield "const $constName = {$encodeValue};";
            } else {
                $encodeValue = is_string($value) ? sprintf('"%s"', $value) : $value;
                yield "const $constant = {$encodeValue};";
            }
        }

        return '';
    }

    public function generateClasses(): Generator
    {
        /** @var \ReflectionClass $phpClassReflection */
        foreach ($this->reflectionExtension->getClasses() as $fqcn => $phpClassReflection) {
            $classGenerator = ClassGenerator::fromReflection(new ClassReflection($phpClassReflection->getName()));

            yield $classGenerator->generate();
        }

        return '';
    }

    public function generateFunctions(): Generator
    {
        $declaredNamespaces = [];
        foreach ($this->reflectionExtension->getFunctions() as $function_name => $phpFunctionReflection) {

            $functionReflection = new FunctionReflection($function_name);

            $namespace = $functionReflection->getNamespaceName();
            if ($namespace && !isset($declaredNamespaces[$namespace])) {
                $declaredNamespaces[$namespace] = true;
                yield "namespace {$namespace};";
            }

            yield FunctionGenerator::generateByPrototypeArray($functionReflection->getPrototype());
        }

        return '';
    }
}
