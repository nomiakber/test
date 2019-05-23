<?php

   function update()
   {
       // the message
$msg = "First line of text\nSecond line of text";

// use wordwrap() if lines are longer than 70 characters
$msg = wordwrap($msg,70);

// send email
mail("nomiakber@gmail.com","My subject",$msg);

        $myFile = "cronlog.txt";
       $fh = fopen($myFile, 'w');
       $stringData = date('l jS \of F Y h:i:s A');
       fwrite($fh, $stringData);
       fclose($fh);
   }
update();
?>