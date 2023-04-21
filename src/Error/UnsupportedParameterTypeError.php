<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Error;

class UnsupportedParameterTypeError extends \InvalidArgumentException implements ArgumentResolverError 
{
}
