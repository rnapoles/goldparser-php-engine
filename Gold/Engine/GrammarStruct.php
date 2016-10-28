<?php
namespace Gold\Engine;

class GrammarStruct { /* Grammar */

    public /* char */ $CaseSensitive;      /* 'True' or 'False'. */
    public /* int */ $InitialSymbol;      /* Index into Grammar.SymbolArray[]. */
    public /* int */ $InitialDfaState;      /* Index into Grammar.DfaArray[]. */
    public /* int */ $InitialLalrState;      /* Index into Grammar.LalrArray[]. */
    public /* int */ $SymbolCount;       /* Number of items in Grammar.SymbolArray[]. */
    public /* class Symbolclass* */ $SymbolArray;
    public /* int */ $RuleCount;       /* Number of items in Grammar.RuleArray[]. */
    public /* class Ruleclass* */ $RuleArray;
    public /* int */ $DfaStateCount;      /* Number of items in Grammar.DfaArray[]. */
    public /* class DfaStateclass* */ $DfaArray;
    public /* int */ $LalrStateCount;      /* Number of items in Grammar.LalrArray[]. */
    public /* class LalrStateclass* */ $LalrArray;

}
?>