<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Helper to manipulate and upload image
 */
class helper_image extends helper_file {

	/**
	 * Image ressource to work in
	 *
	 * @var imageRessource
	 */
	protected $imgAct;

	/**
	 * Image ressource for mask to work in
	 *
	 * @var imageRessource
	 */
	protected $imgTmp;

	/**
	 * Image information
	 *
	 * @var array
	 */
	protected $info;

	/**
	 * Destructor
	 */
	public function __destruct() {
		if ($this->imgAct && is_resource($this->imgAct))
			@imagedestroy($this->imgAct);
		if ($this->imgTmp && is_resource($this->imgTmp))
			@imagedestroy($this->imgTmp);
	}

	/**
	 * Upload an image form a uploaded file
	 * Used in form_fileUploaded
	 *
	 * @param string $file The image uploaded
	 * @param array $prm The parameter for the image
	 * @see bluid
	 */
	public function upload($file, array $prm = array()) {
		$this->cfg->file = $file;
		if (!array_key_exists('fileSaveAdd', $prm))
			$this->cfg->fileSave = $file;
		$this->cfg->setA($prm);
		$this->cfg->rebuild = true;
		return basename($this->build());
	}

	/**
	 * Get the source uri to show an image
	 *
	 * @param string $file Filename
	 * @param array $prm @see helper_mage config
	 * @return string The HTML image tag
	 */
	public function viewUri($file, array $prm = array()) {
		$this->view($file, $prm);
		$fileWeb = str_replace($this->cfg->filesRoot, '', $this->cfg->fileSave);
		return $this->cfg->webUri ? request::webUri($fileWeb) : request::uploadedUri($fileWeb);
	}

	/**
	 * Get the HTML source to show an image
	 *
	 * @param string $file Filename
	 * @param array $prm @see helper_mage config
	 * @return string The HTML image tag
	 */
	public function view($file, array $prm = array()) {
		$this->cfg->file = $this->addFilesRootIfNeeded($file);
		$this->cfg->setA($prm);
		$this->cfg->html = true;
		if ($this->cfg->originalView) {
			$this->cfg->fileSave = $this->cfg->file;
			$ret = $this->html();
		} else {
			$this->cfg->fileSave = $this->makePath($this->cfg->file, $this->cfg->fileSaveAdd);
			$ret = $this->build();
		}
		$fileWeb = str_replace($this->cfg->filesRoot, '', $this->cfg->fileSave);
		return str_replace(
			$this->cfg->fileSave,
			$this->cfg->webUri ? request::webUri($fileWeb) : request::uploadedUri($fileWeb),
			$ret);
	}

	/**
	 * Delete the eventual thumbnail created for an image
	 * Used in form_fileUploaded
	 *
	 * @param string $file The image uploaded
	 * @param array $prm The parameter for the image
	 */
	public function delete($file, array $prm=null) {
		file::delete($this->addFilesRootIfNeeded($file));
		file::multipleDelete($this->addFilesRootIfNeeded($this->makePath($file, '*')));
	}

	/**
	 * Make the path for a thumbnail
	 *
	 * @param string $file File name source
	 * @param string $more To create other
	 * @return string Thumbnail path
	 */
	public function makePath($file, $more=null) {
		if (is_null($more))
			$more = md5($this->cfg->w.'_'.$this->cfg->h.'_'.$this->cfg->bgColor.'_'.$this->cfg->fit);
		if ($more)
			$more = '_'.$more;

		if (!$this->cfg->forceExt)
			$this->cfg->forceExt = file::getExt($file);

		return preg_replace(
			'/\.('.implode('|', $this->cfg->autoExt).')$/i',
			$more.'.'.$this->cfg->forceExt,
			$file);
	}

	/**
	 * Add FILESROOT to a filename if not present in it
	 *
	 * @param string $file Filename
	 * @return string Filename
	 */
	protected function addFilesRootIfNeeded($file) {
		if (strpos($file, $this->cfg->filesRoot) === false)
			$file = $this->cfg->filesRoot.$file;
		return $file;
	}

	/**
	 * Make an image
	 *
	 * @param array $prm The parameter for the image
	 * @return bool|string True if success or HTML string if requested
	 * @see bluid
	 */
	public function make(array $prm = array()) {
		$this->cfg->setA($prm);
		return $this->build();
	}

	public function valid(array $file, array $prm = array()) {
		$ret = parent::valid($file, $prm);
		if ($ret && count($file) && array_key_exists('tmp_name', $file) && file::exists($file['tmp_name'])) {
			$size = getimagesize($file['tmp_name']);
			if (!is_array($size) && $size[2] != IMAGETYPE_GIF && $size[2] != IMAGETYPE_JPEG && $size[2] != IMAGETYPE_PNG)
				return $this->cfg->getInArray('validErrors', 'notValidImg');
		}
		return $ret;
	}

	/**
	 * Make an image with the configuration parameter
	 *
	 * @return bool|string True if success or HTML string if requested
	 */
	protected function build() {
		$ret = null;
		if (file::exists($this->cfg->file)) {
			$this->cfg->wAct = null;
			$this->cfg->hAct = null;

			if ($this->cfg->autoFileSave && empty($this->cfg->fileSave))
				$this->cfg->fileSave = $this->makePath($this->cfg->file, $this->cfg->fileSaveAdd);

			if ($this->cfg->rebuild || !file::exists($this->cfg->fileSave)) {
				$this->setImg($this->cfg->file);
				$change = false;
				if (is_array($this->cfg->crop)) {
					if ($this->crop($this->cfg->crop)) {
						// Save the new size
						$this->cfg->wAct = imagesx($this->imgAct);
						$this->cfg->hAct = imagesy($this->imgAct);
						$change = true;
					}
				} else {
					// Resize
					if ($this->cfg->resizeSmaller ||
							($this->cfg->w > 0 && $this->cfg->wAct > $this->cfg->w) ||
							($this->cfg->h > 0 && $this->cfg->hAct > $this->cfg->h)) {
						if ($this->resize(array(
								'w'=>$this->cfg->w,
								'h'=>$this->cfg->h,
								'bgColor'=>$this->cfg->bgColor,
								'fit'=>$this->cfg->fit,
								'useMaxResize'=>$this->cfg->useMaxResize
							))) {
							// Save the new size
							$this->cfg->wAct = imagesx($this->imgAct);
							$this->cfg->hAct = imagesy($this->imgAct);
							if ($this->cfg->w || $this->cfg->h)
								$change = true;
						}
					}
				}

				if (!empty($this->cfg->filters) && function_exists('imagefilter')) {
					foreach($this->cfg->filters as $prms) {
						if (!is_array($prms)) {
							$f = $prms;
							$prms = array();
						} else
							$f = array_shift($prms);
						switch(count($prms)) {
							case 0:		imagefilter($this->imgAct, $f); break;
							case 1:		imagefilter($this->imgAct, $f, $prms[0]); break;
							case 2:		imagefilter($this->imgAct, $f, $prms[0], $prms[1]); break;
							case 3:		imagefilter($this->imgAct, $f, $prms[0], $prms[1], $prms[2]); break;
							default:	imagefilter($this->imgAct, $f, $prms[0], $prms[1], $prms[2], $prms[3]); break;
						}
					}
				}

				if ($this->cfg->grayFilter) {
					// Copy form $img to $imgDst With the parameter defined, with grayscale
					if (function_exists('imagefilter')) {
						imagefilter($this->imgAct, IMG_FILTER_GRAYSCALE);
					} else {
						// Manual Grayscale
						$imgTmp = $this->imgAct;
						$x = imagesx($imgTmp);
						$y = imagesy($imgTmp);
						$this->imgAct = imagecreatetruecolor($x, $y);
						imagecolorallocate($this->imgAct, 0, 0, 0);
						for ($i = 0; $i < $x; $i++) {
							for ($j = 0; $j < $y; $j++) {
								$rgb = imagecolorat($imgTmp, $i, $j);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								$color = max(array($r, $g, $b));
								imagesetpixel($this->imgAct, $i, $j, imagecolorexact($this->imgAct, $color, $color, $color));
							}
						}
						imagedestroy($imgTmp);
					}
				}

				if (!empty($this->cfg->mask) && file::exists($this->cfg->mask)) {
					$this->mask($this->cfg->mask);
					$change = true;
				}
				
				if (!empty($this->cfg->watermarks) && is_array($this->cfg->watermarks)) {
					foreach($this->cfg->watermarks as $watermark) {
						$tmp = $this->watermark($watermark);
						$change = $change || $tmp;
					}
				}

				$ret = null;
				if (!$change)
					$this->cfg->fileSave = $this->cfg->file;

				if (!empty($this->cfg->fileSave)) {
					if ($change) {
						if ($this->save($this->cfg->fileSave))
							$ret = $this->cfg->fileSave;
					} else {
						if ($this->cfg->file != $this->cfg->fileSave)
							file::move($this->cfg->file, $this->cfg->fileSave);
						$ret = $this->cfg->fileSave;
					}
				}

				if ($this->cfg->html)
					$ret = $this->html();
			} else if ($this->cfg->html) {
				$ret = $this->html();
			} else {
				$ret = $this->cfg->fileSave;
			}
		}

		return $ret;
	}

	/**
	 * Make an image with the configuration parameter
	 *
	 * @return string|null The HTML string or null if the image doesn't exists
	 */
	protected function html(array $options = array()) {
		if (file::exists($this->cfg->fileSave)) {
			$w = $this->cfg->wAct ? $this->cfg->wAct : null;
			$h = $this->cfg->hAct ? $this->cfg->hAct : null;
			if ($this->cfg->forceHtmlSize && (!$w || !$h)) {
				$size = getimagesize($this->cfg->fileSave);
				if (!$w)
					$w = $size[0];
				if (!$h)
					$h = $size[1];
			}
			return utils::htmlTag('img',
				array_merge($options, $this->cfg->htmlDefOptions, array(
					'src'=>$this->cfg->fileSave,
					'alt'=>$this->cfg->alt,
					'width'=>$w,
					'height'=>$h,
				)));
		}
		return null;
	}

	/**
	 * Set an image and creating a ressource with it
	 *
	 * @param string $file The image path
	 * @return bool Indicate if everything went well or not
	 */
	public function setImg($file) {
		$this->cfg->img = $file;

		$tmp = $this->createImage($file);
		if ($tmp) {
			$this->imgAct = $tmp[0];
			$this->cfg->wAct = $tmp[1];
			$this->cfg->hAct = $tmp[2];
			return true;
		} else
			return false;
	}
	
	/**
	 * Rotate an image if there is exif data indicating it need it
	 *
	 * @param string $file The image path
	 * @return boolean True if image was rotate
	 */
	public function rotateIfExifData($file) {
		$ret = false;
		if (function_exists('exif_read_data')) {
			$exif = exif_read_data($file);
			if (is_array($exif) && isset($exif['Orientation']) && $exif['Orientation'] != 1) {
				$tmp = $this->setImg($file);
				if ($tmp) {
					$this->cfg->forceExt = file::getExt($file);
					$ret = $this->save($file);
				}
			}
		}
		return $ret;
	}

	/**
	 * Save the image
	 *
	 * @param string $file The image path
	 */
	public function save($file) {
		$ret = false;

		switch ($this->cfg->forceExt) {
			case 'gif' :
				$ret = imagegif($this->imgAct, $file);
				break;
			case 'png' :
				$ret = imagepng($this->imgAct, $file);
				break;
			default:
				$ret = imagejpeg($this->imgAct, $file, $this->cfg->jpgQuality);
				break;
		}
		if ($ret)
			$this->cfg->fileSave = $file;
		return $ret;
	}

	/**
	 * Create an image ressource and get the dimension of the
	 *
	 * @param string $file The image path
	 * @return false|array False if not a valid image or an array with Image ressource, width and height
	 */
	protected function createImage($file) {
		if (!file::exists($file) || ! is_file($file))
			return false;
		$this->info = getimagesize($file);

		$infoWIndex = 0;
		$infoHIndex = 1;
		$img = null;
		switch ($this->info[2]) {
			case IMAGETYPE_JPEG:
				$img = imagecreatefromjpeg($file);
				if ($this->cfg->processOrientation && function_exists('exif_read_data')) {
					$exif = exif_read_data($file);
					if (is_array($exif) && isset($exif['Orientation']) && $exif['Orientation'] != 1) {
						$switchInfo = $this->rotateOnExifData($img, $exif['Orientation']);
						if ($switchInfo) {
							$infoWIndex = 1;
							$infoHIndex = 0;
						}
					}
				}
				break;
			case IMAGETYPE_GIF:
				$img = imagecreatefromgif($file);
				imagealphablending($img, false);
				imagesavealpha($img, true);
				break;
			case IMAGETYPE_PNG:
				$img = imagecreatefrompng($file);
				imagealphablending($img, false);
				imagesavealpha($img, true);
				break;
			default:
				return false;
		}

		return array(&$img, $this->info[$infoWIndex], $this->info[$infoHIndex]);
	}
	
	/**
	 * Rotate the image based on provided exif data
	 *
	 * @param imageresource $img Resource image
	 * @param int $exifOrientation Exif orientation info
	 * @return boolean True if the dimension have been switched
	 */
	protected function rotateOnExifData(&$img, $exifOrientation) {
		$switchInfo = false;
		switch($exifOrientation) {
			case 2:
			case 4:
				$this->imageFlip($img);
				break;
			case 3:
				$img = imagerotate($img, 180, -1);
				break;
			case 5:
			case 7:
				$this->imageFlip($img);
				$img = imagerotate($img, -90, -1);
				$switchInfo = true;
				break;
			case 6:
				$img = imagerotate($img, -90, -1);
				$switchInfo = true;
				break;
			case 8:
				$img = imagerotate($img, 90, -1);
				$switchInfo = true;
				break;
		}
		return $switchInfo;
	}
	
	/**
	 * Flip the image
	 *
	 * @param imageresource $img Resource image
	 * @return boolean Tru if success
	 */
	protected function imageFlip(&$img) {
		$width  = imagesx($img);
		$height = imagesy($img);
		$tmp = imagecreatetruecolor(1, $height);
		
		$x2 = $x + $width - 1;
		for ($i = (int)floor(($width - 1) / 2); $i >= 0; $i--)	{
			// Backup right stripe.
			imagecopy($tmp, $img, 0, 0, $x2 - $i, $y, 1, $height);
			// Copy left stripe to the right.
			imagecopy($img, $img, $x2 - $i, $y, $x + $i, $y, 1, $height);
			// Copy backuped right stripe to the left.
			imagecopy($img, $tmp, $x + $i,  $y, 0, 0, 1, $height);
		}
		imagedestroy($tmp);
		return true;
	}

	/**
	 * Resize the image
	 *
	 * @param array $prm The parameter for the resizing:
	 *  - string imgName: The image ressource to use (default: Act);
	 *  - int w: The width (default: 0 -> proportionnal resize with the height)
	 *  - int h: The height (default: 0 -> proportionnal resize width the width)
	 *  - bool fit: Indicates if the image will be fit to the size (default: true)
	 *  - bool useMaxResize: Indicates if the image will be resized to only one dimension (default: false)
	 *  - hexa bgColor: The background color (default: ffffff)
	 * @return bool True if success
	 */
	protected function resize(array $prm = array()) {
		config::initTab($prm, array(
			'imgName'=>'Act',
			'w'=>0,
			'h'=>0,
			'fit'=>false,
			'useMaxResize'=>false,
			'bgColor'=>'ffffff'
		));

		$img = &$this->{'img'.$prm['imgName']};
		$srcW = $this->cfg->get('w'.$prm['imgName']);
		$srcH = $this->cfg->get('h'.$prm['imgName']);

		$srcX = 0;
		$srcY = 0;
		$dstX = 0;
		$dstY = 0;
		$scaleW = $prm['w'] / $srcW;
		$scaleH = $prm['h'] / $srcH;
		$dstW = $prm['w'];
		$dstH = $prm['h'];
		
		if ($prm['useMaxResize'] && $prm['w'] && $prm['h']) {
			if ($scaleW > $scaleH) {
				$prm['w'] = 0;
			} else {
				$prm['h'] = 0;
			}
		}

		if ($prm['w'] && $prm['h']) {
			// Dimensions are fixed
			if ($prm['fit']) {
				if ($scaleW > $scaleH) {
					$srcH = round($prm['h'] / $scaleW);
					$srcY = round(($this->cfg->get('h'.$prm['imgName']) - $srcH) / 2);
				} else {
					$srcW = round($prm['w'] / $scaleH);
					$srcX = round(($this->cfg->get('w'.$prm['imgName']) - $srcW) / 2);
				}
			} else {
				if ($scaleW > $scaleH) {
					$dstW = round($srcW * $scaleH);
					$dstX = round(($prm['w'] - $dstW) / 2);
				} else {
					$dstH = round($srcH * $scaleW);
					$dstY = round(($prm['h'] - $dstH) / 2);
				}
			}
		} else if ($prm['w']) {
			// Width is fixed
			$prm['h'] = round($srcH * $scaleW);
			$dstH = round($srcH * $scaleW);
			$prm['fit'] = true;
		} else if ($prm['h']) {
			// Height is fixed
			$prm['w'] = round($srcW * $scaleH);
			$dstW = round($srcW * $scaleH);
			$prm['fit'] = true;
		} else {
			// No dimensions requested, use the imgAct dimensions
			$prm['w'] = $this->cfg->wAct;
			$prm['h'] = $this->cfg->hAct;
			$dstW = $prm['w'];
			$dstH = $prm['h'];
		}

		$imgDst = imagecreatetruecolor($prm['w'], $prm['h']);
		if ($this->info[2] == IMAGETYPE_GIF || $this->info[2] == IMAGETYPE_PNG) {
			imagealphablending($imgDst, false);
			imagesavealpha($imgDst, true);
		}

		if (empty($prm['bgColor']) && ($this->cfg->forceExt == 'png' || $this->cfg->forceExt == 'gif' || $this->info[2] == IMAGETYPE_GIF || $this->info[2] == IMAGETYPE_PNG)) {
			$transparency = imagecolortransparent($img);
			if ($transparency >= 0) {
				$trnprtIndex = imagecolortransparent($img);
				$transparentColor  = imagecolorsforindex($img, $trnprtIndex);
				$transparency      = imagecolorallocate($imgDst, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				imagefill($imgDst, 0, 0, $transparency);
				imagecolortransparent($imgDst, $transparency);
			} else if ($this->info[2] == IMAGETYPE_PNG) {
				imagealphablending($imgDst, false);
				imagesavealpha($imgDst, true);
				imagefill($imgDst, 0, 0, imagecolorallocatealpha($imgDst, 0, 0, 0, 127));
			}
		} else if (!$prm['fit']) {
			$cl = $this->hexa2dec($prm['bgColor']);
			$clR = imagecolorallocate($imgDst, $cl[0], $cl[1], $cl[2]);
			imagefill($imgDst, 0, 0, $clR);
		}

		// Copy form $img to $imgDst With the parameter defined
		imagecopyresampled($imgDst, $img, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

		// Destroy the image ressource source
		imagedestroy($img);

		// Store the new
		$this->{'img'.$prm['imgName']} = &$imgDst;

		return true;
	}

	/**
	 * Add a mask to the actual image
	 *
	 * @param string $file The mask image path
	 */
	public function mask($file) {
		// Create the mask image ressource
		$tmp = $this->createImage($file);
		if ($tmp) {
			$this->imgTmp = $tmp[0];
			$this->cfg->wTmp = $tmp[1];
			$this->cfg->hTmp = $tmp[2];

			// Resize the mask
			if ($this->resize(array(
				'imgName'=>'Tmp',
				'bgColor'=>false
				))) {
				// Save the new size
				$this->cfg->wTmp = imagesx($this->imgTmp);
				$this->cfg->hTmp = imagesy($this->imgTmp);
			}
			
			$newPicture = imagecreatetruecolor($this->cfg->wTmp, $this->cfg->hTmp);
			imagealphablending($newPicture, false);
			imagesavealpha($newPicture, true);
			imagefill($newPicture, 0, 0, imagecolorallocatealpha($newPicture, 0, 0, 0, 127));

			// Perform pixel-based alpha map application
			for( $x = 0; $x < $this->cfg->wTmp; $x++ ) {
				for( $y = 0; $y < $this->cfg->hTmp; $y++ ) {
					$alpha = imagecolorsforindex($this->imgTmp, imagecolorat($this->imgTmp, $x, $y));
					$alpha = 127 - floor($alpha[ 'red' ] / 2);
					$color = imagecolorsforindex($this->imgAct, imagecolorat($this->imgAct, $x, $y));
					imagesetpixel($newPicture, $x, $y, imagecolorallocatealpha($newPicture, $color['red'], $color['green'], $color['blue'], $alpha));
				}
			}
			
			imagedestroy($this->imgAct);
			$this->imgAct = $newPicture;
		}
	}

	/**
	 * Add a watermark to the actual image
	 *
	 * @param array $prm The watermark configuration with:
	 *  - string file: The image file path (required)
	 *  - boolean fit: Indicates if the watermark should fit the image size (default: false)
	 *  - boolean center: Indicates if the watermark is center (if not fitted) (default: true)
	 *  - int margin: Margin to place the watermark (default: 0)
	 */
	public function watermark($prm) {
		$ret = false;
		if (config::initTab($prm, array(
			'file'=>null,
			'fit'=>false,
			'center'=>true,
			'margin'=>0
		))) {
			// Create the mask image ressource
			$tmp = $this->createImage($prm['file']);
			if ($tmp) {
				$this->imgTmp = $tmp[0];
				$this->cfg->wTmp = $tmp[1];
				$this->cfg->hTmp = $tmp[2];
				if (strpos($prm['margin'], '%') !== false) {
					$val = intval(substr($prm['margin'], 0, -1)) / 100;
					$marginW = $val * $this->cfg->wAct;
					$marginH = $val * $this->cfg->hAct;
				} else {
					$marginW = $marginH = $prm['margin'];
				}
				
				$dstX = $marginW;
				$dstY = $marginH;
				$srcX = $srcY = 0;
				$srcW = $this->cfg->wTmp;
				$srcH = $this->cfg->hTmp;
				$dstW = $this->cfg->wAct - ($marginW * 2);
				$dstH = $this->cfg->hAct - ($marginH * 2);

				if (!$prm['fit'] && $prm['center']) {
					$scaleW = $dstW / $srcW;
					$scaleH = $dstH / $srcH;
					if ($scaleW > $scaleH) {
						$dstW = round($srcW * $scaleH);
						$dstX = round(($this->cfg->wAct - $dstW) / 2);
					} else {
						$dstH = round($srcH * $scaleW);
						$dstY = round(($this->cfg->hAct - $dstH) / 2);
					}
					/*
					if ($scaleW > $scaleH) {
						$dstW = round($srcW * $scaleH);
						$dstX = round(($srcW - $dstW) / 2);
					} else {
						$dstH = round($srcH * $scaleW);
						$dstY = round(($srcH - $dstH) / 2);
					}
					 */
				}
				imagecopyresampled($this->imgAct, $this->imgTmp, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	 * Crop the image
	 *
	 * @param array $prm The parameter for the cropping:
	 *  - int x: The X position where crop (default: -1 -> automaticly center)
	 *  - int y: The Y position where crop (default: -1 -> automaticly center)
	 *  - int w: The width result (default: 0 -> the source image width)
	 *  - int h: The height result (default: 0 -> the source image height)
	 * @return bool True if success
	 */
	protected function crop(array $prm) {
		config::initTab($prm, array(
			'x'=>-1,
			'y'=>-1,
			'w'=>0,
			'h'=>0
		));
		$x = $prm['x'];
		$y = $prm['y'];
		$w = $prm['w'];
		$h = $prm['h'];

		if ($w + $h == 0) {
			$w = $this->cfg->w;
			$h = $this->cfg->h;
		} else {
			if ($w == 0)
				$w = $this->cfg->h * $h / $this->cfg->w;
			if ($h == 0)
				$h = $this->cfg->w * $w / $this->cfg->h;
		}

		if ($x == -1)
			$x = round($this->cfg->wAct / 2 - $w / 2);

		if ($y == -1)
			$y = round($this->cfg->hAct / 2 - $h / 2);

		$this->imgTmp = imagecreatetruecolor($this->cfg->w, $this->cfg->h);
		imagecopyresampled($this->imgTmp, $this->imgAct, 0, 0, $x, $y, $this->cfg->w, $this->cfg->h, $w, $h);

		$this->imgAct = $this->imgTmp;

		return true;
	}

	/**
	 * Convert an hexadecimal color to an rgb.
	 *
	 * @param string $col The hexadecimal color
	 * @return array Numeric index (0: R, 1: V and 2: B)
	 */
	protected function hexa2dec($col) {
		return array(
			base_convert(substr($col, 0, 2), 16, 10),
			base_convert(substr($col, 2, 2), 16, 10),
			base_convert(substr($col, 4, 2), 16, 10)
		);
	}

}
