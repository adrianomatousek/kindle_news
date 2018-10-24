<?php
function sendEmail($to,$subject,$message,$attachment) {
    
    // a random hash will be necessary to send mixed content
    $separator = md5(time());
    
    // carriage return type (we use a PHP end of line constant)
    $eol = PHP_EOL;
    
    /*
    // attachment name
    
    //$filename = "BBC-News-" . date("h:i:sa") . ".html";
    $filename = basename($attachment).PHP_EOL;//Name of file
    
    // encode data (puts attachment in proper format)
    
    $attachment = chunk_split(base64_encode(file_get_contents($attachment)));
    */
    $path = $attachment;
    // Read the file content
    $filename = "Hello.html";
    $file = $path;
    $file_size = filesize($file);
    $handle = fopen($file, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    //echo "<br>" . $content;
    $attachment = $content;
    
    // main header
    $headers  = "From: ".$from.$eol;
    $headers .= "MIME-Version: 1.0".$eol; 
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
    
    // no more headers after this, we start the body! //
    
    $body = "--".$separator.$eol;
    $body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
    $body .= "This is a MIME encoded message.".$eol;
    
    // message
    $body .= "--".$separator.$eol;
    $body .= "Content-Type: text/html; charset=\"iso-8859-1\"".$eol;
    $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
    $body .= $message.$eol;
    
    // attachment
    $body .= "--".$separator.$eol;
    $body .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol; 
    $body .= "Content-Transfer-Encoding: base64".$eol;
    $body .= "Content-Disposition: attachment".$eol.$eol;
    $body .= $attachment.$eol;
    $body .= "--".$separator."--";
    
    // send message
    if (mail($to, $subject, $body, $headers)) {
    echo "<br>Success!";
    } else {
    echo "Failed to send email";
    }
} 

?>