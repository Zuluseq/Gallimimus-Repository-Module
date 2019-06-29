<?php

declare(strict_types=1);

namespace GallimimusRepositoryModule\Handler;

use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\AdapterInterface;

class GetFactory
{
    public function __invoke(ContainerInterface $container) : Get
    {
        return new Get(
				$container->get('config'),
				$container->get(AdapterInterface::class)
				);
    }
}
