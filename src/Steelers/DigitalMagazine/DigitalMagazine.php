<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tilastot\Utils;


use Imagick;
use ImagickPixel;
use ImagickDraw;
use App\Tilastot\Utils\MagazineApi;

require 'MagazineApi.php';
class MagazinePage
{
    protected $canvasWidth = 1320;
    protected $canvasHeight = 2868;
    protected $patternHeight;
    protected $canvas;
    protected $pattern;
    protected $patternPosition;

    public function __construct($patternPosition, $backgroundImage)
    {
        $this->patternPosition = $patternPosition;
        $this->initCanvas();
        $this->addBackground($backgroundImage);
        $this->addBackgroundLayer();
        $this->addBorder();
        if ($this->patternPosition == 'top') {
            $this->addPatternTop();
        } elseif ($this->patternPosition == 'bottom') {
            $this->addPatternBottom();
        } elseif ($this->patternPosition == 'both') {
            $this->addPatternTop();
            $this->addPatternBottom();
        }
    }

    protected function initCanvas()
    {
        $this->canvas = new Imagick();
        $this->canvas->newImage($this->canvasWidth, $this->canvasHeight, new ImagickPixel('white'));
        $this->pattern = new Imagick('pattern.svg');
        $this->pattern->modulateImage(100, 0, 100);
        $this->pattern->negateImage(false);
        $this->patternHeight = $this->pattern->getImageHeight();
    }

    protected function addBackgroundLayer() {}
    protected function addBackground($backgroundImage)
    {
        $background = self::resizeAndCrop($this->loadUrlImage($backgroundImage), $this->canvasWidth, $this->canvasHeight);
        $background->blurImage(50, 20);
        $this->canvas->compositeImage($background, Imagick::COMPOSITE_DEFAULT, 0, 0);
    }

    protected function addBorder()
    {
        // Draw a white 2px border with an offset of 20px from the edge
        $offset = 30;
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel('white'));
        $draw->setStrokeWidth(2);
        $draw->setFillColor('none');
        $offsetTop = 0;
        $offsetBottom = 0;

        if ($this->patternPosition == 'top') {
            $offsetTop = $this->patternHeight / 2;
        } elseif ($this->patternPosition == 'bottom') {
            $offsetBottom = $this->patternHeight / 2;
        } elseif ($this->patternPosition == 'both') {
            $offsetBottom = $this->patternHeight / 2;
            $offsetTop = $this->patternHeight / 2;
        }
        $draw->rectangle($offset, $offset + $offsetTop, $this->canvasWidth - $offset, $this->canvasHeight - $offset - $offsetBottom);
        $this->canvas->drawImage($draw);
    }
    protected function addPatternBottom()
    {
        $gradientMask = new Imagick();
        $gradientMask->newPseudoImage($this->canvasWidth, $this->patternHeight / 2, "gradient:black-white");

        // Apply the gradient mask to the pattern
        $this->pattern->compositeImage($gradientMask, Imagick::COMPOSITE_COPYOPACITY, 0, 0);

        // Load the pattern image and place it at the bottom of the canvas
        $this->canvas->compositeImage($this->pattern, Imagick::COMPOSITE_OVER, 0, $this->canvasHeight - ($this->patternHeight / 2));

        $gradientMask->destroy();
    }

    protected function addPatternTop()
    {
        $gradientMask = new Imagick();
        $gradientMask->newPseudoImage($this->canvasWidth, $this->patternHeight / 2, "gradient:white-black");

        // Apply the gradient mask to the pattern
        $this->pattern->compositeImage($gradientMask, Imagick::COMPOSITE_COPYOPACITY, 0, ($this->patternHeight / 2) + 1);

        // Load the pattern image and place it at the bottom of the canvas
        $this->canvas->compositeImage($this->pattern, Imagick::COMPOSITE_OVER, 0, - ($this->patternHeight / 2));

        $gradientMask->destroy();
    }

    protected function loadUrlImage($url)
    {
        $data = file_get_contents($url);
        $image = new Imagick();
        $image->readImageBlob($data);
        return $image;
    }

    protected function generateImage()
    {
        $this->canvas->setImageFormat('png');
        return $this->canvas->getImagesBlob();
    }

    public function saveImage($filePath)
    {
        $imageData = $this->generateImage();
        file_put_contents($filePath, $imageData);
    }

    public function getCanvas()
    {
        return $this->canvas;
    }

    public static function generateAndSave($patternPosition, $backgroundImage, $outputFilePath)
    {
        $magazinePage = new MagazinePage($patternPosition, $backgroundImage);
        $magazinePage->saveImage($outputFilePath);
        echo "Image saved to " . $outputFilePath . PHP_EOL;
    }

    public static function resizeAndCrop($image, $width, $height)
        {
            // Calculate the aspect ratios
            $originalWidth = $image->getImageWidth();
            $originalHeight = $image->getImageHeight();
            $originalAspect = $originalWidth / $originalHeight;
            $newAspect = $width / $height;

            // Resize the image to cover the area
            if ($originalAspect > $newAspect) {
                // Wider than needed
                $image->resizeImage(0, $height, Imagick::FILTER_LANCZOS, 1);
            } else {
                // Taller than needed
                $image->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
            }

            // Crop the image to the exact dimensions
            $image->cropImage($width, $height, ($image->getImageWidth() - $width) / 2, ($image->getImageHeight() - $height) / 2);
            $image->setImagePage($width, $height, 0, 0);
            return $image;
        }
}

class FrontPage extends MagazinePage
{

    private $game = null;

    public function __construct($backgroundImage)
    {
        parent::__construct('bottom', $backgroundImage);
        $this->loadGame();
        $this->addText();
        $this->addCenter($backgroundImage);
        $this->addBottomText();
    }

    private function addText()
    {
        // Add text "Steelers News" with the font "Blue Ridge"
        $textDraw = new ImagickDraw();
        $textDraw->setFont('Blue Ridge.otf');
        $textDraw->setFontSize(110);
        $textDraw->setFillColor(new ImagickPixel('white'));
        $textDraw->setGravity(Imagick::GRAVITY_CENTER);

        // Calculate text dimensions
        $imagick = new Imagick();
        $metrics = $imagick->queryFontMetrics($textDraw, 'Steelers News');

        // Determine bottom-left corner coordinates
        $bottomLeftX = (($this->canvasWidth - $metrics['textWidth']) / 2) + 3;
        $bottomLeftY = $metrics['textHeight'] + 70;

        // Annotate image
        $this->getCanvas()->annotateImage($textDraw, 0, - ($this->canvasHeight / 2) + 150, 0, 'Steelers News');


        // Add text "digital" with the font "Mayonice"
        $textDraw = new ImagickDraw();
        $textDraw->setFont('Mayonice.otf');
        $textDraw->setFontSize(130);
        $textDraw->setFillColor(new ImagickPixel('green'));
        $textDraw->setGravity(Imagick::GRAVITY_CENTER);
        $this->getCanvas()->annotateImage($textDraw, 0, - ($this->canvasHeight / 2) + 200, 0, 'digital');

        $textDraw = new ImagickDraw();
        $textDraw->setFont('Blue Ridge.otf');
        $textDraw->setFontSize(26);
        $textDraw->setFillColor(new ImagickPixel('white'));
        $textDraw->setFontStretch(Imagick::STRETCH_CONDENSED);
        $this->getCanvas()->annotateImage($textDraw, $bottomLeftX, $bottomLeftY, 0, $this->game->gameData->scheduledDate->formattedShort);
        // Calculate the width of the new text
        $metrics = $imagick->queryFontMetrics($textDraw, 'Ausgabe 18/2025');

        // Calculate the right-aligned position
        $rightAlignedX = $this->canvasWidth - $metrics['textWidth'] - $bottomLeftX; // 10px padding from the right edge

        // Annotate the new text
        $this->getCanvas()->annotateImage($textDraw, $rightAlignedX, $bottomLeftY, 0, 'Ausgabe 18/2025');
    }

    private function addCenter($backgroundImage)
    {
        // Create a new Imagick object for the background
        $side = (0.8 * $this->canvasWidth);
        $centerImageWidth = $this->canvasWidth * 0.85;
        $centerImageHeight = $this->canvasWidth * 1.05;
        $gap = 10;
        $smallSide = ($side - (3 * $gap)) / 2;
        $background = new Imagick();
        $background->newImage($centerImageWidth, $centerImageHeight, new ImagickPixel('transparent'));
        $background->setImageFormat('png');

        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel('white'));
        $draw->setStrokeWidth(2);
        $draw->setFillColor('none');
        $draw->rectangle(1, 1, $centerImageWidth - 1, $centerImageHeight - 1);
        $background->drawImage($draw);

        // Add content to the grid elements
        //$images[0]->compositeImage(resizeAndCrop($this->loadUrlImage($this->game->gameData->images->homeTeamLogo), $smallSide, $smallSide), Imagick::COMPOSITE_OVER, 0, 0);
        //$images[1]->compositeImage(resizeAndCrop($this->loadUrlImage('https://steelers.de/files/steelers/news/2025/01/20241210_045__SCB3062.jpg'), $smallSide, $smallSide), Imagick::COMPOSITE_OVER, 0, 0);
        //$images[2]->compositeImage(resizeAndCrop($this->loadUrlImage('https://steelers.de/files/steelers/news/2025/01/20250117_007__SCB0441.jpg'), $smallSide, $smallSide), Imagick::COMPOSITE_OVER, 0, 0);
        //$images[3]->compositeImage(resizeAndCrop($this->loadUrlImage($this->game->gameData->images->awayTeamLogo), $smallSide, $smallSide), Imagick::COMPOSITE_OVER, 0, 0);

        // Create a new Imagick object for the grid
        $image = self::resizeAndCrop($this->loadUrlImage($backgroundImage), $centerImageWidth - 2*$gap, $centerImageHeight - 2 * $gap);
        $background->compositeImage($image, Imagick::COMPOSITE_OVER, $gap, $gap);


        // Composite the images onto the grid
        // $grid->compositeImage($images[1], Imagick::COMPOSITE_OVER, ($smallSide + 2 * $gap), $gap);
        // $grid->compositeImage($images[2], Imagick::COMPOSITE_OVER, $gap, ($smallSide + 2 * $gap));
        // $grid->compositeImage($images[3], Imagick::COMPOSITE_OVER, ($smallSide + 2 * $gap), ($smallSide + 2 * $gap));

        $background->rotateImage(new ImagickPixel('none'), -1); // Rotate by 15 degrees

        $x = ($this->canvasWidth - $background->getImageWidth()) / 2;
        $y = 380;
        $this->getCanvas()->compositeImage($background, Imagick::COMPOSITE_OVER, $x, $y);
        
    }

    private function addBottomText() {
        $top = 500;
        $textDraw = new ImagickDraw();
        $textDraw->setFont('Blue Ridge.otf');
        $textDraw->setFontSize(80);
        $textDraw->setFillColor(new ImagickPixel('white'));
        $textDraw->setStrokeColor(new ImagickPixel('black'));
        $textDraw->setStrokeWidth(2);
        $textDraw->setFontStretch(Imagick::STRETCH_ULTRACONDENSED);
        $textDraw->setGravity(Imagick::GRAVITY_CENTER);
        $this->getCanvas()->annotateImage($textDraw, 0, $top, 0, $this->game->gameData->homeTeamLongname);
        $this->getCanvas()->annotateImage($textDraw, 0, $top+100, 0, $this->game->gameData->awayTeamLongname);

        $textDraw = new ImagickDraw();
        $textDraw->setFont('Mayonice.otf');
        $textDraw->setFontSize(200);
        $textDraw->setFillColor(new ImagickPixel('green'));
        $textDraw->setStrokeColor(new ImagickPixel('white'));
        $textDraw->setStrokeWidth(1);
        $textDraw->setGravity(Imagick::GRAVITY_CENTER);
        $this->getCanvas()->annotateImage($textDraw, 0, $top+40, 0, 'vs.');
    }

    private function addLogos()
    {
        $awayTeamLogoUrl = $this->game->gameData->images->awayTeamLogo;
        $logo_away = $this->loadUrlImage($awayTeamLogoUrl);
        $this->canvas->compositeImage($logo_away, Imagick::COMPOSITE_DEFAULT, 300, 300);
    }

    private function loadGame()
    {
        $game = MagazineApi::getNextHomeGame();
        $this->game = $game;
    }

    protected function addBackgroundLayer()
    {
        $draw = new ImagickDraw();
        $draw->setStrokeWidth(0);
        $draw->setFillColor('black');
        $draw->rectangle(0, 0, $this->canvasWidth, 200);
        $this->canvas->drawImage($draw);

        $gradient = new Imagick();
        $gradient->newPseudoImage($this->canvasWidth, 220, "gradient:black-transparent");

        // Apply the gradient mask to the pattern
        $this->canvas->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, 200);
    }

    public static function generate($backgroundImage, $outputFilePath)
    {
        $frontPage = new FrontPage($backgroundImage);
        $frontPage->saveImage($outputFilePath);
    }
}

// CLI execution
if (php_sapi_name() == 'cli') {
    FrontPage::generate('https://steelers.de/files/steelers/news/2025/01/20241210_045__SCB3062.jpg', 'page_01.png');
    MagazinePage::generateAndSave('both', 'https://steelers.de/files/steelers/news/2025/01/20250110__SCB8987-2.jpg', 'page_02.png');
}

// Jubelbild: https://steelers.de/files/steelers/news/2025/01/20250110__SCB8987-2.jpg