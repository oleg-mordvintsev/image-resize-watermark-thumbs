<?php

namespace OlegMordvintsev\ImagePrepare;

class Image
{

    /**
     * Размер ширины изображения в пикселях, в которое необходимо вписать.
     * @var int
     */
    private $width;

    /**
     * Размер высоты изображения в пикселях, в которое необходимо вписать.
     * @var int
     */
    private $height;

    /**
     * Размер ширины изображения миниатюры в пикселях, в которое необходимо вписать.
     * @var int
     */
    private $thumbsWidth;

    /**
     *  Размер высоты изображения миниатюры в пикселях, в которое необходимо вписать.
     * @var int
     */
    private $thumbsHeight;

    /**
     * Путь до логотипа в формате png (false если наложение логотипа не требуется).
     * @var string|bool
     */
    private $sourceWatermark;

    /**
     * Положение логотипа (0 - по центру, 1 - справа снизу)
     * @var int
     */
    private $positionWatermark;

    /**
     * Максимальный лимит выделяемой памяти (ОЗУ)
     * @var int|bool - false означает, что изменять не требуется
     */
    private $maxMemory;

    /**
     * Максимально допустимое время обработки одного изображения
     * @var int|bool - false означает, что изменять не требуется
     */
    private $maxExecutionTime;

    /**
     * Качество создаваемого jpeg изображения
     * @var int
     */
    private $jpegQuality;

    /**
     * Image constructor.
     * Конструктор класса и автоматическое заполнение переменных значениями по умолчанию.
     * @param int $width
     * @param int $height
     * @param bool $thumbs
     * @param bool $thumbsWidth
     * @param bool $thumbsHeight
     * @param bool $sourceWatermark
     * @param bool $positionWatermark
     * @param int $maxMemory
     * @param int $maxExecutionTime
     * @param int $jpegQuality
     */
    public function __construct(
        $width = 1920,
        $height = 1920,
        $thumbsWidth = 320,
        $thumbsHeight = 320,
        $sourceWatermark = false,
        $positionWatermark = 0,
        $maxMemory = false,
        $maxExecutionTime = false,
        $jpegQuality = 80
    )
    {
        $this->width = $width;
        $this->height = $height;
        $this->thumbsWidth = $thumbsWidth;
        $this->thumbsHeight = $thumbsHeight;
        $this->sourceWatermark = $sourceWatermark;
        $this->positionWatermark = $positionWatermark;
        $this->maxMemory = $maxMemory;
        $this->maxExecutionTime = $maxExecutionTime;
        $this->jpegQuality = $jpegQuality;
    }


    /**
     * Обработка изображения
     * @param string $sourceIn - адрес изображения, которое необходимо обработать
     * @param string $sourceOut - адрес готового изображения, куда будет сохранен результат
     * @param bool $thumbs - Путь до готового изображения предпросмотра (false если миниатюра не нужна).
     * @return bool|string - true в случае удачи, в ином случае текст ошибки.
     */
    public function process($sourceIn, $sourceOut, $thumbs = false)
    {

        // Проверяем и корректируем размер ОЗУ
        $this->memory();

        // Проверяем и корректируем время выполнения
        $this->time();

        // Сброс высоты и ширины в нулевое значение для дальнейшей работы
        $w = $h = 0;

        // Ошибок пока нет, значение по умолчанию
        $error = false;

        // Проверка существования источника
        if (!is_file($sourceIn)) {

            $error = 'Ошибка, файл источника не существует.';

            return $error;

        }

        // Проверка существования готовых изображений
        if (is_file($sourceOut)) {

            $error = 'Ошибка, файл готового изображения существует.';

            return $error;

        }
        if ($thumbs !== false && is_file($thumbs)) {

            $error = 'Ошибка, файл миниатюры изображения существует.';

            return $error;

        }

        // Определяем тип изображения источника
        $mime = getimagesize($sourceIn);

        // Если jpeg
        if ($mime['mime'] == "image/jpeg")
            $im = imagecreatefromjpeg($sourceIn);

        // Если gif
        elseif ($mime['mime'] == "image/gif" && $thumbs !== false)
            $im = imagecreatefromgif($sourceIn);

        // Если png
        elseif ($mime['mime'] == "image/png")
            $im = imagecreatefrompng($sourceIn);

        // Ошибка типа файла
        else {

            $error = 'Ошибка, тип изображения источника не поддерживается.';

            return $error;

        }

        // Изменение размера изображения (кроме gif), добавление логотипа (опционально)
        if ($mime['mime'] != "image/gif") {

            // Производим расчеты для вписания в нужные размеры изображения
            $this->getWidthHeight($im, $this->width, $this->height, $w, $h);

            // Создаём изображение согласно размерам
            $imOut = imagecreatetruecolor($w, $h);

            // Изображения с типом png требуют заливки белым цветом
            if ($mime['mime'] == "image/png")
                imagefilledrectangle($imOut, 0, 0, $w, $h,
                    imagecolorallocate($imOut, 255, 255, 255));

            // Помещаем в созданную область изменнное изображение нужного размера
            imagecopyresampled($imOut, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));

            // Наложение водного знака, если он существует
            if ($this->sourceWatermark !== false) {

                // Существует ли файл водного знака
                if (is_file($this->sourceWatermark)) {

                    // Получаем данные водного знака
                    $watermarkMime = getimagesize($this->sourceWatermark);

                    // Подходит ли тип файла водного знака?
                    if ($watermarkMime['mime'] == "image/png") {

                        // Получаем данные изображения
                        $logo = imagecreatefrompng($this->sourceWatermark);

                        // Размещение по центру
                        if ($this->positionWatermark == 0)
                            imagecopy($imOut, $logo,
                                floor((($w / 2) - ($watermarkMime[0] / 2)) + 0),
                                floor((($h / 2) - ($watermarkMime[1] / 2)) + 0),
                                0, 0, $watermarkMime[0], $watermarkMime[1]);

                        // Размещение справа снизу
                        else if ($this->positionWatermark == 1)
                            imagecopy($imOut, $logo,
                                ($w - $watermarkMime[0]) - 5,
                                ($h - $watermarkMime[1]) - 5,
                                0, 0, $watermarkMime[0], $watermarkMime[1]);

                    } else {

                        $error = 'Ошибка, тип изображения логотипа не поддерживается, используйте PNG.';

                        return $error;

                    }

                } else {

                    $error = 'Ошибка, файл логотипа не найден.';

                    return $error;

                }

            }

            // Сохраняем готовое изображение
            imagejpeg($imOut, $sourceOut, $this->jpegQuality);

            // Данные изображения больше не нужны
            imagedestroy($imOut);

        } else {

            // Логотип для типа файла gif?
            if ($this->sourceWatermark !== false) {

                $error = 'Ошибка, логотип на GIF изображения не добавляется.';

            }

            // Если Gif, просто копирование файла без корректировки размера
            if ($sourceIn != $sourceOut)
                copy($sourceIn, $sourceOut);

        }

        // Изображение предпросмотра требуется? (создаётся без водного знака)
        if ($thumbs !== false) {

            // Производим расчеты для вписания в нужные размеры изображения
            $this->getWidthHeight($im, $this->thumbsWidth, $this->thumbsHeight, $w, $h);

            // Создаём изображение согласно размерам
            $imOut = imagecreatetruecolor($w, $h);

            // Если изображение в формате png то опять нужна белая подложка
            if ($mime['mime'] == "image/png")
                imagefilledrectangle($imOut, 0, 0, $w, $h,
                    imagecolorallocate($imOut, 255, 255, 255));

            // Помещаем в созданную область изменнное изображение нужного размера
            imagecopyresampled($imOut, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));

            // Сохраняем готовое изображение
            imagejpeg($imOut, $thumbs, $this->jpegQuality);

            // Данные изображения больше не нужны
            imagedestroy($imOut);

        }

        // Проверка работы скрипта проверкой существования файлов
        if (!is_file($sourceOut) || ($thumbs !== false && !is_file($thumbs))) {

            $error = 'Ошибка, файлы не созданы. Возможно отсутствуют права на запись.';

            // Удаляем возможно существующие файлы без вывода ошибок
            @unlink($sourceOut);
            @unlink($thumbs);

            return $error;

        }

        // Если дошли до этого этапа, значит всё хорошо. Восклицательный знак меняет false на true.
        return !$error;

    }

    /**
     * Получение пропорциональных размеров для изображения, чтобы вписать в нужный прямоугольник указанного размера
     * @param $im - данные изображения переданные по ссылке
     * @param $sw - даннные ширины, в которую необходимо вписать
     * @param $sh - данные высоты в которую необходимо вписать
     * @param $w - возвращаемые по ссылке обратно данные нужного значения ширины
     * @param $h - возвращаемые по ссылке обратно данные нужного значения высоты
     */
    private function getWidthHeight(&$im, $sw, $sh, &$w, &$h)
    {

        // Получаем текущие значения ширины и высоты
        $ix = imagesx($im);
        $iy = imagesy($im);

        // Если хотябы один из размеров не подходит, то делаем расчеты
        if ($ix > $sw || $iy > $sh) {

            // Получаем коэффициенты
            $k1 = $sw / $ix;
            $k2 = $sh / $iy;

            // Какой коэффициент использовать?
            $k = $k1 > $k2 ? $k2 : $k1;

            // Корректируем размеры и приводим к целому числу
            $w = intval(round($ix * $k));
            $h = intval(round($iy * $k));

        } else {

            // Если все размеры меньше тех, в которые надо вписать, то ничего и не расчитываем
            $w = $ix;
            $h = $iy;

        }

    }

    /**
     * Изменяем максимально допустимо кол-во ОЗУ для работы скрипта (глобально).
     */
    private function memory()
    {

        if ($this->maxMemory !== false && gettype($this->maxMemory) === 'integer') {

            // Получаем текущий размер ОЗУ
            $memory = (int)str_replace('M', '', ini_get('memory_limit'));

            // Если размер ОЗУ меньше необходимого для работы, то...
            if ($memory < $this->maxMemory) {

                // Устанавливаем новое большее значение
                ini_set('memory_limit', $this->maxMemory . 'M');

            }

        }

    }

    /**
     * Изменяем максимально допустимое время работы скрипта (глобально).
     */
    private function time()
    {

        if ($this->maxExecutionTime !== false && gettype($this->maxExecutionTime) === 'integer') {

            // Устанавливаем нужное значение
            set_time_limit($this->maxExecutionTime);

        }

    }

}