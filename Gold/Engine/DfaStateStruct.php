<?php
namespace Gold\Engine;

class DfaStateStruct { /* Grammar.DfaArray[] */

    public /* int */ $AcceptSymbol;       /* -1 (Terminal), or index into Grammar.SymbolArray[]. */
    public /* int */ $EdgeCount;       /* Number of items in Edges[] array. */
    public /* class DfaEdgeclass* */ $Edges;  /* Array of DfaEdgeclass. */

    function __construct($AcceptSymbol, $EdgeCount, $Edges) {
        $this->AcceptSymbol = $AcceptSymbol;
        $this->EdgeCount = $EdgeCount;
        $this->Edges = $Edges;
    }

}

?>