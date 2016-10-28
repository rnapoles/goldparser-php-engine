<?php
namespace Gold\Engine;

class ActionStruct { /* Grammar.LalrArray[].Actions[] */

    public /* int */ $Entry;        /* Index into Grammar.SymbolArray[]. */
    public /* short */ $Action;        /* 1...4, see ACTION defines. */
    public /* int */ $Target;        /* If Action=SHIFT then index into Grammar.LalrArray[]. */

    /* If Action=REDUCE then index into Grammar.RuleArray[]. */
    /* If Action=GOTO then index into Grammar.LalrArray[]. */

    function __construct($Entry,$Action,$Target) {
		$this->Entry = $Entry;
		$this->Action = $Action;
		$this->Target = $Target;
    }

}

?>