<?php
echo '<h1>'.$error.'</h1>';
echo utils::htmlOut(session::getFlash('nyroError')).'<br /><br />';
echo security::getInstance()->getLoginForm();