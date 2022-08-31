<?php
declare(strict_types=1);

/**
 * most of parts is borrowed from zendframework/zend-code
 * https://github.com/zendframework/zend-code
 *
 * This source is aimed for hack to override zend-code.
 *
 * @license New BSD, code from Zend Framework
 * https://github.com/zendframework/zend-code/blob/master/LICENSE.md
 */

namespace PHPExtensionStubGenerator\ZendCode;

use Laminas\Code\Reflection\DocBlockReflection;
use Laminas\Code\Reflection\FunctionReflection as BaseFunctionReflection;

class FunctionReflection extends BaseFunctionReflection
{
    /**
     * @return DocBlockReflection|string
     */
    public function getDocBlock()
    {
        if ('' === ($comment = $this->getDocComment())) {
            return '';
        }

        if (false === $comment) {
            return '';
        }

        return new DocBlockReflection($comment);
    }

    public function getParameters(): array
    {
        $phpReflections  = parent::getParameters();
        $zendReflections = [];
        while ($phpReflections && ($phpReflection = array_shift($phpReflections))) {
            $instance          = new ParameterReflection($this->getName(), $phpReflection->getName());
            $zendReflections[] = $instance;
            unset($phpReflection);
        }
        unset($phpReflections);

        return $zendReflections;
    }

    /**
     * @return array|string
     */
    public function getPrototype($format = BaseFunctionReflection::PROTOTYPE_AS_ARRAY)
    {
        $docBlock    = $this->getDocBlock();
        $returnType = 'mixed';

        if ($docBlock instanceof DocBlockReflection) {
            $return      = $docBlock->getTag('return');
            $returnTypes = $return->getTypes();
            $returnType  = count($returnTypes) > 1 ? implode('|', $returnTypes) : $returnTypes[0];
        }

        $prototype = [
            'namespace' => $this->getNamespaceName(),
            'name'      => substr($this->getName(), strlen($this->getNamespaceName()) + 1),
            'return'    => $returnType,
            'arguments' => [],
        ];

        $parameters = $this->getParameters();
        foreach ($parameters as $parameter) {
            $prototype['arguments'][$parameter->getName()] = [
                'type'     => $parameter->detectType(),
                'required' => ! $parameter->isOptional(),
                'by_ref'   => $parameter->isPassedByReference(),
                'default'  => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            ];
        }

        if ($format === self::PROTOTYPE_AS_STRING) {
            $line = $prototype['return'] . ' ' . $prototype['name'] . '(';
            $args = [];
            foreach ($prototype['arguments'] as $name => $argument) {
                $argsLine = ($argument['type']
                        ? $argument['type'] . ' '
                        : '') . ($argument['by_ref'] ? '&' : '') . '$' . $name;
                if (! $argument['required']) {
                    $argsLine .= ' = ' . var_export($argument['default'], true);
                }
                $args[] = $argsLine;
            }
            $line .= implode(', ', $args);
            $line .= ')';

            return $line;
        }

        return $prototype;
    }
}
