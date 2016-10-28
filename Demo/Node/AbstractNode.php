<?php
namespace Node;

class AbstractNode {

    function __construct()
    {
        //PHP don't support function overload :(
        
		$i = func_num_args();
		$a = func_get_args();
		$f = '';

		if($i >= 1){
			if($i>1){
				$f='_init'.$i;
			} else {

				if(is_object($a[0])){
					$type = self::getClassName($a[0]);
				} else {
					$type = gettype($a[0]);
				}

				$f='_init'.$type;
			}
			echo "call $f\n";
			if (method_exists($this,$f)) {
				call_user_func_array(array($this,$f),$a);
			}
		}
    }

	function execute(){
	}

	static function getClassName($className){

		$c = new \ReflectionClass($className);
		return $c->getShortName();
	}
}

?>