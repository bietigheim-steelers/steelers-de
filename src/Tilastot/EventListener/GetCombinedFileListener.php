<?php

namespace App\Tilastot\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;

/**
 * @Hook("getCombinedFile")
 */
class GetCombinedFileListener
{
    public function __invoke(string $content, string $key, string $mode, array $file): string
    {

        $htaccess_file = __DIR__ . '/../../../public/.htaccess';
        $htaccess_data = file_get_contents($htaccess_file);
        preg_match_all('/^H2PushResource.*css$/m', $htaccess_data, $output_array);
        if (count($output_array[0]) > 0) {
            // found it, replace
            $htaccess_data = preg_replace('/^H2PushResource.*css$/m', 'H2PushResource /assets/css/' . $key . $mode, $htaccess_data);
        } else {
            // not found, add it:
            $htaccess_data .= PHP_EOL . 'H2PushResource /assets/css/' . $key . $mode . PHP_EOL;
        }

        // deactivate during development!
        // file_put_contents($htaccess_file, $htaccess_data);


        return $content;
    }
}