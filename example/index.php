<?php

use OM\ImagePrepare\Image;

// Composer
require_once __DIR__ . '/vendor/autoload.php';

// Если хотите ручной режим, закомментируйте предыдущее и раскомментируйте следующую строку
// require_once __DIR__. '/../src/ImagePrepare.php';


### 1. Простое использование ###
$image = new Image();
$image->process('in.jpg', 'out.jpg');


### 2. Изменение ширины и высоты ###

$image = new Image(
    2000, // ширина
    1500 // высота
);
$image->process('in.jpg', 'out.jpg');


### 3. Добавление миниатюры без изменения размеров по умолчанию основного изображения ###

$image = new Image(
    null, // ширина по умолчанию
    null, // высота по умолчанию
    320, // ширина миниатюры
    240 // высота миниатюры
);
$image->process('in.jpg', 'out.jpg', 'out_thumbs.jpg');


### 4. Миниатюра и водный знак справа снизу ###

$image = new Image(
    1500, // ширина
    1500, // высота
    null, // ширина миниатюры по умолчанию
    null, // высота миниатюры по умолчанию
    'watermark.png', // файл водного знака
    1 // водный знак справа снизу
);
$image->process('in.jpg', 'out.jpg', 'out_thumbs.jpg');


### 5. Все параметры меняем ###

$image = new Image(
    2000, // ширина
    2000,  // высота
    300,  // ширина миниатюры
    300,  // высота миниатюры
    'watermark.png', // файл водного знака
    0, // водный знак в центре
    512, // 512Mb выделить в ОЗУ
    90, // 90 секунд на выполнение операции с одним изображением
    80 // качество готового jpg изображения
);
$image->process('in.jpg', 'out.jpg', 'out_thumbs.jpg');


### 6. Массовая обработка из директории с сохранением названия файла ###

$inDir = __DIR__ . '/inDir/';
$outDir = __DIR__ . '/outDir/';

$image = new Image(
    1000, // ширина
    1000,  // высота
    120,  // ширина миниатюры
    120,  // высота миниатюры
    'watermark.png', // файл водного знака
    0, // водный знак в центре
    512, // 512Mb выделить в ОЗУ
    30, // 30 секунд на выполнение операции с одним изображением
    85 // качество готового jpg изображения
);

if ($handle = opendir($inDir)) {

    while (false !== ($entry = readdir($handle))) {

        if ($entry != "." && $entry != "..") {

            // Получаем имя файла без расширения
            $out = explode(".", $entry);
            array_pop($out);
            $out = implode(".", $out);

            // Обработка изображения
            echo $image->process(
                $inDir . $entry, // изображение в обработку
                $outDir . $out . '.jpg', // готовое изображение
                $outDir . $out . '_thumbs.jpg' // готовая миниатюра
            );

            if (php_sapi_name() === 'cli')
                echo ' Файл: ' . $inDir . $entry . "\n";
            else
                echo ' Файл: ' . $inDir . $entry . "<br>";

        }

    }

    closedir($handle);

}
