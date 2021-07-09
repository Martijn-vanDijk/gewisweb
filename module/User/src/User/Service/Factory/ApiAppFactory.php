<?php

namespace User\Service\Factory;

use User\Service\ApiApp;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiAppFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiApp
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ApiApp($serviceLocator->get(\User\Mapper\ApiApp::class));
    }
}
