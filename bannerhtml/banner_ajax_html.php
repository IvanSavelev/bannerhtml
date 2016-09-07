<?php

/*Ничего не пересылаем, сохраняем временно в БД, то что баннер ненадо показывать
Этот файл нуджен только для приема AJAX
 Located in /modules/mymodule/ajax.php*/
require_once(dirname(__FILE__).'../../../config/config.inc.php');
require_once(dirname(__FILE__).'../../../init.php');

switch (Tools::getValue('method')) {
    case 'getContent':
        Configuration::updateValue('BLOCKBANNER_HTML_VIEW',0,false);
        //die( Tools::jsonEncode( array('result'=>'my_value')));
        break;
    default:
        exit;
}
exit;
?>