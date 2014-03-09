<?php
require_once nZEDb_LIB . 'Util.php';

/**
 * Resize/save/delete images to disk.
 *
 * Class ReleaseImage
 */
class ReleaseImage
{
	/**
	 * Path to save ogg audio samples.
	 *
	 * @var string
	 */
	public $audSavePath;

	/**
	 * Path to save video preview jpg pictures.
	 *
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * Path to save large jpg pictures(xxx).
	 *
	 * @var string
	 */
	public $jpgSavePath;

	/**
	 * Path to save movie jpg covers.
	 *
	 * @var string
	 */
	public $moveimgSavePath;

	/**
	 * Path to save video ogv files.
	 *
	 * @var string
	 */
	public $vidSavePath;

	/**
	 * Construct.
	 */
	public function __construct()
	{
		$s = new Sites(); // Creates the nZEDb_COVERS constant
		$this->audSavePath    = nZEDb_COVERS . 'audiosample' . DS;
		$this->imgSavePath    = nZEDb_COVERS . 'preview'     . DS;
		$this->jpgSavePath    = nZEDb_COVERS . 'sample'      . DS;
		$this->movimgSavePath = nZEDb_COVERS . 'movies'      . DS;
		$this->vidSavePath    = nZEDb_COVERS . 'video'       . DS;
	}

	/**
	 * Get a URL or file image and convert it to string.
	 *
	 * @param string $imgLoc URL or file location.
	 *
	 * @return bool|mixed|string
	 */
	protected function fetchImage($imgLoc)
	{
		$img = false;

		if (strpos(strtolower($imgLoc), 'http:') === 0) {
			$img = getUrl($imgLoc);
		} else if (is_file($imgLoc)) {
			$img = @file_get_contents($imgLoc);
		}

		if ($img !== false) {
			$im = @imagecreatefromstring($img);
			if ($im !== false) {
				imagedestroy($im);
				return $img;
			}
		}
		return false;
	}

	/**
	 * Save an image to disk, optionally resizing it.
	 * @param string $imgName      What to name the new image.
	 * @param string $imgLoc       URL or location on the disk the original image is in.
	 * @param string $imgSavePath  Folder to save the new image in.
	 * @param string $imgMaxWidth  Max width to resize image to.   (OPTIONAL)
	 * @param string $imgMaxHeight Max height to resize image to.  (OPTIONAL)
	 * @param bool   $saveThumb    Save a thumbnail of this image? (OPTIONAL)
	 *
	 * @return int 1 on success, 0 on failure Used on site to check if there is an image.
	 */
	public function saveImage($imgName, $imgLoc, $imgSavePath, $imgMaxWidth='', $imgMaxHeight='', $saveThumb=false)
	{
		// Try to get the image as a string.
		$cover = $this->fetchImage($imgLoc);
		if ($cover === false) {
			return 0;
		}

		// Check if we need to resize it.
		if ($imgMaxWidth != '' && $imgMaxHeight != '') {
			$im = @imagecreatefromstring($cover);
			$width = @imagesx($im);
			$height = @imagesy($im);
			$ratio = min($imgMaxHeight/$height, $imgMaxWidth/$width);
			// New dimensions
			$new_width = intval($ratio*$width);
			$new_height = intval($ratio*$height);
			if ($new_width < $width && $new_width > 10 && $new_height > 10) {
				$new_image = @imagecreatetruecolor($new_width, $new_height);
				@imagecopyresampled($new_image, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				ob_start();
				@imagejpeg($new_image, null, 85);
				$thumb = ob_get_clean();
				@imagedestroy($new_image);

				if ($saveThumb) {
					@file_put_contents($imgSavePath.$imgName.'_thumb.jpg', $thumb);
				} else {
					$cover = $thumb;
				}

				unset($thumb);
			}
			@imagedestroy($im);
		}
		// Store it on the hard drive.
		$coverPath = $imgSavePath.$imgName.'.jpg';
		$coverSave = @file_put_contents($coverPath, $cover);

		// Check if it's on the drive.
		if ($coverSave === false || !is_file($coverPath)) {
			return 0;
		}
		return 1;
	}

	/**
	 * Delete images for the release.
	 *
	 * @param string      $guid   The GUID of the release.
	 * @param null|string $imdbid The IMDBid of the release. (OPTIONAL)
	 *
	 * @return void
	 */
	public function delete($guid, $imdbid=null)
	{
		$thumb = $guid . '_thumb.jpg';

		// Audiosample folder.
		@unlink($this->audSavePath . $guid . '.ogg');

		// Movies folder.
		if (!is_null($imdbid)) {
			@unlink($this->movimgSavePath.$imdbid.'-cover.jpg');
		}

		// Preview folder.
		@unlink($this->imgSavePath . $thumb);

		// Sample folder.
		@unlink($this->jpgSavePath . $thumb);

		// Video folder.
		@unlink($this->vidSavePath . $guid . '.ogv');
	}
}
