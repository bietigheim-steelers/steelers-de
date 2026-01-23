<?php
namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 * @internal
 */
class SeasonTicketDownloadController {

  /**
   * @Route("/contao/seasontickets/download", name="backend_season_ticket_download")
   */
  public function triggerDownload(): Response {
    return new Response('Hello World!');
  }
}