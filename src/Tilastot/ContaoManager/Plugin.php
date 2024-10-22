<?php

namespace App\Tilastot\ContaoManager;

use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements RoutingPluginInterface
{
  public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
  {
    return $resolver
      ->resolve(__DIR__ . '/../../../config/routes.yaml')
      ->load(__DIR__ . '/../../../config/rogwrgwrutes.yaml');
  }
}
