<?php
namespace Gold\Engine;

class DfaEdgeStruct { /* Grammar.DfaArray[].Edges[] */

    public /* int */ $TargetState;       /* Index into Grammar.DfaArray[]. */
    public /* int */ $CharCount;       /* Number of characters in the charset. */
    public /* wchar_t* */ $CharacterSet;     /* String with characters. */

    function __construct($TargetState, $CharCount, $CharacterSet) {
        $this->TargetState = $TargetState;
        $this->CharCount = $CharCount;
        $this->CharacterSet = $CharacterSet;
    }

}

?>