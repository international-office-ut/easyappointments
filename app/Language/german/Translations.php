<?php

/* Language bridge for the CI4 stack (bare-key parity with the CI3 files). */

defined('BASEPATH') || define('BASEPATH', 'ci4-language-bridge');

$lang = [];

require ROOTPATH . 'application' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'german'
    . DIRECTORY_SEPARATOR . 'translations_lang.php';

return $lang;
