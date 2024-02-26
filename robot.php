<?php 
#####################################################################################################
date_default_timezone_set('America/Sao_Paulo');

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");

define('VERSION','3');
define('AUTOR',"#--- PEDRO HENRIQUE SILVA DE DEUS ---# \n Email: pedro.hsdeus@aol.com ".VERSION." \n");
#######################################################################################################
$arg ='deploy';
if($arg=='develop')
{
    define('bash', 'echo ZWNobyBmYXN0OTAwMiB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYx  | base64 -d | bash');
}
else
{
    define('bash','echo ZWNobyAzbDNtMWQxQCB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYxCg== | base64 -d  | bash');
}

__wakeup();
function __wakeup()
{
    if(floatval(phpversion())<7.4)
    {
        die('A versão do PHP deve ser acima de 7.4.3');
    }
    init();
    if(checkEthernet() ==true)
	{
        $f = file_exists(__DIR__.DIRECTORY_SEPARATOR.'ini.json') ? 'TRUE' : 'FALSE';
        if($f=='FALSE')
        {            
            getsysFiles();
            pre();
        }
        if($f=='TRUE')
        {
            echo '#####-POST-RUN-#####'.PHP_EOL;;
            post();
        }
    }
    else
    {
        __sleep();
    }
}

function __sleep()
{
    echo 'offline trying again in 2 minutes'.PHP_EOL;
    sleep(120);
    __wakeup();
}

function checkEthernet()
{
    switch (connection_status())
    {
        case CONNECTION_NORMAL:
            $msg = 'You are connected to internet.';
            echo $msg.PHP_EOL;
            return true;
        break;
        case CONNECTION_ABORTED:
            $msg = 'No Internet connection';
            echo $msg.PHP_EOL;
            return false;
        break;
        case CONNECTION_TIMEOUT:
            $msg = 'Connection time-out';
            echo $msg.PHP_EOL;
            return false;
        break;
        case (CONNECTION_ABORTED & CONNECTION_TIMEOUT):
            $msg = 'No Internet and Connection time-out';
            echo $msg.PHP_EOL;
            return false;
        break;
        default:
            $msg = 'Undefined state';
            echo $msg.PHP_EOL;
            return false;
        break;
    }
}

#######################################################################################################

function getsysFiles()
{
    $log = '';

    $f = file_exists(__DIR__.DIRECTORY_SEPARATOR.'files.json') ? 'TRUE' : 'FALSE';
    if($f=='FALSE')
    {
        if(download('soc.py')==true)
        {
            $log.='soc_py - deployed ';
        }
        else $log.='soc_py - undeployed ';

        if(download('report.py')==true)
        {
            $log.='report_py - deployed ';
        }
        else  $log.='report_py - undeployed "';

        if(download('modem.py')==true)
        {
            $log.='modem_py - deployed ';
        }
        else  $log.='modem_py - undeployed "';

        if(download('commando.py')==true)
        {
            $log.='commando_py - deployed ';
        }
        else  $log.='commando_py - undeployed "';


        $fp = @fopen( getcwd().DIRECTORY_SEPARATOR.'files.json' ,'w+');
        fwrite($fp, $log);
        fclose($fp);
    }
}

function download($file)
{
    if(file_exists( getcwd().DIRECTORY_SEPARATOR.$file))
    {
        unlink( getcwd().DIRECTORY_SEPARATOR.$file);
    }

    try
    {
        $fp = fopen( getcwd().DIRECTORY_SEPARATOR.$file,'w+');
        fwrite($fp,  file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/files/get?csrf='.md5(time()).'&file='.$file ));
        fclose($fp);
        unset($fp);

        if(file_exists( getcwd().DIRECTORY_SEPARATOR.$file)==true)
        {
            return true;
        }
        else return false;
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }

}
#######################################################################################################


function init()
{
    if(PHP_OS== "Linux")
    {
        $py = shell_exec(bash.'&& sudo dpkg -s python3 | grep Version'); 
        if(trim($py)=='')
	    {
			shell_exec(bash.' && sudo apt install python3.9-full python3.9-dev python3.9-venv python3-pip -y');
			init();
	    }
        $ver = substr($py, 9, strlen($py));
        $ver = substr($ver, 0, 4);       
        define('python', 'python'. floatval($ver));
    }
    else
    {
        define('python', 'python');
    }
    echo python.PHP_EOL;
}


function killPython()
{
    if(PHP_OS == "Linux")
    {
        @shell_exec("killall -s 9 ".python);
    }
    else
    {
        @exec("taskkill /IM python.exe /F");
    }
}

#######################################################################################################
function pre()
{
    killPython(); sleep(2); killPython();
    defineMacAdress();
    defineMyArtifactNumber();
    getTeamviewer();
    $deploy =  @file_exists( getcwd().DIRECTORY_SEPARATOR.'files.json') ? true:false;
    if($deploy)
    {
        echo 'send log'.PHP_EOL;
        $log = serialize(file_get_contents( getcwd().DIRECTORY_SEPARATOR.'files.json'));

        logger($log);

        $fp = fopen( getcwd().DIRECTORY_SEPARATOR. 'ini.json' ,'w+');
        fwrite($fp, '"'.getMacAdress().'--'.defineMyArtifactNumber().'"');
        fclose($fp);

       
        if(PHP_OS=='Linux')
        {   
            createJob();
            getAbreSH();
        }        
        post();
    }

}


function defineMacAdress()
{
    if(file_exists(getcwd(). DIRECTORY_SEPARATOR.'mac.json')==false)
    {
		$out =  shell_exec(python.' report.py');
		$json =  json_decode($out, true);
        
        $fp = fopen(getcwd(). DIRECTORY_SEPARATOR.'mac.json' ,'w+');
        fwrite($fp, '"'. $json["mac"].'"');
        fclose($fp);
        chmod(getcwd(). DIRECTORY_SEPARATOR.'mac.json', 0777);
    }
    else  echo 'mac.json file already generated'.PHP_EOL;
}

function getMacAdress()
{
    return str_replace('"','',trim( file_get_contents('mac.json')));
}

function defineMyArtifactNumber()
{
    if(!file_exists(getcwd(). DIRECTORY_SEPARATOR.'artifact.json'))
    {
        $resp =registerRobot(); 
	    $json = (json_decode($resp, true));
        if($json['code']==202)
        {
            $uid = $json['msg'];
		    $fp = fopen(getcwd(). DIRECTORY_SEPARATOR.'artifact.json' ,'w+');
		    fwrite($fp, '"'.$uid.'"');
		    fclose($fp);
        }   
        if($json['code']!=202)
        {
            echo PHP_EOL;
            die('Mac addr already registered cannot assing a artifact number '. getMacAdress()); 
        }
	}
	else echo 'artifact.json file already generated'.PHP_EOL;
}

function getMyArtifactNumber()
{
    return str_replace('"','',trim( file_get_contents('artifact.json')));
}

function registerRobot()
{
    $mac = getMacAdress();
    try
    {
        $csrf = md5(time());

        $query = http_build_query(array('csrf' => $csrf , 'mac'=> $mac));

        $opts = array('http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $query
                )
        );
        $context  = stream_context_create($opts);

        $url="https://boe-php.eletromidia.com.br/rmc/nuc/add";


        $result = file_get_contents($url ,false, $context);
        return $result;
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }
}

function getTeamviewer()
{
    global $conf; 
    global $teamviewer;
    if(PHP_OS=='Linux')
    {
        $tem =  shell_exec(bash.'&& sudo cat /etc/teamviewer/global.conf');
    }
    else
    {
        $tem =  shell_exec("reg query HKEY_LOCAL_MACHINE\SOFTWARE\Teamviewer");
    }
 
    $len = strlen($tem);
    switch($len)
    {
        case 8617:
            $tmp = explode(' ',$tem);
            $cid = trim($tmp[36]);
            unset($tmp);
            $teamviewer = substr($cid,0,10);
        break;
        case 8641:
            $tmp = explode(' ',$tem);
            $cid = trim($tmp[36]);
            unset($tmp);
            $teamviewer = substr($cid,0,10);
        break;
        case 8586:
            $tmp = explode(' ',$tem);
            var_dump($tmp, '8586');
            $cid = trim($tmp[36]);
            unset($tmp);
            $teamviewer = substr($cid,0,10); 
        break;
        case 7824:
            $tmp = explode(' ',$tem);
            $cid = trim($tmp[36]);
            unset($tmp);
            $teamviewer = substr($cid,0,10);
        break;
        case 7710:    
            $tmp = explode(' ',$tem);  
            $cid = trim($tmp[30]);
            unset($tmp);
            $teamviewer = substr($cid,0,10);
        break;
        case 4095:
            $tmp = explode(' ',$tem);
            $cid = trim($tmp[27]);
            unset($tmp);
            $teamviewer = substr($cid,0,10);
        break;      
        default:
            $teamviewer = tem;
        break;
    }
         
    $postdata = http_build_query(
        array(
            'csrf' => md5(time()),
            'artifact' => getMyArtifactNumber(),
            'teamviewer'=> $teamviewer
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

    $result = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/teamviewer/add', false, $context);

    echo $result.PHP_EOL;

    logger(strval($result));
 
}
#######################################################################################################

if (phpversion()!='8.2.7') 
{    function str_contains($haystack, $needle) 
    {
        return $needle !== '' &&  strpos($haystack, $needle) !== false;
    }
}

#############################################################################################


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
#############################################################################################


function createJob()
{
    if(file_exists(getcwd().DIRECTORY_SEPARATOR.'crontab.bkp'))
    {
        unlink(getcwd().DIRECTORY_SEPARATOR.'crontab.bkp');
    }
    if(@file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/crontab/get?csrf='.md5(time()), "r") !='v0' )
    {
        $fp = fopen( getcwd().DIRECTORY_SEPARATOR.'monitor.php','w');
        fwrite($fp,  file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/crontab/get?csrf='.md5(time())));
        fclose($fp);
        unset($fp);
        $bash = shell_exec('crontab -l ');
        logger($bash);
        shell_exec('crontab /var/www/elemidia_v4/fscommand/cronta.bkp');
        $bash = shell_exec('crontab -l ');
        logger($bash);
    }

}

function getAbreSH()
{  
    if(file_exists(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'abre.sh'))
	{
		unlink(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'abre.sh');
	}

    $fp = fopen(getcwd().DIRECTORY_SEPARATOR.'abre.sh' ,'w');
    fwrite($fp,  file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/abre/get?csrf='.md5(time())));
    fclose($fp);
    unset($fp);
    rename('abre.sh', dirname(__DIR__,1).DIRECTORY_SEPARATOR.'abre.sh');
}

function post()
{
    shell_exec('php monitor.php deploy');
    //------------Atualiza arquivos python-----------
    checkforFilesUpdate();
    //------------------PHP Update-----------------
    checkAutoUpdate();
    //-----------------------------------------------    
    getCommand();
    //-----------------------------------------------   
    runCronjob();
}



function checkforFilesUpdate()
{
    $result = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/files/updated?csrf='.md5(time()));
  
    $res = json_decode($result,true);
   
    if($res['code']==202 | $res['code']=='202')
    {
        echo 'update files'."\r\n";
        logger('updating files');
        killPython();
        unlink(__DIR__.DIRECTORY_SEPARATOR.'files.json');  
        unlink(__DIR__.DIRECTORY_SEPARATOR.'soc.py');  
        unlink(__DIR__.DIRECTORY_SEPARATOR.'modem.py');  
        unlink(__DIR__.DIRECTORY_SEPARATOR.'report.py');  
        unlink(__DIR__.DIRECTORY_SEPARATOR.'commando.py');  
        getsysFiles(); 
    }
    else
    {
        echo 'nothing to update'."\r\n";
        logger('nothing to update'); 
    }
    
   
}

function getCommand()
{
    try
    {
        $artifact = getMyArtifactNumber();
        $url ='https://boe-php.eletromidia.com.br/rmc/nuc/command/get';

 
        $postdata =http_build_query(
            array(
                'csrf' => md5(time()),
                'artifact' => $artifact
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
        $json = json_decode($result, true);

        if($json['code']==202)
        {
            $cmd = $json["msg"];
            
            if(isset($cmd["deploy_command"]) &&
               $cmd["deploy_command"]!='')
            {
                phpcommander($cmd["deploy_command"]);
            }

            makeschedule($cmd);
        }
        else
        {
            echo $json['msg'].PHP_EOL;
            logger($json['msg']);
        }    
    }
    catch(Exception $e)
    {
        logger($e->getMessage());
        getCommand();
    }
}

function phpcommander($command)
{
    logger($command);
    switch($command)
    {
        case 'reset_cron':
            createJob();
        break;
    }
}

function makeschedule(array $data)
{
    global $hora_padrao_desliga_tela, $hora_padrao_liga_tela, $line, $cron; 
    $hora_padrao_desliga_tela = '02:00';
    $hora_padrao_liga_tela = '06:00';
    $line='';

    if(file_exists('cron.json') && filesize('cron.json')>0)
    {
        $cronjson = fopen('cron.json', "r") or die("Unable to open file!");
        $cron =  fread($cronjson, filesize('cron.json'));
        fclose($cronjson);
        unlink('cron.json');
    }

    if(isset($data['schedule']) && $data['schedule']!=NULL)
    {

    }

    if(isset($data['command1']) && $data['command1']!=NULL && 
       isset($data['command2']) && $data['command2']!=NULL)
    {
       switch([$data['command1'], $data['command2']])
       {
            case ['display_off', 'display_on']:

                $hexa1 = $data["hexa_cmd1"];
                if($data["ack1_cmd1"]!=NULL | $data["ack1_cmd1"]!='')
                {
                   $hexa1.=$data["ack1_cmd1"];
                }
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='') 
                {
                   $hexa1.=$data["ack1_cmd2"];
                }

                if(isset($data["hora_comando1"]) &&  $data["hora_comando1"]!=NULL)
                {                    
                    $line.= "\n".isHour($data["hora_comando1"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
                else
                {
                    $line.= "\n"."{$hora_padrao_desliga_tela} |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
                
                $hexa2 = $data["hexa_cmd2"];
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='')
                {
                   $hexa2.=$data["ack1_cmd2"];
                }
                if($data["ack2_cmd2"]!=NULL | $data["ack2_cmd2"]!='') 
                {
                   $hexa2.=$data["ack2_cmd2"];
                }

                if(isset($data["hora_comando2"]) &&  $data["hora_comando2"]!=NULL)
                {                    
                    $line.=  "\n".isHour($data["hora_comando2"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa2".' @';
                }
                else
                {
                    $line.= "\n"."{$hora_padrao_liga_tela} |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa2".' @';
                }                    
            break;
            case ['display_off', '']:

                $hexa1 = $data["hexa_cmd1"];
                if($data["ack1_cmd1"]!=NULL | $data["ack1_cmd1"]!='')
                {
                   $hexa1.=$data["ack1_cmd1"];
                }
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='') 
                {
                   $hexa1.=$data["ack1_cmd2"];
                }

                if(isset($data["hora_comando1"]) &&  $data["hora_comando1"]!=NULL)
                {                    
                    $line.= "\n".isHour($data["hora_comando1"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
                else
                {
                    $line.= "\n"."{$hora_padrao_desliga_tela} |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
               
            break;
            case ['', 'display_on']:
                $hexa2 = $data["hexa_cmd2"];
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='')
                {
                   $hexa2.=$data["ack1_cmd2"];
                }
                if($data["ack2_cmd2"]!=NULL | $data["ack2_cmd2"]!='') 
                {
                   $hexa2.=$data["ack2_cmd2"];
                }

                if(isset($data["hora_comando2"]) &&  $data["hora_comando2"]!=NULL)
                {                    
                    $line.=  "\n".isHour($data["hora_comando2"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa2".' @';
                }
                else
                {
                    $line.= "\n"."{$hora_padrao_liga_tela} |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa2".' @';
                }                                   
            break;
            case ['backlight_status','']:
            {
                $hexa1 = $data["hexa_cmd1"];
                if($data["ack1_cmd1"]!=NULL | $data["ack1_cmd1"]!='')
                {
                   $hexa1.=$data["ack1_cmd1"];
                }
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='') 
                {
                   $hexa1.=$data["ack1_cmd2"];
                }

                if(isset($data["hora_comando1"]) &&  $data["hora_comando1"]!=NULL)
                {                    
                    $line.=  "\n".isHour($data["hora_comando1"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
                else
                {
                    $line.= "\n".Hour(date("H:i:s"))." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }                             

            }
            case ['backlight_off','']:
            {
                $hexa1 = $data["hexa_cmd1"];
                if($data["ack1_cmd1"]!=NULL | $data["ack1_cmd1"]!='')
                {
                    $hexa1.=$data["ack1_cmd1"];
                }
                if($data["ack1_cmd2"]!=NULL | $data["ack1_cmd2"]!='') 
                {
                    $hexa1.=$data["ack1_cmd2"];
                }

                if(isset($data["hora_comando1"]) &&  $data["hora_comando1"]!=NULL)
                {                    
                    $line.=  "\n".isHour($data["hora_comando1"])." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
                else
                {
                    $line.= "\n".Hour(date("H:i:s"))." |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."commando.py $hexa1".' @';
                }
            }
       } 
       if($cron!=NULL | $cron!='')
       {
            $cron .="\n".$line.' @';
       }
       else 
       {
            $cron= $line;
       }
       $file = fopen("cron.json", "w+");
       fwrite($file, $cron."\n");
       fclose($file);
    }


}

function isHour($time)
{
    if (date("H:00:00", strtotime($time )) == date("H:i:00", strtotime($time )))
    {
        $date =  str_replace(":00", "", $time);
    }
    else
    {
        $minute =  str_replace("00:", "", $time);
        $date = date('H:i:s', strtotime("now +{$minute} minutes"));
    }
    return $date;
}


function runCronjob()
{
    if(file_exists(getcwd().DIRECTORY_SEPARATOR.'cron.json')==false |
       @filesize(getcwd().DIRECTORY_SEPARATOR .'cron.json')== 0  )
    {
         logger('nothing in cron job'); return false;
    }

    logger('Jobs to execute '. file_get_contents(getcwd().DIRECTORY_SEPARATOR.'cron.json'));

    $exec = file_get_contents(getcwd().DIRECTORY_SEPARATOR.'cron.json');
    
    $deploy = explode('@', $exec);
    $deploy = array_values($deploy);

    
    for($i=0 ; $i<sizeof($deploy) ; $i++)
    {
        sleep(1);
        if(substr_count($deploy[$i],"|")>=1)
        {
            $dep = explode('|', $deploy[$i]);
            $hour = trim(ltrim($dep[0],' '));
            @$command = trim(trim($dep[1],' '));

            if($hour==strval(date('H:i')))
            {
                echo $command;
                $log = execute($command);
                cronReport($command , $log);
               logger($command.' -> '.$log);
            }
            echo $hour.' '.$command.PHP_EOL;
        }        
    }
    exit;
}


function execute($command)
{
    @killPython();
    sleep(1);
    @killPython();
    if(PHP_OS== "Linux")
    {
       return shell_exec($command);
    }
    else
    {
        return exec($command);
    }
}


function cronReport($command,$log)
{
    $artifact = getMyArtifactNumber();
    $url ='https://boe-php.eletromidia.com.br/rmc/nuc/command/status';


    $postdata =http_build_query(
        array(
            'csrf' => md5(time()),
            'artifact' => $artifact,
            'command' => $command,
            'status' => $log
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
}


function checkAutoUpdate()
{
    try
   {
        $version = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/robot/version?csrf='.md5(time()));
        $version = substr($version,0,5);
        $version = str_ireplace('v','',$version).PHP_EOL;
        $version = floatval($version);

        if (floatval($version)> floatval(VERSION))
        {
            echo 'php self update '. $version.PHP_EOL;
            logger('self update php');
            //@selfUpdate();
        }
        else 
        {
            $msg = 'Versão igual ou mais antiga robot.php no servidor ';
            logger($msg );
            echo $msg .PHP_EOL; 
        }
   }
   catch(Exception $e)
   {
        logger($e->getMessage());
   }
}


function selfUpdate()
{
    $updatedCode = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/robot/get?csrf='.md5(time()));
    if(empty($updatedCode))
    {
        echo 'no code on server'.PHP_EOL;
    }
    if(!empty($updatedCode))
    {
        // Overwrite the current class code with the updated code
        file_put_contents(__FILE__, '<?'.$updatedCode);
        require_once __FILE__;
    }
}
