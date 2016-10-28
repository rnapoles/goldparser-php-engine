<?php
namespace Node;

class MulOpNode extends AbstractNode {
	public $char1;

	// <MulOp> ::= '*' 
	function _initChar1Node($char1){
		$this->char1 = $char1;
	}

	function execute(){
		//code
	}

}

