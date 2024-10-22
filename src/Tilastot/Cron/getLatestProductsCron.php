<?php
namespace App\Tilastot\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;

use Automattic\WooCommerce\Client;

/**
 * @CronJob("hourly")
 */
class getLatestProductsCron
{
    private $woo_key;
    private $woo_secret;

    public function __construct(string $woo_key, string $woo_secret)
    {
        $this->woo_key = $woo_key;
        $this->woo_secret = $woo_secret;
    }

    public function __invoke(): void
    {
        $woocommerce = new Client(
            'https://shop.steelers.de',
            $this->woo_key,
            $this->woo_secret,
            [
              'version' => 'wc/v3',
            ]
        );
        $shop_file = __DIR__ . '../../../shopdata.json';
        file_put_contents($shop_file, json_encode($woocommerce->get('products')));
    }
}
