<?php

namespace major98113\umbrelliotest;

/**
 * This is just an example.
 */
class FileRead extends \yii\base\Widget
{

    public function openFile($filePath){
        $handle = fopen($filePath, "r");
        return $handle;

    }

    public function run()
    {
        return "Hello, I am Max     !";
    }
}
