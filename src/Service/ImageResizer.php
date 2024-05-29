<?php
// src/Service/ImageResizer.php
namespace App\Service;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\HttpFoundation\File\File;
use Imagine\Gd\Imagine; // ou \Imagine\Imagick\Imagine selon votre configuration

class ImageResizer
{
    private $cacheManager;
    private $filterManager;
    private $imagine;

    public function __construct(CacheManager $cacheManager, FilterManager $filterManager)
    {
        $this->cacheManager = $cacheManager;
        $this->filterManager = $filterManager;
        $this->imagine = new Imagine(); // Initialisez Imagine
    }

    public function resizeImage(File $file, $filter = 'recu')
    {
        $path = $file->getPathname();

        // Ouvrir l'image avec Imagine
        $image = $this->imagine->open($path);

        // Convertir l'image en une instance de Binary
        $binary = new Binary(
            $image->get('png'), // Format de sortie, ici 'jpg', changez si nécessaire
            'image/png', // Type MIME correspondant
            'png' // Format de sortie, ici 'jpg', changez si nécessaire
        );

        // Appliquer le filtre
        $filteredBinary = $this->filterManager->applyFilter($binary, $filter);

        // Sauvegarder l'image filtrée
        file_put_contents($path, $filteredBinary->getContent());

        return $file;
    }
}
