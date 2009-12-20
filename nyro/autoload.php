<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * This function allows us to avoid "including/requiring" all our class files
 * it provides a method for determining the class file to include based upon naming conventions we've defined
 */
spl_autoload_register(array('factory', 'load'));