<?php

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class VectorType extends Type
{
    const VECTOR = 'vector'; // custom type name

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'VECTOR(1024)';
    }

    public function getName()
    {
        return self::VECTOR;
    }
}