<?PHP

namespace blacksenator\sff;

require_once 'sffReader.php';

use blacksenator\sff\sffReader;

/**
 * This class provides functions to convert sff page data (fax image) into more
 * common readable file formats
 *
 * @author Volker Püschel <knuffy@anasco.de>
 * @copyright Volker Püschel 2022
 * @license MIT
 */

class sffImager
{
    private $sFFile;
    private $pageImage;

    /**
     * setting the white canvas of page image according to dimensions given in
     * the page header
     *
     * @param array $header
     * @return void
     */
    private function setWhiteCanvas(array $header)
    {
        $this->pageImage = imagecreatetruecolor($header['LineLength'], $header['PageLength']);
        imagefill($this->pageImage, 0, 0, imagecolorallocate($this->pageImage, 255, 255, 255));
    }

    /**
     * draws the image line by line
     *
     * @param array $page
     * @return void
     */
    private function getPageImage(array $page)
    {
        $this->setWhiteCanvas($page['PageHeader']);
        $black = imagecolorallocate($this->pageImage, 0, 0, 0);
        foreach ($page['PageLines'] as $y => $pageLine) {
            foreach ($pageLine as $start => $length) {
                imageline($this->pageImage, $start, $y, ($start + $length), $y, $black);
            }
        }
    }

    /**
     * return a SFF as an array of PNGs
     *
     * @param string $fileData
     * @return array
     */
    public function getSFFasPNG(string $fileData)
    {
        $images = [];
        $this->sFFile = new sffReader($fileData);
        foreach ($this->sFFile->getPages() as $page) {
            $this->getPageImage($page);
            ob_start();
            imagepng($this->pageImage);
            $images[] = ob_get_clean();
            imagedestroy($this->pageImage);
        }

        return $images;
    }
}
