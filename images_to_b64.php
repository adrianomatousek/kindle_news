<?php
function imagesToB64($string) {
    //Converts all img links to Base64 encodes
    //This allows us to maintain simplicity of generating 1 file
    //With a drawback of (slightly) larger image size
    $out = preg_replace_callback(
    "(src=\"(.*?)\")",
    function($match) {
        static $id = 0;
        $id++;
        $path = $match[1];
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $curl = curl_init($path);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        $ret_val = curl_exec($curl);
        $b64_image_data =  chunk_split(base64_encode($ret_val));
        curl_close($curl);
        return 'src="data:image/' . $type . ';base64,' . $b64_image_data . '" style="width:100%"';
    },
    $string);
    return $out;
}
?>