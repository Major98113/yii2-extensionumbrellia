<?php

namespace major98113\umbrelliotest;


class FileRead extends \yii\base\Widget
{

    public $path = '';
    public $filePath = '';
    public $searchStr = '';
    public $maxSize = '';
    public $mimeType = '';



    public function run()
    {
        if (file_exists($this->path) && ($this->checkMimeType(0) == $this->mimeType || $this->mimeType == '')) { //проверяем, существует ли файл
            $this->checkMimeType(0);
            $this->CheckDirectSize(filesize($this->path));
        }
        else{ // если он находится удаленно, то file_exists выкинет false, поэтому делаю проверку на существование файла по url с помощью is_url_exist

            if($this->is_url_exist($this->path) && ($this->checkMimeType(1) == $this->mimeType || $this->mimeType == '')){
                $this->CheckDirectSize($this->curl_get_file_size($this->path));
            }
            else{
                return 'No such FILE : '.$this->path.' or Forbidden mime_type';
            }
        }
    }


    public function checkMimeType($remote)
    {
        if($remote == true){
            $ch = curl_init($this->path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            echo curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        }
        else{
            return mime_content_type($this->path);
        }
    }


    public function searchLine($content) //функция для поиска слова в строке, выделил отдельно чтобы было удобнее ориентироваться
    {
        return stripos($content, $this->searchStr);
    }


    public function is_url_exist($url){ //смотрим, есть ли файл удаленно
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($code == 200){
            $status = true;
        }
        else{
            $status = false;
        }
        curl_close($ch);
        return $status;
    }

    public function curl_get_file_size( $url ) { //берем вес удаленного файла, если он существует
        $result = -1;
        $curl = curl_init( $url );
        curl_setopt( $curl, CURLOPT_NOBODY, true );
        curl_setopt( $curl, CURLOPT_HEADER, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );

        $data = curl_exec( $curl );
        curl_close( $curl );

        if( $data ) {
            $content_length = "unknown";
            $status = "unknown";

            if( preg_match( "/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches ) ) {
                $status = (int)$matches[1];
            }

            if( preg_match( "/Content-Length: (\d+)/", $data, $matches ) ) {
                $content_length = (int)$matches[1];
            }

            if( $status == 200 || ($status > 300 && $status <= 308) ) {
                $result = $content_length;
            }
        }

        return $result;
    }


    public function convertToBytes($from) //если задан параметр максимального веса файла, конвертируем это значение в байты
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from,-2));

        if(is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        if($exponent === null) {
            return null;
        }

        return $number * (1024 ** $exponent);
    }


    public function CheckDirectSize($size){ //смотрим вес файла
        if (!empty($this->maxSize)){ //смотрим, задан ли третий параметр (maxSize)
            if ($this->convertToBytes($this->maxSize) >= $size ){ //если размер файла меньше или равен нашему maxSize, то идем дальше, в противном случае выкидываем ошибку
                $this->GetSearchLines(); //смотрим файл для проверки наличия нужного слова
            }
            else{
                echo "Too BIG SIZE";
            }
        }
        else{ //если maxSize не задан, начинаем поиск подстроки в файле
            if($size){
                $this->GetSearchLines(); //смотрим файл для проверки наличия нужного слова
            }
        }
    }




    public function GetSearchLines(){
        $fp = fopen($this->path, "r"); // Открываем файл в режиме чтения
        $counter = 0;
        if ($fp) //можно ли открыть файл?
        {
            $line = 0;
            while (!feof($fp)) //проходим по всему файлу в поисках нужного слова в каждой строке
            {
                $line++;
                $mytext = fgets($fp, 999);
                $pos = $this->searchLine($mytext);
                if ($pos !== false) {
                    $counter++;
                    echo "Нашел '$this->searchStr' в позиции $pos в строке $line <br/>";
                }
            }

            if ($counter==0){ //если искомого слова не найдено в файле выводим сообщение
                echo "Строка '$this->searchStr' не найдена в файле '$this->path'";
            }
        }
        else echo "Ошибка при открытии файла";
        fclose($fp);
    }


}
