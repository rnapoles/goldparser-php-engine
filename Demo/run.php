<?php

require_once('autoload.php');


/* Load the inputfile into memory. */
//$InputBuf = file_get_contents("Example.input");
$InputBuf = "rand(5,40) *2";

echo "$InputBuf\n";
$p = new DataGenParserImp($InputBuf,0,0);
$p->Debug = 0;
$p->Run();

?>