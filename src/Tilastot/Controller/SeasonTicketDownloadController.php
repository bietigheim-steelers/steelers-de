<?php
namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/%contao.backend.route_prefix%/season_ticket_download_excel', name: self::class, defaults: ['_scope' => 'backend'])]
class SeasonTicketDownloadController {
 
  public function __invoke(): Response {
    return new Response('Hello World!');
  }
}