<?php
$app = new Phar("bin/docblock.phar", 0, "bin/docblock.phar");
$app->addFile('src/docblock.php');
$defaultStub = $app->createDefaultStub("src/docblock.php");
$stub = "#!/usr/bin/env php \n".$defaultStub;
$app->setStub($stub);
$app->stopBuffering();
?>

