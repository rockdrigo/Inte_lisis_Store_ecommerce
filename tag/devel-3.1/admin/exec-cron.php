<?php
$cron = dirname(__FILE__).'/cron-pendingjobs.php';

$i = 0;

while($i<5)
{
sleep(10);
$out = shell_exec('php '.$cron);
print($out);
$i++;
}
