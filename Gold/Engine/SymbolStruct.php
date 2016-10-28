<?php
namespace Gold\Engine;

/* Grammar table and sub-tables. */
class SymbolStruct { /* Grammar.SymbolArray[] */

    public /* short */ $Kind;        /* 0...7, See SYMBOL defines. */
    public /* wchar_t */ $Name;       /* String with name of symbol. */

    function __construct($Kind, $Name) {
        $this->Kind = $Kind;
        $this->Name = $Name;
    }

}


?>