<?php

namespace App\Tilastot\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;

/**
 * @CronJob("*\/10 * * * *")
 */
class postFacebookToWhatsApp
{
    private $whatsapp_token;
    private $whatsapp_channel;
    public function __construct(string $whatsapp_token, string $whatsapp_channel)
    {
        $this->whatsapp_token = $whatsapp_token;
        $this->whatsapp_channel = $whatsapp_channel;
    }

    public function __invoke(): void
    {
        // Fetch the JSON feed
        $json = file_get_contents('https://rss.app/feeds/v1.1/W3qJOIJMsDZzmagZ.json');
        $data = json_decode($json, true);

        // Load existing IDs
        $idsFile = __DIR__ . '/posted_ids.txt';
        $postedIds = file_exists($idsFile) ? file($idsFile, FILE_IGNORE_NEW_LINES) : [];

        foreach ($data['items'] as $item) {
            if (!in_array($item['id'], $postedIds)) {
                $this->postWhatsApp($item['image'], $item['content_text']);
                file_put_contents($idsFile, $item['id'] . PHP_EOL, FILE_APPEND);
            }
        }
    }

    private function postWhatsApp($image_url, $message_content)
    {
        // Prepare the data for the API call
        $postData = [
            'to' => $this->whatsapp_channel,
            'media' => $image_url,
            'caption' => $message_content
        ];

        // Initialize cURL
        $ch = curl_init('https://api.whapi.cloud/messages/image');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: bearer ' . $this->whatsapp_token
        ]);

        // Execute the API call
        $response = curl_exec($ch);
        curl_close($ch);
    }
}
