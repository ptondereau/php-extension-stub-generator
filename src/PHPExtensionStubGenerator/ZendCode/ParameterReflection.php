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

use Laminas\Code\Reflection\ParameterReflection as BaseParameterReflection;
use ReflectionClass;

class ParameterReflection extends BaseParameterReflection
{
    public function detectType(): ?string
    {
        if (
            method_exists($this, 'getType')
            && null !== ($type = $this->getType())
        ) {
            if ($type instanceof \ReflectionUnionType) {
                return implode('|', $type->getTypes());
            }

            return $type->getName();
        }

        if (null !== $type && $type->getName() === 'self') {
            return $this->getDeclaringClass()->getName();
        }

        if (($class = $this->getClass()) instanceof ReflectionClass) {
            return $class->getName();
        }

        return null;
    }
}
