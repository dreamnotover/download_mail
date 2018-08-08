<?php

require_once  './Zend/Mail/Storage/Imap.php';
 
class get_facebook_mail_report {
	
	 
	
	public function download_dacebook_runid($source, $mail_server, $use_ssl, $mail_server_user_name, $mail_server_passwd,   $check_day, $days=1) {
		$information = '';
		$attachment = '';
		$attach = '';
		$attach_except ="";
		$part = NULL;		
		$except_arr = array();
		$downloaded_arr=array();
		$errordate_arr=array();
		$emptyfile_arr=array();
		$run_id =array();
		 $path = './Zend';
        set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        set_time_limit(1000);
		
        require_once './Zend/Loader/Autoloader.php';
        Zend_Loader_Autoloader::getInstance();
		echo "  before  download report memory  is  " . memory_get_usage () / 1024 / 1024 . "M .";
		try {
			
			 
			$mail_server_port = $use_ssl=='1' ? 993 : 143;
			
			$mail_server_user_name =trim($mail_server_user_name );			
			$mail_server_passwd=trim($mail_server_passwd);
			echo  "   download_report($source, $mail_server, $use_ssl, $mail_server_user_name, $mail_server_passwd,   $check_day)  ";
			$mail = new Zend_Mail_Storage_Imap ( array (
					'host' => $mail_server,
					'user' => $mail_server_user_name,
					'password' => $mail_server_passwd,
					'ssl' =>true,
					'port' =>$mail_server_port
			) );
			 
			$folder = $mail->getFolders()->Inbox;
			 
			$mail->selectFolder ( $folder );
		
			$begin_time = strtotime ( $check_day );
			$end_time = $begin_time + 86400 * ($days-1 );
			$num =  0;
			 $total = $mail->countMessages();
            //loop through the mails, starting from the latest mail
			echo "  $total  mails ";
            while ($total > 0) {
                $message = $mail->getMessage($total);				
				$arrive_time = strtotime ( $message->date );
				$mail_date = date ( 'Y-m-d', $arrive_time );
			    $diff =   $begin_time -  $arrive_time- 86400 * 1;
				if ($diff >0){
				    echo  " $mail_date older than  start day and  quit ";
            		break;		
				}
				if ($arrive_time>$end_time ){
					$total = $total - 1;
					echo " $mail_date  $arrive_time  younger than endday skip and continue<br> ";
					continue;
				}
				 
				if ($source === 'facebook' && strstr ( $message->subject, 'campaign_yesterday_hourly' ) == false){
					$total = $total - 1;
					continue;
				}
				 
				$content=$message->getContent()	;
			 
				  if (preg_match('/report_run_id=3D([0-9]+)/', $content, $id)) {
                    $rid = $id[1];
                }
                if (preg_match("/Your scheduled report is ready for\s+(\d{4}-\d{2}-\d{2})/", $content, $report_date)) {
                    echo "  Your scheduled report is ready for  $report_date[1] <br> ";
					$day=$report_date[1];
                }
				$run_id[$day]=$rid;
				$total = $total - 1;
		} 
		$mail->close (); 
			echo "  after  download  report is " . memory_get_usage () / 1024 / 1024 . "M.";
			echo   $information;
		return $run_id;
	} 
	catch ( Exception $e ) {			
			echo $information . $e->getMessage () . 'download failed!!'; 
    }
	}

}

date_default_timezone_set("Asia/Shanghai");
$getmail=new get_facebook_mail_report();

$source= 'facebook';
#outlook  mail server,account name and password
$mail_server='imap-mail.outlook.com';
$use_ssl=1;
$mail_server_user_name='XXX@outlook.com';
$mail_server_passwd='AAAAA';
 
$check_day='2017-12-16';
$ids=$getmail->download_dacebook_runid($source, $mail_server, $use_ssl, $mail_server_user_name, $mail_server_passwd, $check_day,3);
var_dump($ids);
$rep_days = array_keys($ids);


$year = date('Y', strtotime($check_day));
$month =date('n', strtotime($check_day));
$destDir = "./uploads/$year/$month/$source";
if(!is_dir($destDir)) {
	mkdir($destDir,0777,true);
}	

 
$curl = curl_init();
#facebook  username  and password
$username = "XXXX@outlook.com";
$password ="BBB";
$agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.122 Safari/537.36";
$reffer = "http://www.facebook.com/login.php";
$ch = $curl;
curl_setopt($ch, CURLOPT_URL,"https://login.facebook.com/login.php");
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_COOKIEFILE, "./uploads/cookies.jar");
curl_setopt($ch, CURLOPT_COOKIEJAR, "./uploads/cookies.jar");
curl_setopt($ch, CURLOPT_REFERER, $reffer);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, "charset_test=%E2%82%AC%2C%C2%B4%2C%E2%82%AC%2C%C2%B4%2C%E6%B0%B4%2C%D0%94%2C%D0%84&version=1.0&return_session=0&charset_test=%E2%82%AC%2C%C2%B4%2C%E2%82%AC%2C%C2%B4%2C%E6%B0%B4%2C%D0%94%2C%D0%84&email=".$username."&pass=".$password."");
$html = curl_exec($ch);
$info = curl_getinfo($curl);


$linkformat='https://www.facebook.com/ads/manage/download_report.php?act=924393061075853&report_run_id=%s&format=csv&source=email_v2';
	 
foreach ($rep_days as $report_date){
	$f_runid=$ids[$report_date]	;		 
	$host=sprintf($linkformat,$f_runid);
	echo " report url is :  $host <br>";
	curl_setopt( $curl, CURLOPT_URL, $host);
	curl_setopt($curl, CURLOPT_POST, false);
	$htmlStr = curl_exec($curl);
	$info = curl_getinfo($curl);
  var_dump($info);
	//var_dump($htmlStr);	
  $outputfile="$destDir/facebook_$report_date.csv";
   file_put_contents($outputfile,$htmlStr);
   echo  "save report $outputfile <br>";
  
} 











