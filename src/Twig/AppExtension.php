<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension; 
use Symfony\Component\HttpFoundation\RequestStack;

class AppExtension extends AbstractExtension
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack=$requestStack;
    }
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', [$this, 'doSomething']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('set_active_route', [$this, 'setActiveRoute']),
            new TwigFunction('pluralize', [$this, 'doSomething']),
        ];
    }

    public function setActiveRoute(string $route,?string $activeClass ='active'):string
    {
        $currentRoute = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        return $currentRoute == $route ? $activeClass : '';
    }

     

    public function doSomething(int $count,string $singular,?string $plural =null):string
    {
        $plural = $plural ?? $singular . 's';
        $str = $count === 1 ? $singular : $plural;
        return "$count $str";
    }

    
}
