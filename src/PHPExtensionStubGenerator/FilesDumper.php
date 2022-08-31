<?php
declare(strict_types=1);

namespace PHPExtensionStubGenerator;

use AppendIterator;
use ArrayIterator;
use Generator;
use Iterator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Reflection\ClassReflection;
use PHPExtensionStubGenerator\ZendCode\{FunctionGenerator, FunctionReflection};
use ReflectionExtension;
use RuntimeException;

final class FilesDumper
{
    private const CONST_FILENAME = '%s/const.php';
    private const FUNCTIONS_FILENAME = '%s/functions.php';
    private const CLASS_FILENAME = '%s.php';

    private DocBlockGenerator $docBlockGenerator;

    public function __construct(private ReflectionExtension $reflectionExtension)
    {
        $this->docBlockGenerator = new DocBlockGenerator('auto generated file by PHPExtensionStubGenerator');
    }

    public function dumpFiles(string $dir): void
    {
        $generates = $this->getGenerationTargets();

        foreach ($generates as $fileName => $code) {
            $pathinfo = pathinfo($fileName);
            $codeDir = $dir . DIRECTORY_SEPARATOR . $pathinfo['dirname'];
            if (!file_exists($codeDir) && !mkdir($codeDir, 0777, true) && !is_dir($codeDir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $codeDir));
            }

            $code = $this->docBlockGenerator->generate() . $code;
            file_put_contents($codeDir . DIRECTORY_SEPARATOR . $pathinfo['basename'], "<?php\n$code");
        }
    }

    protected function getGenerationTargets() : Iterator
    {
        $generates = new AppendIterator();
        $generates->append(new ArrayIterator($this->generateConstants()));
        $generates->append(new ArrayIterator($this->generateFunctions()));
        $generates->append($this->generateClasses());

        return $generates;
    }

    public function generateConstants(): array
    {
        $reflectionConstants = $this->reflectionExtension->getConstants();

        $constantsFiles = [];
        foreach ($reflectionConstants as $constant => $value) {
            $c = preg_split('#\\\#', $constant);

            // has namespace ?
            if (count($c) > 1) {
                [$namespaces, $constName] = array_chunk($c, count($c) - 1);
                $constName = current($constName);

                $namespaceFilename = sprintf(self::CONST_FILENAME, implode(DIRECTORY_SEPARATOR, $namespaces));
                if (!isset($constantsFiles[$namespaceFilename])) {
                    $constantsFiles[$namespaceFilename] = 'namespace '. implode('\\', $namespaces) . ";\n\n";
                }
            } else {
                $namespaceFilename = sprintf(self::CONST_FILENAME, '');
                if (!isset($constantsFiles[$namespaceFilename])) {
                    $constantsFiles[$namespaceFilename] = '';
                }

                $constName = $constant;
            }

            $encodeValue = is_string($value) ? sprintf('"%s"', $value) : $value;
            $constantsFiles[$namespaceFilename] .= "const $constName = {$encodeValue};\n";
        }

        return $constantsFiles;
    }

    public function generateClasses() : Generator
    {
        /** @var \ReflectionClass $phpClassReflection */
        foreach ($this->reflectionExtension->getClasses() as $fqcn => $phpClassReflection) {
            $classGenerator = ClassGenerator::fromReflection(new ClassReflection($phpClassReflection->getName()));

            yield self::fqcnToFilename($fqcn) => $classGenerator->generate();
        }
    }

    public function generateFunctions() : array
    {
        $functionFiles = [];
        foreach ($this->reflectionExtension->getFunctions() as $function_name => $phpFunctionReflection) {

            $functionReflection = new FunctionReflection($function_name);

            $function_filename = sprintf(self::FUNCTIONS_FILENAME, str_replace('\\', '/', $functionReflection->getNamespaceName()));

            if (isset($functionFiles[$function_filename])) {
                $functionFiles[$function_filename] .= "\n".
                    FunctionGenerator::generateByPrototypeArray($functionReflection->getPrototype());
            } else {
                $namespaceLine = '';
                if ($namespace = $functionReflection->getNamespaceName()) {
                    $namespaceLine = "namespace {$namespace};";
                }
                $functionFiles[$function_filename] = $namespaceLine . "\n" .
                    FunctionGenerator::generateByPrototypeArray($functionReflection->getPrototype());
            }
        }

        return $functionFiles;
    }

    private static function fqcnToFilename(string $fqcn) :string 
    {
        return sprintf(self::CLASS_FILENAME, str_replace('\\', '/', $fqcn));
    }
}
