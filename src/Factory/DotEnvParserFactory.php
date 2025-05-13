<?php declare(strict_types=1);

namespace AlanVdb\Server\Factory;

use AlanVdb\Server\Definition\DotEnvParserFactoryInterface;
use AlanVdb\Server\Definition\DotEnvParserInterface;
use AlanVdb\Server\DotEnvParser;

class DotEnvParserFactory implements DotEnvParserFactoryInterface
{
	public function create(string $root) : DotEnvParserInterface
	{
        return new DotEnvParser($root);
    }
}
