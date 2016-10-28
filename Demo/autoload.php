<?php

    function __autoload($className) { 
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        $file2 = '../'. str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        
        if(file_exists($file)){
            require_once($file); 
        } else if(file_exists($file2)){
            require_once($file2); 
        } else {
            die($file." Not Found -\n");
        }
    }


?>