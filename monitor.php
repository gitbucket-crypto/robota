<?php
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");

define('VERSION','1.00');
define('AUTOR',"#--- PEDRO HENRIQUE SILVA DE DEUS ---# \n Email: pedro.hsdeus@aol.com ".VERSION." \n");
#################################################################################################

if(PHP_OS=='Linux')
{
    if(@$argv[1]=='develop')
    {
        define('bash', 'echo ZWNobyBmYXN0OTAwMiB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYx  | base64 -d | bash');
    }
    else
    {
        define('bash','echo ZWNobyAzbDNtMWQxQCB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYxCg== | base64 -d  | bash');
    }
}

function getMyArtifactNumber()
{
    return str_replace('"','',trim( file_get_contents('artifact.json')));
}

function getMacAdress()
{
    return str_replace('"','',trim( file_get_contents('mac.json')));
}

function ListDevices()
{
    if(PHP_OS=='Linux')
    {
        $lswh = shell_exec('sudo lshw -json'); 
        return $lswh;
    }
    else
    {
        return shell_exec('pnputil /enum-devices /connected');
    }
}


function sendHwInfo($hardware, $macaddres)
{
   try
   {
        $url="https://boe-php.eletromidia.com.br/rmc/nuc/hwinfo/add ";
        $artifact = getMyArtifactNumber();

        $postdata =http_build_query(
                array(
                    'hwinfo' => json_encode( $hardware ),
                    'csrf' => md5(time()),
                    'artifact' => $artifact,
                    'macaddress'=> $macaddres
                )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);
        $result = file_get_contents( $url, false, $context );
        var_dump($result);
   }
   catch(Exception $e)
   {
        registerArtifactHw($artifact, $hardware, $macaddres);
   }
}

function logger($texto)
{
   try
   {
	   $url ='https://boe-php.eletromidia.com.br/rmc/nuc/log';

       $artifact = getMyArtifactNumber();

        $postdata =http_build_query(
                array(
                    'csrf' => md5(time()),
                    'artifact' => $artifact,
                    'log'=> $texto
                )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);
        $result = file_get_contents( $url, false, $context );
        var_dump($result."\r\n");
   }
   catch(Exception $e)
   {
	   logger($e->getMessage());
	   logger($texto);
   }
}


function run()
{
    if(!file_exists(__DIR__.DIRECTORY_SEPARATOR.'hw.json'))
    {
        $lswh = ListDevices();
        sendHwInfo($lswh ,  getMacAdress());
        $fp = fopen( getcwd().DIRECTORY_SEPARATOR.'hw.json','w');
        fwrite($fp, $lswh);
        fclose($fp);
        unset($fp);
    }
    nuceport();

}



####################################################################################################################

run();

####################################################################################################################

function sendData($memory, $cpu, $hdd,$type, $whois, $temp)
{
    try
    {
        $postdata = http_build_query(
            array(
                'memory' => $memory,
                'cpu' => $cpu,
                'hdd' => $hdd,
                'tipo' => $type,
                'csrf' => md5(time()),
                'artifact'=> $whois,
                'temperature' => $temp
             )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        $result = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/report/add', false, $context);
        var_dump($result."\r\n");
    }
    catch(\Exception $e)
    {
        @sendData($memory, $cpu, $hdd,$type, $whois, $temp);
    }
}



#####################################################################################################################

function getTemp()
{
    if(PHP_OS == 'Linux')
    {
        return  shell_exec('sensors');
    }
    else
    {
        return shell_exec('wmic OS get TotalVisibleMemorySize /Value & wmic OS get FreePhysicalMemory /Value');
    }
}


function getMemory()
{
    if(PHP_OS == 'Linux')
    {
     
       return  shell_exec('free -m');
    }
    else
    {
        return shell_exec('wmic ComputerSystem get TotalPhysicalMemory   & wmic OS get FreePhysicalMemory ');
    }
}

function getCPU()
{
    if(PHP_OS == 'Linux')
    {        
        return shell_exec('iostat -c 1 1');
    }
    else
    {
        return shell_exec('wmic cpu get loadpercentage /format:Value');
    }
}

function getHDD()
{
    if(PHP_OS == 'Linux')
    {
        return shell_exec('df -ht ext4');
    }
    else
    {
        return shell_exec('wmic /node:"%COMPUTERNAME%" LogicalDisk Where DriveType="3" Get DeviceID,FreeSpace, Size|find /I "c:"');
    }
}

function forceReboot()
{
    if(PHP_OS == 'Linux')
    {
        ///etc/sudoers
        //%www-data ALL=NOPASSWD: /sbin/reboot
        shell_exec('sudo /sbin/reboot');
    }
    else
    {
        exec('shutdown /r /t 0');
    }
}

function nuceport()
{
    if(PHP_OS=='Linux')
    {
        
        $whois =getMyArtifactNumber();
        $mem = (string)getMemory();
        $mem = (explode(' ', $mem));
        //var_dump($mem); exit;
        if(isset($mem['48']))
        {
            $total = $mem['48'];
        } 
        if(isset($mem['53']))
        {
            $total = $mem['53'];
        }        
        if(isset($mem['81']))
        {
            $usada = $mem['81'];
        }
        if(isset($mem['62']))
        {
            $usada = $mem['62'];
        }
        $livre = floatval($total)- floatval($usada);

        $memory= ''.$total.':'.$usada.':'.$livre;
        echo 'total:'.$total.'  usada:'.$usada.'  livre:'.$livre.PHP_EOL;

        $cpu = getCPU();
        $cpu = explode(' ',$cpu);
        //var_dump($cpu); exit;
        if(isset($cpu['47']))
        {
            $iddle = $cpu['47'];
        }
        if(isset($cpu['45']))
        {
            $iddle = $cpu['45'];
        }
        
        if(isset($cpu['28']))
        {
            $load = $cpu['28'];
        }
        if(isset($cpu['27']))
        {
            $load = $cpu['27'];
        }
        
        $cpusa = 'load:'.@$load.'  iddle:'.@$iddle;
        $cpusa = trim(ltrim($cpusa,' '));
        print($cpusa."\r\n");
        $cpuUsage = ''.$load.':'.$iddle;


        $disk = getHDD();  
        $disk = explode(' ', $disk);
        //var_dump($disk); exit;
        if(isset($disk['22']))
        {
            $htd=$disk['22'];
        }
         if(isset($disk['27']))
        {
            $htd=$disk['27'];
        }
        print($htd."\r\n");

        $temp = getTemp();
        $temp = json_encode($temp, true);

        if(floatval($load)>floatval('95.00'))
        {
            sendData($memory, $cpuUsage, $htd,'alto uso de cpu reboot imediato',$whois, $temp);
            sleep(1);
            forceReboot();
            sleep(1);
            forceReboot();
        }
        if(floatval($usada)/floatval($total)>floatval(0.80))
        {
            sendData($memory, $cpuUsage, $htd,'alto uso de memoria reboot imediato',$whois, $temp);
            sleep(1);
            forceReboot();
            sleep(1);
            forceReboot();
        }
        sendData($memory, $cpuUsage, $htd,'normal',$whois, $temp);
    }
    else
    {
        $whois =getMyArtifactNumber();

        $cpu = getCPU();
        $cpu = trim($cpu);
        $cpu = ltrim($cpu,'  ');
        $cpu = explode('=',$cpu,PHP_INT_MAX);
        $iddle= floatval(100) - floatval($cpu[1]);
        $load = $cpu[1];
        $cpuUsage = 'load:'.$cpu[1].'  iddle:'.$iddle;
        print_r($cpuUsage);
        echo PHP_EOL;

        $disk = getHDD();
        $disk = trim($disk);
        $disk = explode(' ',$disk,PHP_INT_MAX);
        $free = floatval($disk[8]);
        $total = floatval($disk[10]);
        $used = $total - $free;
        $percent = (($used*100)/$total);
        $htt = 'hd usage '.round($percent).'%';
        print_r($htt);
        echo PHP_EOL;

        $mem = trim(strval(getMemory()));
        $temp = substr($mem, strlen('TotalPhysicalMemory'), strlen($mem));
        $totMem =trim( substr($temp, 0, 14));

        $freeMem =trim( substr($temp,47,strlen($temp)));

        if(strlen($freeMem)==7)
        {
            $freeMem.='000';
        }

        $usedMemo = strval(intval($totMem) - intval($freeMem));

        $memory ='total: '.formatBytes(intval($totMem)).' usada: '.formatBytes(intval($usedMemo)).' livre: '.formatBytes(intval($freeMem));
        echo $memory.PHP_EOL;

        if(floatval($load)>floatval('95.00'))
        {
            sendData($memory, $cpuUsage, $htt,'alto uso de cpu reboot imediato',$whois, $temp);
            sleep(1);
            forceReboot();
            sleep(1);
            forceReboot();
        }

        if(floatval($usedMemo/$totMem)>floatval(0.80))
        {
            sendData($memory, $cpuUsage, $htt,'alto uso de memoria reboot imediato',$whois, $temp);
            sleep(1);
            forceReboot();
            sleep(1);
            forceReboot();
        }
        sendData($memory, $cpuUsage, $htt,'normal',$whois, $temp);
    }

}


function utf8_str_split(string $input, int $splitLength = 1)
{
    $re = \sprintf('/\\G.{1,%d}+/us', $splitLength);
    \preg_match_all($re, $input, $m);
    return $m[0];
}

function formatBytes($bytes, $precision = 2) { 
    $i = floor(log($bytes) / log(1024));
    $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

    return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
} 

#####################################################################################################


