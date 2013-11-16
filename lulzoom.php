<?php

class Lulzoom {

    protected $_combined;
    protected $_x;
    protected $_y;
    protected $_w;
    protected $_h;
    protected $_steps;
    protected $_originalDims;

    function __construct($options) {
        // Instantiate the end image which will be a combination of our zoomed images
        $this->_combined = new Imagick();
        $this->_combined->newImage(0, 0, new ImagickPixel('black'));

        // Store the target dimensions
        $this->_x = $options['x'];
        $this->_y = $options['y'];
        $this->_w = $options['w'];
        $this->_h = $options['h'];

        // TODO validate steps
        $this->_steps = $options['steps'];

        // Upload file, bail out if this fails
        if (!$this->_fp = $this->_handleFileUpload($options['file'])) {
            $this->_log(sprintf('Failed to upload file with details: %s', print_r($options['file'], true)));
            $this->_log('Exiting');
            exit;
        }

        $this->_originalDims = $this->_getOriginalImageDimensions();
    }

    function __destruct() {
        $this->_combined = null;
    }

    public function generateImage() {
        for ($i = 0; $i <= $this->_steps; $i++) {

            // Work towards our target values in increments
            $x = $this->_x / $this->_steps * $i;
            $y = $this->_y / $this->_steps * $i;
            $wDiff = $this->_originalDims['width'] - $this->_w;
            $w = $this->_originalDims['width'] - ($wDiff / $this->_steps * $i);
            $hDiff = $this->_originalDims['height'] - $this->_h;
            $h = $this->_originalDims['height'] - ($hDiff / $this->_steps * $i);

            $this->_appendZoomedImage($x, $y, $w, $h);
        }
    }

    public function outputImage() {
        $this->_combined->flattenImages();
        $this->_combined->setFormat('jpg');
        $this->_combined->writeImage('out.jpg');
    }

    protected function _getOriginalImageDimensions() {
        try {
            $image = new Imagick($this->_fp);
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
        } catch (Exception $e) {
            $this->_log(sprintf('Failed to get original image dimensions (%s)', $e->getMessage()));
            exit;
        }

        return array('width' => $w, 'height' => $h);
    }

    protected function _appendZoomedImage($x, $y, $w, $h) {
        try {
            $image = new Imagick($this->_fp);
            $image->cropImage($w, $h, $x, $y);
            $image->resizeImage($this->_originalDims['width'], $this->_originalDims['height'], Imagick::FILTER_GAUSSIAN, 1);
            $this->_combined->setImageExtent($image->getImageWidth(), $this->_combined->getImageHeight() + $image->getImageHeight());
            $this->_combined->compositeImage($image, Imagick::COMPOSITE_DEFAULT, 0, $this->_combined->getImageHeight() - $image->getimageheight());
        } catch (Exception $e) {
            $this->_log(sprintf('Failed to generate zoom level (%s)', $e->getMessage()));
            exit;
        }
    }

    protected function _handleFileUpload($file) {
        if ($file['error'] != 0) return false;

        // TODO VALIDATE
        $fp = 'upload/'.$_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $fp);
        return $fp;
    }

    protected function _log($msg) {
        file_put_contents('lulzoom.log', $msg . "\n", FILE_APPEND | LOCK_EX);
    }

}

ini_set('display_errors', E_ALL);

$x = $_POST['x'];
$y = $_POST['y'];
$w = $_POST['w'];
$h = $_POST['h'];

//TODO Validate steps, make sure it's an integer and not negative or w/e
$steps = $_POST['steps'];

$options = array(
    'file' => $_FILES['image'],
    'x' => $x,
    'y' => $y,
    'w' => $w,
    'h' => $h,
    'steps' => $steps
);

$lulzoom = new Lulzoom($options);
$lulzoom->generateImage();
$lulzoom->outputImage();

?>

<img src="out.jpg" alt="LULZOOM"/>
