<?php
function dump($var)
{
    echo '<div id="codedump">'; var_dump($var); echo '</div>';
}

function implodeAttributeList($attributeList)
{
    array_walk($attributeList, function(&$i,$k) { $i=" $k=\"$i\""; } );
    return implode($attributeList,"");
}
?>