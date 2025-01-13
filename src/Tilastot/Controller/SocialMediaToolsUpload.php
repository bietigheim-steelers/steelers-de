<?php
namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Contao\CoreBundle\Framework\ContaoFramework;

class SocialMediaToolsUpload {
  public function upload(Request $request, ContaoFramework $framework): Response
  {
    
    $framework->initialize();
    $data = $request->request->all();


    return new Response('upload successful');
  }
}