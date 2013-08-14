<?php

/**
 * Captcha class
 *
 * This class generate captcha image
 *
 * @usage		<img src="/captcha/?background=000&color=fff&rand=1234"
 * @author		Pavel Eremin
 */

// ------------------------------------------------------------------------

class Captcha {

	/**
	 * ID for more than one captcha images on site page
	 *
	 * @var string
	 */
	private $id = '';
	/**
	 * Font file of captcha image
	 *
	 * @var array
	 */
	private $font = './segoeprb.ttf';
	/**
	 * Default font size of captcha image
	 *
	 * @var array
	 */
	private $font_size = 20;
	/**
	 * Allowed symbols in the captcha image
	 *
	 * @var array
	 */
	private $symbols = array(
		'0',
		'1',
		'2',
		'3',
		'4',
		'5',
		'6',
		'8',
		//'A','B','C','E'
	);
	/**
	 * Number of symbols in the captcha image
	 *
	 * @var array
	 */
	private $length = 4;
	/**
	 * Default color of symbols
	 *
	 * @var string
	 */
	private $color = '#000000';
	/**
	 * Default background of symbols
	 *
	 * @var string
	 */
	private $background = '#FFFFFF';


	/**
	 * Constructor
	 *
	 * Generate and display captcha image
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct($config = array())
	{
		!$config OR $this->initialize($config);

		if ($img = $this->generate()) {
			$this->show($img);
		}
	}


	// --------------------------------------------------------------------

	/**
	 * Initialize the user preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function initialize($config = array())
	{
		foreach ($config as $key => $val) {
			if (isset($this->$key)) {
				$this->$key = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generate captcha image
	 *
	 * @access	private
	 * @return	mixed
	 */
	private function generate()
	{
		$font_size = $this->getFontSize();

		// Add 50% width and height for write symbols with angle
		$img_width = round($font_size * $this->length * 1.5);
		$img_height = round($font_size * 1.5);

		if ($img = imagecreatetruecolor($img_width, $img_height)) {
			$color = $this->getColor();
			$color = imagecolorallocate($img, $color[0], $color[1], $color[2]);

			if ($this->get('background')) {
				$background = $this->getBackground();
				$background = imagecolorallocate($img, $background[0], $background[1], $background[2]);
				imagefilledrectangle($img, 0, 0, $img_width, $img_height, $background);
			}
			else {
				$background = imagecolorallocatealpha($img, 0, 0, 0, 127);
				imagefill($img, 0, 0, $background);
				imagesavealpha($img, true);
			}

			session_start();
			$_SESSION['captcha'.$this->getId()] = '';

			// For more speed calculate this out of loop
			$pre_y = round(($img_height - $font_size) / 2) + $font_size;
			$pre_x = max($font_size, $img_width / $this->length);

			for ($i = 0; $i < $this->length; $i++) {
				$symbol = $this->symbols[array_rand($this->symbols)];
				$_SESSION['captcha'] .= $symbol;

				$symbol_angle = rand(-30, 30);

				// Add 20% position x to make word at center
				$x = $i * $pre_x + $font_size * 0.2;
				$y = $symbol_angle < 0 ? $font_size : $pre_y;

				imagettftext($img, $font_size, $symbol_angle, $x, $y, $color, $this->font, $symbol);
			}
		}

		return $img;
	}

	// --------------------------------------------------------------------

	/**
	 * Displays captcha image in the browser
	 *
	 * @access	private
	 * @return	void
	 */
	private function show($img)
	{
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0',false);
		header('Pragma: no-cache');
		header('Content-type: image/png');

		imagepng($img);
		imagedestroy($img);
	}

	// --------------------------------------------------------------------

	/**
	 * Get captcha font size
	 *
	 * @access	private
	 * @return	integer
	 */
	private function getFontSize()
	{
		return $this->get('font_size') ? $this->get('font_size') : $this->font_size;
	}

	// --------------------------------------------------------------------

	/**
	 * Get captcha font color
	 *
	 * @access	private
	 * @return	array
	 */
	private function getColor()
	{
		$color = $this->get('color') ? $this->get('color') : $this->color;
		return $this->html2rgb($color);
	}

	// --------------------------------------------------------------------

	/**
	 * Get captcha background color
	 *
	 * @access	private
	 * @return	array
	 */
	private function getBackground()
	{
		$background = $this->get('background') ? $this->get('background') : $this->background;
		return $this->html2rgb($background);
	}

	// --------------------------------------------------------------------

	/**
	 * Get captcha id
	 *
	 * @access	private
	 * @return	string
	 */
	private function getId()
	{
		$id = $this->get('id') ? $this->get('id') : $this->id;
		if ($id) {
			$id = '_'.$id;
		}
		return $id;
	}

	// --------------------------------------------------------------------

	/**
	 * Return rgb color from hex
	 *
	 * @access	private
	 * @param 	string $color
	 * @return	array
	 */
	private function html2rgb($color)
	{
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}

		if (strlen($color) != 6 && strlen($color) != 3) {
			$color = $this->color;
		}

		if (strlen($color) == 3) {
			$color = array(
				$color[0],
				$color[0],
				$color[1],
				$color[1],
				$color[2],
				$color[2]
			);
		}
		
		list($r, $g, $b) = array(
			$color[0] . $color[1],
			$color[2] . $color[3],
			$color[4] . $color[5]
		);

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array($r, $g, $b);
	}

	// --------------------------------------------------------------------

	/**
	 * Return variable from $_GET array, if not exist return NULL
	 *
	 * @access	private
	 * @param	string $name
	 * @return	mixed
	 */
	private function get($name)
	{
		return isset($_GET[$name]) ? $_GET[$name] : null;
	}
}

new Captcha(array(
	'font_size' => 50,
	'length' => 10
));