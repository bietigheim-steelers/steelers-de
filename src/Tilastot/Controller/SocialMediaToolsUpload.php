<?php
namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Contao\CoreBundle\Framework\ContaoFramework;

class SocialMediaToolsUpload {
  private $smt_tennant_id;
  private $smt_client_id;
  private $smt_client_secret;
  private $smt_team_id;
  public function __construct(string $smt_tennant_id, string $smt_client_id, string $smt_client_secret, string $smt_team_id)
  {
    $this->smt_tennant_id = $smt_tennant_id;
    $this->smt_client_id = $smt_client_id;
    $this->smt_client_secret = $smt_client_secret;
    $this->smt_team_id = $smt_team_id;
  }
  public function upload(Request $request, ContaoFramework $framework): Response
  {
    
    $framework->initialize();
    $data = $request->request->all();


    return new Response('upload successful');
  }
}