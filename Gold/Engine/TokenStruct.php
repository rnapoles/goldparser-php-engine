<?php
namespace Gold\Engine;

/* Output from the parser. */

class TokenStruct {

    public /* int */ $ReductionRule;      /* Index into Grammar.RuleArray[]. */
    public /* class Tokenclass** */ $Tokens;  /* Array of reduction Tokens. */
    public /* int */ $Symbol;        /* Index into Grammar.SymbolArray[]. */
    public /* wchar_t* */ $Data;       /* String with data from the input. */
    public /* int */ $Value;       /* String with data from the input. */
    public /* long */ $Line;        /* Line number in the input. */
    public /* long */ $Column;        /* Column in the input. */

}
?>