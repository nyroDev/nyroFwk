<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * To throw Exception in Nyro Framework
 */
class nException extends Exception {

	public $line;
	public $file;

}