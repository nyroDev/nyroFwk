<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * manipulate image
 */
class helper_image extends helper_file {

	/**
	 * Image ressource to work in
	 *
	 * @var imageRessource
	 */
	private $imgAct;

	/**
	 * Image ressource for mask to work in
	 *
	 * @var imageRessource
	 */
	private $imgTmp;

	/**
	 * Destructor
	 */
	public function __destruct() {
		if ($this->imgAct)
			@imagedestroy($this->imgAct);
		if ($this->imgTmp)
			@imagedestroy($this->imgTmp);
	}

	/**
	 * Upload an image form a uploaded file
	 * Used in form_fileUploaded
	 *
	 * @param string $file The image uploaded
	 * @param array $prm The parameter for the image
	 * @return bool True if success
	 * @see bluid
	 */
	public function upload($file, array $prm) {
		$this->cfg->file = $file;
		$this->cfg->setA($prm);
		$this->cfg->rebuild = true;
		return $this->build();
	}

	/**
	 * Get the HTML source to show an image
	 *
	 * @param string $file Filename
	 * @param array $prm @see helper_mage config
	 * @return string The HTML image tag
	 */
	public function view($file, array $prm) {
		$this->cfg->file = FILESROOT.$file;
		$this->cfg->setA($prm);
		$this->cfg->html = true;
		$this->cfg->fileSave = $this->makePath($this->cfg->file, $this->cfg->fileSaveAdd);
		$fileWeb = str_replace(array(FILESROOT, '/'), array('', ','), $this->cfg->fileSave);
		return str_replace(
			$this->cfg->fileSave,
			request::uri(array('module'=>'nyroUtils', 'action'=>'uploadedFiles', 'param'=>$fileWeb, 'out'=>null)),
			$this->build());
	}

	/**
	 * Delete the eventual thumbnail created for an image
	 * Used in form_fileUploaded
	 *
	 * @param string $file The image uploaded
	 * @param array $prm The parameter for the image
	 */
	public function delete($file, array $prm=null) {
		file::delete(FILESROOT.$file);
		file::multipleDelete(FILESROOT.$this->makePath($file, '*'));
	}

	/**
	 * Make the path for a thumbnail
	 *
	 * @param string $file File name source
	 * @param string $more To create other
	 * @return string Thumbnail path
	 */
	protected function makePath($file, $more=null) {
		if (is_null($more))
			$more = md5($this->cfg->w.'_'.$this->cfg->h.'_'.$this->cfg->bgColor.'_'.$this->cfg->fit);

		return preg_replace(
			'/\.('.implode('|', $this->cfg->autoExt).')$/i',
			'_'.$more.'.'.file::getExt($file),
			$file);
	}

	/**
	 * Make an image
	 *
	 * @param array $prm The parameter for the image
	 * @return bool|string True if success or HTML string if requested
	 * @see bluid
	 */
	public function make(array $prm) {
		$this->cfg->setA($prm);
		return $this->build();
	}

	/**
	 * Make an image with the configuration parameter
	 *
	 * @return bool|string True if success or HTML string if requested
	 */
	private function build() {
		$ret = null;
		if (file::exists($this->cfg->file)) {
			$this->setImg($this->cfg->file);

			if ($this->cfg->autoFileSave && empty($this->cfg->fileSave))
				$this->cfg->fileSave = $this->makePath($this->cfg->file, $this->cfg->fileSaveAdd);

			if ($this->cfg->rebuild || !file::exists($this->cfg->fileSave)) {
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
					if($this->resize(array(
						'w'=>$this->cfg->w,
						'h'=>$this->cfg->h,
						'bgColor'=>$this->cfg->bgColor,
						'fit'=>$this->cfg->fit
						))) {
						// Save the new size
						$this->cfg->wAct = imagesx($this->imgAct);
						$this->cfg->hAct = imagesy($this->imgAct);
						if ($this->cfg->w || $this->cfg->h)
							$change = true;
					}
				}

				if (!empty($this->cfg->mask) && file::exists($this->cfg->mask)) {
					$this->mask($this->cfg->mask);
					$change = true;
				}

				$ret = null;
				if (!$change)
					$this->cfg->fileSave = $this->cfg->file;

				if (!empty($this->cfg->fileSave))
					if ($this->save($this->cfg->fileSave))
						$ret = $this->cfg->fileSave;

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
	private function html(array $options = array()) {
		if (file::exists($this->cfg->fileSave)) {
			return utils::htmlTag('img',
				array_merge($options, array(
					'src'=>$this->cfg->fileSave,
					'alt'=>$this->cfg->alt,
				)));
			$ret = str_replace('[src]', $this->cfg->fileSave, $this->html);
			$ret = str_replace('[width]', $this->cfg->wAct, $ret);
			$ret = str_replace('[height]', $this->cfg->hAct, $ret);
			$ret = str_replace('[alt]', $this->cfg->alt, $ret);
			$ret = str_replace('[plusImg]', $this->cfg->plusImg, $ret);
			return $ret;
		}
		return null;
	}

	/**
	 * Set an image and creating a ressource with it
	 *
	 * @param string $file The image path
	 */
	public function setImg($file) {
		$this->cfg->img = $file;

		$tmp = $this->createImage($file);
		$this->imgAct = $tmp[0];
		$this->cfg->wAct = $tmp[1];
		$this->cfg->hAct = $tmp[2];
	}

	/**
	 * Save the image
	 *
	 * @param string $file The image path
	 */
	public function save($file) {
		$ret = false;
		switch (strtolower(file::getExt($file))) {
			case 'gif' :
				$ret = imagegif($this->imgAct, $file);
				break;
			case 'png' :
				$ret = imagepng($this->imgAct, $file);
				break;
			default:
				$ret = imagejpeg($this->imgAct, $file);
				break;
		}
		if ($ret)
			$this->cfg->fileSave = $file;

		return $ret;
	}

	/**
	 * Create an image ressource and get the dimesnion of the
	 *
	 * @param string $file The image path
	 * @return array Image ressource, height, width
	 */
	private function createImage($file) {
		$size = getimagesize($file);

		$img = null;

		switch ($size[2]) {
			case 1 :
				$img = imagecreatefromgif($file);
				break;
			case 2 :
				$img = imagecreatefromjpeg($file);
				break;
			case 3 :
				$img = imagecreatefrompng($file);
				break;
		}

		return array(&$img, $size[0], $size[1]);
	}

	/**
	 * Resize the image
	 *
	 * @param array $prm The parameter for the resizing:
	 *  - string imgName: The image ressource to use (default: Act);
	 *  - int w: The width (default: 0 -> proportionnal resize with the height)
	 *  - int h: The height (default: 0 -> proportionnal resize width the width)
	 *  - bool fit: Indicate if the image will be fit to the size (default: true)
	 *  - hexa bgColor: The background color (default: ffffff)
	 * @return bool True if success
	 */
	private function resize(array $prm = array()) {
		config::initTab($prm, array(
			'imgName'=>'Act',
			'w'=>0,
			'h'=>0,
			'fit'=>false,
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

		if (!empty($prm['w']) && !empty($prm['h'])) {
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
		} else if (!empty($prm['w'])) {
			// Width is fixed
			$prm['h'] = round($srcH * $scaleW);
			$dstH = round($srcH * $scaleW);
			$prm['fit'] = true;
		} else if (!empty($prm['h'])) {
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

		$imgDst = imagecreatetruecolor($prm['w'],$prm['h']);

		if (!$prm['fit']) {
			$cl = array();
			// Background
			if (empty($prm['bgColor'])) {
				// For a transparent Background : the color inverse of the first Pixel
				$rgb = ImageColorAt($img, 1, 1);
				$clT = imagecolorsforindex($img, $rgb);
				$cl[0] = 255 - $clT['red'];
				$cl[1] = 255 - $clT['green'];
				$cl[2] = 255 - $clT['blue'];
			} else {
				$cl = $this->hexa2dec($prm['bgColor']);
			}

			$clR = imagecolorallocate($imgDst, $cl[0], $cl[1], $cl[2]);
			imagefill($imgDst, 0, 0, $clR);

			if (empty($prm['bgColor']))
				// Make background transparent
				imagecolortransparent($imgDst, $clR);
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

		imagecopymerge($this->imgAct, $this->imgTmp, 0, 0, 0, 0, $this->cfg->wAct, $this->cfg->hAct, 100);
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
	private function crop(array $prm) {
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

		$this->imgTmp = imagecreatetruecolor($this->cfg->w,$this->cfg->h);
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
	private function  hexa2dec($col) {
		return array(
			base_convert(substr($col,0,2),16,10),
			base_convert(substr($col,2,2),16,10),
			base_convert(substr($col,4,2),16,10)
		);
	}
}
