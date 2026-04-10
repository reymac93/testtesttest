<?php
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

function output_style_header()
{
	echo <<< __EOF__
<style type="text/css">
body {margin-top:10px; margin-right:0px; margin-bottom:0px; margin-left:10px; width:900px; }
p {font-family: century, serif; font-size: 14px;}
.warning {background-color: yellow;}
.error {background-color: red;}
.left {text-align: left;}
.right {text-align: right;}
.error_button {
    border: 1px solid #000;
    background-color: red;
    padding: 0px;
}
.warn_button {
    border: 1px solid #000;
    background-color: yellow;
    padding: 0px;
}
.good_button {
    border: 1px solid #000;
    background-color: palegreen;
    padding: 0px;
}
.blank_button {
    border: 1px solid #000;
    background-color: white;
    color: white;
}

table, th, td
{
border: 1px solid black;
}
pre {font-family: courier; font-size: 14px;}
</style>
__EOF__;

} // output_style_header

?>
