<?php

//<Factor> ::= FloatLiteral 
//<MulExpr> ::= <Factor>
//<Factor> ::= Id '(' <Params> ')'

@mkdir('Node');

$includes = '';
$className = 'DataGenParser';
$className2 = 'DataGenAstParser';
$code = file_get_contents("$className.php");

$classList = array();
$extraClassList = array();

function process($code){

    global $classList,$extraClassList;

    echo "\n\n$code\n";
    $out = '';
    $code = preg_replace('/\s+/',' ', $code);
    
    preg_match('/\s(<[^>].*>)\s+::=/',$code,$data);
    $className = $data[1];
    $className = str_replace(array('<','>'),'',$className);
    
    $classDecl = NULL;
    if(isset($classList[$className])){
        $classDecl = &$classList[$className];
    } else {
        $classDecl = array();
    }

    if(!isset($classDecl['code'])){
        $classDecl['code'] = array();
    }

    $classDecl['code'][] = $code;

    if(!isset($classDecl['methods'])){
        $classDecl['methods'] = array();
    }

    if(!isset($classDecl['vars'])){
        $classDecl['vars'] = array();
    }


    $code = preg_replace('/\s<[^>].*>\s+::=/','', $code);
    $pieces= explode(' ',$code);

    $method = array();
    $vars = array();

    if(is_array($pieces)){
        $i = 0;
        $q = 0;

        foreach($pieces as $part){
            if($part != '') $q++; 
        }

        $k = 1;
        foreach($pieces as $part){
            
            if($part != ''){
                
                if($i == 0) $out .= "[backspace][backspace]";
                if(preg_match('/\'.\'/',$part)){
                    //if($q == 1){
                        $method[] = '$char'.$k;
                        $vars[] = '$char'.$k;
                        $out .= "\t\t//$part\n";
                        //$out .= "\t\t\$Context->ReturnValue = \$Token->Tokens[0]->Data;\n\n";
                        $out .= "\t\t\$char$k = $part;\n";
                        $k++;
                    //}
                } else if($q > 1){
                    $out .= "\t\t//$part\n";

                    if(preg_match('/^[a-z0-9]+$/i',$part)){
                        $lpart = lcfirst($part);
                        if(!isset($extraClassList[$part])){
                            $extraClassList[] = $part;
                        }
                        $out .= "\t\t\$$lpart = new {$part}Node(\$Token->Tokens[$i]->Data);\n\n";
                    } else {
                        $part = str_replace(array('<','>'),'',$part);
                        $lpart = lcfirst($part);
                        $out .= "\t\t\$fn = \$this->RuleJumpTable[\$Token->Tokens[$i]->ReductionRule];\n";
                        $out .= "\t\t\$$lpart = \$this->\$fn(\$Token->Tokens[$i],\$Context);\n\n";
                    }

                    $method[] = "{$part}Node \$$lpart";
                    $vars[] = "\$$lpart";

                    //$out .= "\t\t\$$part = \$Context->ReturnValue;\n\n";
                } else {
                    if(preg_match('/^[a-z0-9]+$/i',$part)){
                        $out .= "\t\t//$part\n";
                        
                        if(!isset($extraClassList[$part])){
                            $extraClassList[] = $part;
                        }
                        
                        //echo "$part\n";
                        $lpart = lcfirst($part);
                        $method[] = "{$part}Node \$$lpart";
                        $vars[] = "\$$lpart";
                        $out .= "\t\t\$$lpart = new {$part}Node(\$Token->Tokens[0]->Data);\n\n";
                    } else {
                        $out .= "\t\t//$part\n";
                        $part = str_replace(array('<','>'),'',$part);
                        $lpart = lcfirst($part);
                        $method[] = "{$part}Node \$$lpart";
                        $vars[] = "\$$lpart";
                        $out .= "\t\t\$fn = \$this->RuleJumpTable[\$Token->Tokens[$i]->ReductionRule];\n";
                        $out .= "\t\t\$$lpart = \$this->\$fn(\$Token->Tokens[$i],\$Context);\n\n";
                        //$out .= "\t\t\$$part = \$Context->ReturnValue;\n\n";
                    }
                }
                $i++;
            }

        }
    }

    foreach($vars as $var){
        if(!in_array($var,$classDecl['vars'])){
            $classDecl['vars'][] = $var;
        }
    }
    $out .= "\t\treturn new {$className}Node(".implode(',',$vars).");\n";

    $method = implode(', ',$method);
    if(!in_array($method,$classDecl['methods'])){
        $classDecl['methods'][] = $method;
    }

    if(!isset($classList[$className])){
        $classList[$className] = $classDecl;
    }

    return $out;
}

function perform_backspace($string = '') {
    $search = '[backspace]';
    $search_length = strlen($search);
    $search_pos = strpos($string, $search);
    while($search_pos !== false) {
        if($search_pos === 0) {
            // this is beginning of string, just delete the search string
            $string = substr_replace($string, '', $search_pos, $search_length);
        } else {
            // delete character before search and the search itself
            $string = substr_replace($string, '', $search_pos - 1, $search_length + 1);
        }
        $search_pos = strpos($string, $search);
    }
    return $string;
}

if(preg_match_all('/\/\/\^(.*)/m',$code,$rules)){
    $c = count($rules[0]);
    for($i = 0;$i<$c;$i++){
        //echo $rules[0][$i]."\n";
        $pattern = str_replace("\n",'',$rules[0][$i]);
        $remp = process($rules[1][$i]);
        echo "$remp";
        $code = str_replace($pattern,$remp,$code);
    }
}


//var_dump($classList);
$out = '';

foreach($classList as $class=>$value){
    
    $out = "<?php\n";
    $out .= "namespace Node;\n\n";

    //$out .= "/*\n";
    //foreach($value['code'] as $c){
    //  $out .= "$c\n";
    //}
    //$out .= "*/\n";
    

    $out .= "class {$class}Node extends AbstractNode {\n";
    //var_dump($value);
    
    foreach($value['vars'] as $v){
        $out .= "\tpublic $v;\n";
    }
    
    $out .= "\n";
    
    $k = 0;
    foreach($value['methods'] as $m){
        
        if(preg_match_all('/\$([a-zA-Z0-9]+)/',$m, $cap)){
            $c = count($cap[0]);
            
            $co = $value['code'][$k]; 
            $out .= "\t//$co\n";
            if($c == 1){
                $var = ucfirst($cap[1][0]);
                
                if($var != 'Char'){
                    $var .= 'Node';
                }
                $out .= "\tfunction _init$var($m){\n";
            } else {
                $out .= "\tfunction _init$c($m){\n";
            }
            for($i = 0;$i<$c;$i++){
                $var1 = $cap[0][$i];
                $var2 = $cap[1][$i];
                $out .= "\t\t\$this->$var2 = $var1;\n";
            }
            $out .= "\t}\n\n";
        }

        //$sp = explode($m);
        //echo "\t";
        $k++;
    }
    
    $out .= "\tfunction execute(){\n";
    $out .= "\t\t//code\n";
    $out .= "\t}\n\n";
    $out .= "}\n\n";
    file_put_contents("Node/{$class}Node.php",$out);
    $includes .= "use Node\\{$class}Node;\n";
    
}

foreach($extraClassList as $class){
    
    $out = "<?php\n";
    $out .= "namespace Node;\n\n";
    $out .= "class {$class}Node extends AbstractNode {\n";
    //var_dump($value);
    
    $out .= "\tpublic \$data;\n";

    $out .= "\n";
    $out .= "\tfunction __construct(\$data){\n";
    $out .= "\t\t\$this->data = \$data;\n";
    $out .= "}\n\n";

    $out .= "\tfunction execute(){\n";
    $out .= "\t\t//code\n";
    $out .= "\t}\n\n";
    $out .= "}\n\n";
    file_put_contents("Node/{$class}Node.php",$out);
    $includes .= "use Node\\{$class}Node;\n";
    
}

$includes.="\n";


$code = perform_backspace($code);
$code = str_replace('class ',$includes."class ",$code);
$code = str_replace('$this->$fn($this->FirstToken,$Context);','$this->Result = $this->$fn($this->FirstToken,$Context);',$code);
$code = str_replace($className,$className2,$code);

file_put_contents("$className2.php",$code);

?>