<?php

namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Contao\CoreBundle\Framework\ContaoFramework;

class SocialMediaToolsUpload
{
  private $smt_tennant_id;
  private $smt_client_id;
  private $smt_client_secret;
  private $smt_team_id;
  private $bearer_token;
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
    $this->setBearerToken();

    $date = $request->request->get('date');
    $opponent = $request->request->get('opponent');
    $images = $request->files->get('images');

    if (!$date || !$opponent || !$images) {
      return new Response(json_encode(['error' => 'Missing required parameters']), Response::HTTP_BAD_REQUEST);
    }

    // Process each image
    foreach ($images as $image) {
      $imageContent = file_get_contents($image->getPathname());
      $imageName = $image->getClientOriginalName();

      // Make a PUT call to upload the image to the specified endpoint
      $gameday_folder = date('Y-m-d', $date) . '_' . $opponent;
      $url = 'https://graph.microsoft.com/v1.0/drives/' . $this->smt_team_id . '/root:/Organisation/Spieltage/' .$gameday_folder. '/' . $imageName . ':/content';

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $this->bearer_token,
        'Content-Type: application/octet-stream'
      ]);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $imageContent);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($httpCode !== 201) {
        return new Response(json_encode(['error' => 'Failed to upload image', 'details' => $response]), Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      curl_close($ch);
    }

    return new Response(json_encode(['message' => 'Upload successful', 'date' => $date, 'opponent' => $opponent]), Response::HTTP_OK);
  }

  private function setBearerToken()
  {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://login.microsoftonline.com/' . $this->smt_tennant_id . '/oauth2/v2.0/token',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => http_build_query(array(
        'grant_type' => 'client_credentials',
        'client_id' => $this->smt_client_id,
        'client_secret' => $this->smt_client_secret,
        'scope' => 'https://graph.microsoft.com/.default'
      )),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response_data = json_decode($response, true);
    $this->bearer_token = $response_data['access_token'];
  }
}
