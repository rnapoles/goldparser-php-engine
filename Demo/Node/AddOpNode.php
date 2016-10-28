<?php
namespace Node;

class AddOpNode extends AbstractNode {
	public $char1;

	// <AddOp> ::= '+' 
	function _initChar1Node($char1){
		$this->char1 = $char1;
	}

	function execute(){
		//code
	}

}

