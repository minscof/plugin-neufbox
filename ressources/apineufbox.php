<?php
$xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>PHP: Behind the Parser</title>
  <characters>
   <character>
    <name>Ms. Coder</name>
    <actor>Onlivia Actora</actor>
   </character>
   <character>
    <name>Mr. Coder</name>
    <actor>El Act&#211;r</actor>
   </character>
  </characters>
  <plot>
   So, this language. It's like, a programming language. Or is it a
   scripting language? All is revealed in this thrilling horror spoof
   of a documentary.
  </plot>
  <great-lines>
   <line>PHP solves all my web problems</line>
  </great-lines>
  <rating type="thumbs">7</rating>
  <rating type="stars">5</rating>
 </movie>
</movies>
XML;

$movies = new SimpleXMLElement($xmlstr);

echo $movies->movie[0]->plot;

$xmlstr = <<<XML
<rsp stat="ok" version="1.0">
<auth token="b3a9ddeed3766894fcb69127bff886" method="passwd"/>
</rsp>
XML;

$request = 'http://192.168.0.1/api/1.0/?method=auth.getToken';
$request = new com_http($request);
$xmlstr = $request->exec(5, 1);
//$jsonstatus = json_decode($request->exec(5, 1), true);

$neufbox = new SimpleXMLElement($xmlstr);

echo $neufbox->auth['token'];

$request = 'http://192.168.0.1/api/1.0/?method=auth.checkToken&token='.$neufbox->auth['token'];
$request = new com_http($request);
$xmlstr = $request->exec(5, 1);
echo $xmlstr;
?>