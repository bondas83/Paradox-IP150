<?php
//config
$passw = 'paradox';//password
$user = '0000';//pin
$domain = 'http://192.168.1.2/';//IP150 module IP or domain
//config ends

logWho();

$url = $domain . 'login_page.html';

//$ses = '320D0260C15330C6';//debug mode
if (!isset($ses)) {
    for ($i = 1; $i <= 4; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($ch);
        if (!$ch) {
            echo 'No connection to IP Module, are you already logged in? Attempting logout and trying again...<br>';
            logout($domain);
            sleep(2);
        } else {
            curl_close($ch);

            preg_match('/loginaff\("([A-Z0-9]*)"/', $return, $matches);
            $ses = $matches[1];
            if (strlen($ses) == 16) {
                break;
            }
            echo 'SES key not found in reply... <br>';
            sleep(4);
        }
    }

    if (!strlen($ses)) {
        //logData($return);
        exit('SES Key now found'); //lets try 4 times
    }
    echo 'SES Key Found: ' . $ses . '<br>';
}

$spass = strtoupper(md5($passw)) . $ses;
$p = strtoupper(md5($spass));
$user = ''.(int)($user * 5);
$u = rc4($spass, $user);


//echo 'p: ' . $p;
//echo '<br>';
//echo 'spass ' . $spass;
//echo '<br/>';
//echo 'u: ' . $u;
//echo '<br>';

loginIp150($domain, $u, $p);
turnOn($domain, $u, $p);
logout($domain, $u, $p);


function logout($domain, $u, $p)
{
    $url = $domain . 'logout.html?u=' . $u . '&p=' . $p;
//    echo 'turn on url: ' . $url.'<br>';

    $curl_data = getCurlStatus($url);

    if ($curl_data[0] == 200) {
        echo 'Successful logout<br>';
    }
}

function turnOn($domain, $u, $p)
{
    $url = $domain . 'statuslive.html?area=00&value=i&u=' . $u . '&p=' . $p;
//    echo 'turn on url: ' . $url.'<br>';

    $curl_data = getCurlStatus($url);

    if ($curl_data[0] != 200) {
        echo '<span style="color:red">Failed turn On Alarm error:' . $curl_data[0] . '</span><br>';
    } else {
        if(strstr($curl_data[1], 'You must activate your javascript')){
            echo '<span style="color:red">Failed turn On Alarm</span><br>';
        }else{
            echo 'Successful turn On Alarm<br>';
        }
    }

}

function loginIp150($domain, $u, $p)
{
    $url = $domain . 'default.html?u=' . $u . '&p=' . $p;
    echo 'login url: ' . $url . '<br>';
    $curl_data = getCurlStatus($url);

    if ($curl_data[0] != 200) {
        exit('Cant connect, return error code' . $curl_data[0]);
    }

    if(strstr($curl_data[1], 'newaitaff.html')){
        exit('Are you already logged in?');
    }

    sleep(8);//waiting until initialize

    echo 'Successful Login<br>';
}

function getCurlStatus($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_ENCODING, '');

    $data = curl_exec($ch);
    //logData($data);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $data];
}

function rc4($key, $text)
{
    $kl = strlen($key);

    for ($i = 0; $i < 256; $i++) {
        $s[$i] = $i;
    }

    $y = 0;
    $x = $kl;
    while ($x != 0) {
        $x = $x - 1;
        $y = (ord($key[$x]) + $s[$x] + $y) % 256;
        //echo "y: " + $y."\n";
        $t = $s[$x];
        $s[$x] = $s[$y];
        $s[$y] = $t;
    }
    #print "s: "
    #print s
    $x = 0;
    $y = 0;
    $z = "";
    for ($x = 0; $x < strlen($text); $x++) {
        $x2 = $x & 255;
        $y = ($s[$x2] + $y) & 255;
        $t = $s[$x2];
        $s[$x2] = $s[$y];
        $s[$y] = $t;
        $temp = chr(ord($text[$x]) ^ $s[($s[$x2] + $s[$y]) % 256]);
        #temp = String.fromCharCode((text.charCodeAt(x) ^ s[(s[x2] + s[y]) % 256]))
        #print "temp" + str(x) + ": " + str(ord(temp))
        //echo "ord(temp[0]: "  . ord($temp[0])."<br>";
        $z .= d2h(ord($temp[0]));
    }
    //echo "z: " + $z;
    return $z;
}

function d2h($d)
{
    //echo "d: " + $d."..<br>";
    $hd = "0123456789ABCDEF";
    $h = $hd[$d & 15];
    //echo 'h' . $h . "<br>";
    while ($d > 15) {
        $d >>= 4;
        $h = $hd[$d & 15] . $h;
    }

    if (strlen($h) == 1) {
        $h = "0" . $h;
    }

    //print "h: " . $h."<br>";
    return $h;
}

function logWho()
{
    file_put_contents('paradox.log', date('Y-m-d H:i:s') . ' ' . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);
}

function logData($data)
{
    file_put_contents('paradox.log', $data . "\n", FILE_APPEND);

}

?>