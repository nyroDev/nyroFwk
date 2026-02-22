<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * To throw Exception in Nyro Framework
 */
class nException extends Exception {

	public int $line;
	public string $file;

}