<!DOCTYPE html>

<html>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="xml_import.php" method="POST">
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="300000000" />
    <!-- Name of input element determines name in $_FILES array -->
    .SCX filename: <input name="xmlfile" size="50" type="file" />
    <input type="submit" value="Upload" />
</form>
<h1>My first PHP page</h1>
<?php
echo "Hello World!";
?> 

</html>
