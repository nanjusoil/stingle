<?php

class Image extends Model {

	private $info = null;
	private $imageRes = null;
	private $imageExif = null;
	public $fileName;
	public $filePath;

	const IMAGE_TYPE_JPEG = 'jpeg';
	const IMAGE_TYPE_PNG = 'png';
	const IMAGE_TYPE_GIF = 'gif';
	const DIMENSIONS_EQUAL = 1;
	const DIMENSIONS_SMALLER = 2;
	const DIMENSIONS_LARGER = 3;
	const DIMENSIONS_WIDTH_LARGER_HEIGHT_SMALLER = 4;
	const DIMENSIONS_HEIGHT_LARGER__WIDTH_SMALLER = 5;
	const CORNER_TOP_LEFT = 1;
	const CORNER_TOP_RIGHT = 2;
	const CORNER_BOTTOM_LEFT = 3;
	const CORNER_BOTTOM_RIGHT = 4;

	/**
	 * Image manipulation
	 * Supports jpg, gif, png
	 *
	 * @param string $filename
	 * @param wInfo $error
	 */
	public function __construct($filePath) {
		if (empty($filePath)) {
			throw new InvalidArgumentException("\$filename is empty!");
		}
		if (!file_exists($filePath)) {
			throw new RuntimeException("Given file $filePath does not exists.");
		}

		$this->imageRes = $this->createImageRes($filePath);
		try{
			$this->imageExif = exif_read_data($filePath);
		}
		catch (Exception $e){}

		$this->filePath = $filePath;
		$this->fileName = basename($filePath);
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		unset($this->imageRes);
	}

	/**
	 * Creat an image resource from file
	 *
	 * @param string $filename
	 */
	private function createImageRes($filePath) {
		if (($this->info = @getimagesize($filePath)) == false or ! in_array($this->info[2], array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG))) {
			throw new RuntimeException("Given file is not a image!");
		}
		switch ($this->info[2]) {
			case IMAGETYPE_JPEG:
				$imageRes = @imagecreatefromjpeg($filePath);
				break;
			case IMAGETYPE_GIF:
				$imageRes = @imagecreatefromgif($filePath);
				break;
			case IMAGETYPE_PNG:
				$imageRes = @imagecreatefrompng($filePath);
				break;
		}
		if (!is_resource($imageRes)) {
			throw new RuntimeException("Unable to create resource from image!");
		}
		return $imageRes;
	}

	public function getType() {
		switch ($this->info[2]) {
			case IMAGETYPE_JPEG:
				return self::IMAGE_TYPE_JPEG;
			case IMAGETYPE_GIF:
				return self::IMAGE_TYPE_GIF;
			case IMAGETYPE_PNG:
				return self::IMAGE_TYPE_PNG;
			default:
				throw new RuntimeException("Invalid image or object is not initialized!");
		}
	}

	public function getWidth() {
		return $this->info[0];
	}

	public function getHeight() {
		return $this->info[0];
	}

	/**
	 * Resize image
	 *
	 * @param int $width (Can be 0)
	 * @param int $height (Can be 0)
	 * @param bool $preserve_aspect_ratio
	 * @return bool
	 * 
	 * Only one of the width or height can be 0
	 */
	public function resize($width = 0, $height = 0, $preserve_aspect_ratio = true) {
		if (!self::checkMemAvailbleForResize($width, $height)) {
			throw new RuntimeException("Memory is not enough for resize operation!");
		}

		if ($preserve_aspect_ratio) {
			$mode = 0;

			if ($width == 0 and $height == 0) {
				return;
			}

			if ($width == 0) {
				$mode = 2;
			}
			elseif ($height == 0) {
				$mode = 1;
			}
			else {
				$future_width = ceil($this->info[0] * $height / $this->info[1]);
				$future_height = ceil($this->info[1] * $width / $this->info[0]);
				if ($this->info[0] >= $width or $this->info[1] >= $height) {
					if ($this->info[0] >= $this->info[1]) {
						if ($future_height <= $height) {
							$mode = 1;
						}
						else {
							$mode = 2;
						}
					}
					elseif ($this->info[0] < $this->info[1]) {
						if ($future_width <= $width) {
							$mode = 2;
						}
						else {
							$mode = 1;
						}
					}
				}
			}
			if ($mode == 1) {
				$resized_image = imagecreatetruecolor($width, ceil($this->info[1] * $width / $this->info[0]));
				$this->makeImageTransparent($resized_image, $width, ceil($this->info[1] * $width / $this->info[0]));

				if (!imagecopyresampled($resized_image, $this->imageRes, 0, 0, 0, 0, $width, ceil($this->info[1] * $width / $this->info[0]), $this->info[0], $this->info[1])) {
					throw new RuntimeException("Unable to resize image!");
				}
				else {
					$this->info[1] = $this->info[1] * $width / $this->info[0];
					$this->info[0] = $width;
				}
			}
			elseif ($mode == 2) {
				$resized_image = imagecreatetruecolor(ceil($this->info[0] * $height / $this->info[1]), $height);
				$this->makeImageTransparent($resized_image, ceil($this->info[0] * $height / $this->info[1]), $height);

				if (!imagecopyresampled($resized_image, $this->imageRes, 0, 0, 0, 0, ceil($this->info[0] * $height / $this->info[1]), $height, $this->info[0], $this->info[1])) {
					throw new RuntimeException("Unable to resize image!");
				}
				else {
					$this->info[0] = $this->info[0] * $height / $this->info[1];
					$this->info[1] = $height;
				}
			}
			else {
				$resized_image = $this->imageRes;
			}
		}
		else {
			$resized_image = imagecreatetruecolor($width, $height);
			$this->makeImageTransparent($resized_image, $width, $height);

			if (!imagecopyresampled($resized_image, $this->imageRes, 0, 0, 0, 0, $width, $height, $this->info[0], $this->info[1])) {
				throw new RuntimeException("Unable to resize image!");
			}
			else {
				$this->info[0] = $width;
				$this->info[1] = $height;
			}
		}
		$this->imageRes = $resized_image;
		unset($resized_image);
	}

	/**
	 * Write jpeg file
	 *
	 * @param string $filePath (If is null, jpeg will be outputed directly)
	 * @param int $progrssive_jpeg (Set to 1 only for jpg outputs);
	 * @param int $quality (0-100)
	 * @return bool
	 */
	public function writeJpeg($filePath = null, $progrssive_jpeg = 0, $quality = 100) {
		if ($progrssive_jpeg) {
			@imageinterlace($this->imageRes, 1);
		}
		if ($progrssive_jpeg) {
			@imageinterlace($this->imageRes, 0);
		}
		if (!imagejpeg($this->imageRes, $filePath, $quality)) {
			throw new RuntimeException("Unable to write image file!");
		}

		$this->filePath = $filePath;
		$this->fileName = basename($filePath);
	}

	/**
	 * Write gif file
	 *
	 * @param string $filePath (If is null, gif will be outputed directly)
	 * @return bool
	 */
	public function writeGif($filePath = null) {
		if (!imagegif($this->imageRes, $filePath)) {
			throw new RuntimeException("Unable to write image file!");
		}

		$this->filePath = $filePath;
		$this->fileName = basename($filePath);
	}

	/**
	 * Write gif file
	 *
	 * @param string $filePath (If is null, png will be outputed directly)
	 * @return bool
	 */
	public function writePng($filePath = null) {
		imagealphablending($this->imageRes, false);
		imagesavealpha($this->imageRes, true);

		if (!imagepng($this->imageRes, $filePath)) {
			throw new RuntimeException("Unable to write image file!");
		}

		$this->filePath = $filePath;
		$this->fileName = basename($filePath);
	}

	/**
	 * Stamps image with another image
	 *
	 * @param string $image_filename
	 * @param int $corner
	 * @param int $alpha (0-100)
	 * @return bool
	 */
	public function makeStamp($backgroundImagePath, $corner = self::CORNER_BOTTOM_RIGHT, $alpha = 60) {
		if (!in_array($corner, $this->getConstsArray('CORNER'))) {
			throw new InvalidArgumentException("Invalid corner identifier given!");
		}
		if (!($alpha >= 0 and $alpha <= 100)) {
			throw new InvalidArgumentException("Alpha needs to be between 0 and 100!");
		}

		$img_res = $this->createImageRes($backgroundImagePath);
		$img_info = getimagesize($backgroundImagePath);

		switch ($corner) {
			case self::CORNER_TOP_LEFT:
				if (!imagecopymerge($this->imageRes, $img_res, 0, 0, 0, 0, $img_info[0], $img_info[1], $alpha)) {
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_TOP_RIGHT:
				if (!imagecopymerge($this->imageRes, $img_res, $this->info[0] - $img_info[0], 0, 0, 0, $img_info[0], $img_info[1], $alpha)) {
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_BOTTOM_LEFT:
				if (!imagecopymerge($this->imageRes, $img_res, 0, $this->info[1] - $img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)) {
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
			case self::CORNER_BOTTOM_RIGHT:
				if (!imagecopymerge($this->imageRes, $img_res, $this->info[0] - $img_info[0], $this->info[1] - $img_info[1], 0, 0, $img_info[0], $img_info[1], $alpha)) {
					throw new RuntimeException("Unable to make stamp!");
				}
				break;
		}
	}

	/**
	 * Add background to the image
	 *
	 * @param string $background_filename
	 */
	public function addBackround($backgroundFilePath) {
		$img_res = $this->createImageRes($backgroundFilePath);
		$img_info = getimagesize($backgroundFilePath);

		$checkResult = $this->checkDimensions($img_info[0], $img_info[1]);

		if ($checkResult == self::DIMENSIONS_LARGER) {
			throw new RuntimeException("Unable to add background because background is smaller than main image!");
		}

		$newImg = imagecreatetruecolor($img_info[0], $img_info[1]);
		$this->makeImageTransparent($newImg, $img_info[0], $img_info[1]);

		if (!imagecopy($newImg, $img_res, 0, 0, 0, 0, $img_info[0], $img_info[1])) {
			throw new RuntimeException("Unable to add background!");
		}

		if (!imagecopy($newImg, $this->imageRes, ($img_info[0] - $this->info[0]) / 2, ($img_info[1] - $this->info[1]) / 2, 0, 0, $this->info[0], $this->info[1])) {
			throw new RuntimeException("Unable to add background!");
		}
		unset($this->imageRes);
		$this->imageRes = $newImg;
	}

	/**
	 * Crop image
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	public function crop($x, $y, $width, $height) {
		if (!is_numeric($x) or ! is_numeric($y) or ! is_numeric($width) or ! is_numeric($height)) {
			throw new InvalidArgumentException("Some of the parameters are not numeric!");
		}

		if (!self::checkMemAvailbleForResize($width, $height)) {
			throw new RuntimeException("Memory is not enough for crop operation!");
		}

		$cropped_image = imagecreatetruecolor($width, $height);
		$this->makeImageTransparent($cropped_image, $width, $height);

		if (!imagecopyresampled($cropped_image, $this->imageRes, 0, 0, $x, $y, $width, $height, $width, $height)) {
			throw new RuntimeException("Unable to crop!");
		}

		@imagedestroy($this->imageRes);
		$this->imageRes = $cropped_image;
		$this->info[0] = $width;
		$this->info[1] = $height;
	}

	/**
	 * Check image dimensions
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return integer 
	 */
	public function checkDimensions($width, $height) {
		if ($this->info[0] == $width and $this->info[1] == $height) {
			return self::DIMENSIONS_EQUAL;
		}
		elseif ($this->info[0] >= $width or $this->info[1] >= $height) {
			return self::DIMENSIONS_LARGER;
		}
		else {
			return self::DIMENSIONS_SMALLER;
		}
	}

	/**
	 * Check if image meets requirements for minimal size.
	 *
	 * @param integer $largeSideMinSize
	 * @param integer $smallSideMinSize
	 * @return boolean 
	 */
	public function isSizeMeetRequirements($largeSideMinSize, $smallSideMinSize) {
		$width = $this->info[0];
		$height = $this->info[1];
		if ($width >= $height) {
			if ($width >= $largeSideMinSize and $height >= $smallSideMinSize) {
				return true;
			}
		}
		else {
			if ($height >= $largeSideMinSize and $width >= $smallSideMinSize) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check if image meets requirements for given minimum width and height.
	 *
	 * @param integer $minWidth
	 * @param integer $minHeight
	 * @return boolean 
	 */
	public function isWidthHeightMeetRequirements($minWidth, $minHeight) {
		$width = $this->info[0];
		$height = $this->info[1];
		if ($width >= $minWidth and $height >= $minHeight) {
			return true;
		}
		return false;
	}

	/**
	 * Return width and height of the image in array
	 * 
	 * @return array
	 */
	public function getDimensions() {
		return array($this->info[0], $this->info[1]);
	}

	/**
	 * Rotate image
	 *
	 * @param int $angle
	 * @param int $bg_red
	 * @param int $bg_green
	 * @param int $bg_blue
	 * @return bool
	 */
	public function rotate($angle, $bg_red = 255, $bg_green = 255, $bg_blue = 255) {
		if (!is_numeric($angle)) {
			throw new InvalidArgumentException("\$angle have to have numeric value!");
		}
		if (!($bg_red >= 0 and $bg_red <= 255)) {
			throw new InvalidArgumentException("\$bg_red needs to be between 0 and 255!");
		}
		if (!($bg_green >= 0 and $bg_green <= 255)) {
			throw new InvalidArgumentException("\$bg_green needs to be between 0 and 255!");
		}
		if (!($bg_blue >= 0 and $bg_blue <= 255)) {
			throw new InvalidArgumentException("\$bg_blue needs to be between 0 and 255!");
		}
		if (!($rotated_image = imagerotate($this->imageRes, 360 - $angle, imagecolorexact($this->imageRes, $bg_red, $bg_green, $bg_blue)))) {
			throw new RuntimeException("Unable to rotate!");
		}

		@imagedestroy($this->imageRes);
		$this->imageRes = $rotated_image;

		$tmp = $this->info[0];
		$this->info[0] = $this->info[1];
		$this->info[1] = $tmp;
	}

	public function fixOrientation() {
		if (!empty($this->imageExif) and !empty($this->imageExif['Orientation'])) {
			switch ($this->imageExif['Orientation']) {
				case 3:
					$this->rotate(180);
					break;

				case 6:
					$this->rotate(90);
					break;

				case 8:
					$this->rotate(270);
					break;
			}
		}
	}

	public static function checkMemAvailbleForResize($width, $height, $returnRequiredMem = false, $gdBloat = 1.68) {
		$maxMem = ((int) ini_get('memory_limit') * 1024) * 1024;

		$GDBytes = ceil((($width * $height) * 3) * $gdBloat);

		$totalMemRequired = $GDBytes + memory_get_usage();

		if ($returnRequiredMem) {
			return $GDBytes;
		}

		if ($totalMemRequired > $maxMem) {
			return false;
		}

		return true;
	}

	private function makeImageTransparent($imgRes, $width, $height) {
		imagealphablending($imgRes, false);
		imagesavealpha($imgRes, true);
		imagefilledrectangle($imgRes, 0, 0, $width, $height, imagecolorallocatealpha($imgRes, 255, 255, 255, 127));
	}

}
