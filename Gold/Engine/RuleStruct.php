<?php
namespace Gold\Engine;

class RuleStruct { /* Grammar.RuleArray[] */

    public /* int */ $Head;         /* Index into Grammar.SymbolArray[]. */
    public /* int */ $SymbolsCount;       /* Number of items in Symbols[] array. */
    public /* int* */ $Symbols;        /* Array of indexes into Grammar.SymbolArray[]. */
    public /* wchar_t* */ $Description;      /* String with BNF of the rule. */

    function __construct($Head, $SymbolsCount, $Symbols, $Description) {
		$this->Head = $Head; 
		$this->SymbolsCount = $SymbolsCount;
		$this->Symbols = $Symbols;
		$this->Description = $Description;
    }

}

?>