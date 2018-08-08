<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Original Author <Liuwenhua ok16998@163.com>                 |                                   |
// +----------------------------------------------------------------------+
require_once './Zend/Mail/Storage/Imap.php';
class get_mail_report {
    public function download_report($source, $mail_server, $use_ssl, $mail_server_user_name, $mail_server_passwd, $id_promotion = NULL, $check_day) {
        $information = '';
        $attachment = '';
        $attach = '';
        $attach_except = "";
        $part = NULL;
        $except_arr = array();
        $downloaded_arr = array();
        $errordate_arr = array();
        $emptyfile_arr = array();
        $path = './Zend';
        set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        require_once './Zend/Loader/Autoloader.php';
        Zend_Loader_Autoloader::getInstance();
        try {
            $mail_server_port = $use_ssl == '1' ? 993 : 143;
            $mail_server_user_name = trim($mail_server_user_name);
            $mail_server_passwd = trim($mail_server_passwd);
            $mail = new Zend_Mail_Storage_Imap(array(
                'host' => $mail_server,
                'user' => $mail_server_user_name,
                'password' => $mail_server_passwd,
                'ssl' => true,
                'port' => $mail_server_port
            ));
			 
            $folder = $mail->getFolders()->INBOX;
            $mail->selectFolder($folder);
            $report_date = date('Y-m-d', strtotime($check_day) - 86400 * 1);
            $year = ( int )(date('Y', strtotime($report_date)));
            $month = ( int )(date('m', strtotime($report_date)));
            $day = ( int )(date('d', strtotime($report_date)));
            $destDir = "./uploads/$year/$month/$day/$source/$id_promotion";
            $report_infor = array();
            $remotefileName = array();
            $num = 0;
            $total = $mail->countMessages();
            //loop through the mails, starting from the latest mail
            $begin_time = strtotime($check_day);
            while ($total > 0) {
                $deZippedFileName = '';
                $message = $mail->getMessage($total);
                $arrive_time = strtotime($message->date);
                $mail_date = date('Y-m-d', $arrive_time);
                $diff = $begin_time - $arrive_time - 86400 * 1;
                if ($diff > 0) {
                    echo "  older  and  quit ";
                    break;
                }
                if ($num == 40) break;

                if ($source === 'yahoo' && strstr($message->subject, 'Yahoo') === false) {
                    $total = $total - 1;
                    continue;
                } elseif ($source === 'google_mail_cost' && strstr(strtolower($message->subject) , 'adwords_report') === false) {
                    $total = $total - 1;
                    continue;
                } elseif ($source === 'musiq' && strstr($message->subject, 'Faqsmb Report') === false) {
                    $total = $total - 1;
                    continue;
                }
                if ($mail_date == $check_day) {
                    if ($message->isMultiPart()) {
                        $part = $message->getPart($message->countParts() == 1 ? 1 : 2);
                        if ($source == 'yahoo') {
                            $str = array();
                            $str = $part->getHeaders('content-disposition');
                            $attach = $str['content-disposition'];
                            preg_match('/filename="?((yahoo.*\.csv|yahoo.*\.xls))"?/', $attach, $remotefileName);
                            if ($remotefileName !== null) {
                                if ($remotefileName[1] == NULL || $remotefileName[1] == '') {
                                    $information.= " regex  matched  attachment filename is blank,please check it! ";
                                    echo " regex  matched  attachment filename is blank,please check it! ";
                                    $total = $total - 1;
                                    continue;
                                }
                                $num++;
                                preg_match_all('/-([\\d]{4}-[\\d]{2}-[\\d]{2})\.csv/', $remotefileName[1], $dates);
                                $file_date = $dates[1][0];
                                $fileName = $destDir . '/' . $remotefileName[1];
                                if (!is_dir($destDir)) {
                                    mkdir($destDir, 0777, true);
                                }
                                if (!array_key_exists($fileName, $report_infor)) {
                                    $report_infor[$fileName] = $arrive_time;
                                }
                                if ($arrive_time < $report_infor[$fileName]) {
                                    $total = $total - 1;
                                    continue;
                                }
                                $attachment = base64_decode($part->getContent());
                                $fh = fopen($fileName, 'w');
                                fwrite($fh, $attachment);
                                fclose($fh);
                                $information.= "save $report_date report file  $fileName. ";
                                unset($attachment);
                                unset($part);
                                continue;
                            }
                        } elseif ($source == 'google_mail_cost') {
                            if (is_dir($destDir) === false) {
                                mkdir($destDir, 0777, true);
                            }
                            $num++;
                            // Get the attachment file name
                            $fileName = $destDir . $message->subject . '.zip';
                            echo "  $fileName  ";
                            if (!array_key_exists($fileName, $report_infor)) {
                                $report_infor[$fileName] = $arrive_time;
                            }
                            if ($arrive_time < $report_infor[$fileName]) {
                                $total = $total - 1;
                                continue;
                            }
                            // Get the attachment and decode
                            $attachment = base64_decode($part->getContent());
                            // save the attachment
                            $fileHandler = fopen($fileName, 'w');
                            fwrite($fileHandler, $attachment);
                            fclose($fileHandler);
                            $information.= "save $report_date report file   $fileName. ";
                            unset($attachment);
                            unset($part);
                        } 
                    }
                }
                $total = $total - 1;
            }
            $mail->close();
            echo "  after  download  report is " . memory_get_usage() / 1024 / 1024 . "M.";
            if ($num > 0) {
                $information.= " Download $report_date reports successfully!  ";
            } else {
                $information.= " Download $report_date reports failed.";
            }
            return $information;
        }
        catch(Exception $e) {
            return $information . $e->getMessage() . 'download failed!!';
        }
    }
}


set_time_limit(1000);

$getmail=new get_mail_report();
$source= 'google_mail_cost';;
$mail_server='x.x.com';
$use_ssl=1;
$mail_server_user_name='xxx@x.com';
$mail_server_passwd='xxxxxxx';
$id_promotion=NULL;
$report_day='2017-12-16';
$check_day=date('Y-m-d', strtotime($report_day) + 86400);
$infomation=$getmail->download_report($source, $mail_server, $use_ssl, $mail_server_user_name, $mail_server_passwd, $id_promotion,  $check_day);
echo  $infomation;











