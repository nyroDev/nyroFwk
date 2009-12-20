<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * This function is used to handle the errors
 */
set_error_handler(array('debug', 'errorHandler'), E_ALL | E_STRICT);