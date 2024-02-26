<?php
require_once("../include/system.functions.php");

$configFile     = LOCAL_PATH . CONF_NAME;
$timer          = 60;
$timerDebug     = 1;
$debug          = FALSE;
$redimensionarA = null;
$redimensionarW = null;
$redimensionarH = null;
$redimensionarX = null;
$redimensionarY = null;

function releitura($dados)
{
    global $configFile, $debug;

    $redimensionarA = @$dados["redimensionar"]["ativar"];
    $redimensionarW = @$dados["redimensionar"]["width"];
    $redimensionarH = @$dados["redimensionar"]["height"];
    $redimensionarX = @$dados["redimensionar"]["positionx"];
    $redimensionarY = @$dados["redimensionar"]["positiony"];

    $forcarInput     = @$dados["sistema"]["forca_resolucao_input"];
    $forcarW         = @$dados["sistema"]["forca_resolucao_largura"];
    $forcarY         = @$dados["sistema"]["forca_resolucao_altura"];

    if (is_numeric($forcarW) && is_numeric($forcarY) && is_string($forcarInput)) {
        @log_create("info", "ForceResolution", sprintf("Force Resolucao ativado! Input: %s X: %s Y: %s", $forcarInput, $forcarW, $forcarY));
    }

    if ($redimensionarA == "1") {

        if (time() - filemtime($configFile) <= 1200) {
            $debug  = TRUE;
        } else {
            $debug  = FALSE;
        }
        @log_create("info", "Resize", sprintf("Resize ativado! W: %s H: %s X: %s Y: %s", $redimensionarW, $redimensionarH, $redimensionarX, $redimensionarY));
        exec(sprintf('export DISPLAY=:0; wmctrl -r "adobe flash" -e 0,%s,%s,%s,%s', $redimensionarX, $redimensionarY, $redimensionarW, $redimensionarH));
    }

    if (is_numeric($forcarW) && is_numeric($forcarY) && is_string($forcarInput)) {
        exec(sprintf('xrandr --output %s --mode %sx%s -display :0.0', $forcarInput, $forcarW, $forcarY));
    }
}
function readSystem()
{

    $xmldoc = new DOMDocument();
    $xmldoc->load(LOCAL_PATH . CONF_NAME);
    $xmldoc->saveXML();

    $rootNode = $xmldoc->firstChild;
    $config = xml_toArray($rootNode);

    return $config;
}
while (true) {
    set_time_limit(75);
    releitura(readSystem());
    if ($debug) {
        sleep($timerDebug);
    } else {
        sleep($timer);
    }
}
