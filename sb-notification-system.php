<?php
//error_reporting(0);
/*
Plugin Name: Fast Grades Notification System.
Description: Notification-App.
Author: Subhojit Banik And Muhammad Burhan
Version: 1.0.0
Author URI: #
 */



define('SB_NOTIFICATION_VERSION', '1.0.0');
define('SB_NOTIFICATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SB_NOTIFICATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SB_NOTIFICATION_PLUGIN_FILE', __FILE__);



/**
 * enque style and scripts.
 */

function sb_notification_enqueue_scripts(){
  wp_enqueue_style('sb-notification-css', SB_NOTIFICATION_PLUGIN_URL . 'style.css');
  wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'sb_notification_enqueue_scripts');



/*
required files..
 */
require_once SB_NOTIFICATION_PLUGIN_DIR . 'notification.php';
/*
*create notification table..
*/

 function sb_create_notification_table_fn(){

    global $wpdb;
    $table_name = $wpdb->prefix . 'notification_table';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    notif_ID mediumint(9) NOT NULL AUTO_INCREMENT,
    notification_content varchar(255) NOT NULL,
    sender_id varchar(255) NOT NULL,
    receiver_id varchar(255) NOT NULL,
    request_id varchar(255) NOT NULL,
    seen_status mediumint(9) DEFAULT '0',
    date_time TIMESTAMP NOT NULL,
    PRIMARY KEY  (notif_ID)
  ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(SB_NOTIFICATION_PLUGIN_FILE, 'sb_create_notification_table_fn');

// 


function sb_booking_req_notification($booking_id,$booking){
    $sender_id = $booking['tutor_id'];
    $receiver_id = $booking['student_id'];
    $notification = '1';
    $request_id = $booking['request_id'];
    sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification);
}
add_action('after_insert_booking','sb_booking_req_notification',10,2);
  
function sb_booking_req_email_notif($booking_id,$booking){
$headers = array('Content-Type: text/html; charset=UTF-8');
$student = get_userdata( $booking['student_id'] );
$student_mail = $student->user_email;
//$to = array('admin@fastgrades.net',$student_mail) ;

$subject = "You have received a booking request";
$title = "you have received a booking request for ". get_the_title($booking['request_id']);
$msg = sb_mail_templ_booking_req($title);
// sb_notif_mail_fn($to,$subject,$message);
wp_mail($student_mail,$subject,$msg,$headers);

}
add_action('after_insert_booking','sb_booking_req_email_notif',15,2);

function sb_booking_req_admin_email_notif($result){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $student_profile_id = get_user_meta( get_current_user_id(), 'profile_id', true );
    $student_name = get_post_meta( $student_profile_id, 'first_name', true ) . ' ' . get_post_meta( $student_profile_id, 'last_name', true );
    $tutor = get_userdata( $_POST['tutor_id'] );
    $tutor_name = $tutor->first_name.' '.$tutor->last_name;

    $to = 'support@fastgrades.net';
    //$to = array('support@fastgrades.net','developersuvo007@gmail.com');
    $subject = "Tutoring Session has Booked";
    $booking_date = get_field('preferred_time_slot',$_POST['request_id']);
    $title =  get_the_title($_POST['request_id']).'<br><br> <strong>Student </strong> : '. $student_name.' <br><br> <strong>Tutor </strong> : '. $tutor_name.' <br><br><strong>Booking Date/Time  </strong> : '.$booking_date;
    $msg = sb_admin_mail_templ($title,$subject);
    // sb_notif_mail_fn($to,$subject,$message);
    wp_mail($to,$subject,$msg,$headers);
}
add_action('after_payment_success','sb_booking_req_admin_email_notif',20,1);


function sb_payment_success_notification($result){

$sender_id = get_current_user_id();
$receiver_id = $_POST['tutor_id'];
$notification = '2';
$request_id = $_POST['request_id'];
sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification);
}
add_action('after_payment_success','sb_payment_success_notification',10,1);

function sb_payment_success_stu_email_notif($result){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $student = get_userdata( get_current_user_id() );
    $student_mail = $student->user_email;
    $subject = "Payment successful";
    $title = "Thank you, your payment is successful for the booking request ".get_the_title( $_POST['request_id'] );
    $message = sb_mail_templ_stu_payment_confirm($title);
    //sb_notif_mail_fn($to,$subject,$message);
    wp_mail($student_mail,$subject,$message,$headers);
}
add_action('after_payment_success','sb_payment_success_stu_email_notif',15,1);

function sb_payment_success_tutor_email_notif($result){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $student_profile_id = get_user_meta( get_current_user_id(), 'profile_id', true );
    $student_name = get_post_meta( $student_profile_id, 'first_name', true ) . ' ' . get_post_meta( $student_profile_id, 'last_name', true );
    $tutor = get_userdata( $_POST['tutor_id'] );
    $tutor_mail = $tutor->user_email;
    $sub = "Payment Received";
    
    $title = "Payment Received from ".$student_name." for the booking request ".get_the_title( $_POST['request_id'] );
    $message = sb_mail_templ_tutor_payment_confirm($title);
    //sb_notif_mail_fn($to,$subject,$message);
    wp_mail($tutor_mail,$sub,$message,$headers);
}
add_action('after_payment_success','sb_payment_success_tutor_email_notif',16,1);
  
  
  
function sb_update_private_response_status_notification(){

$post_id = $_POST['request_id'];
//$title = get_the_title($post_id );
$author_id = get_post_field( 'post_author', $post_id );
$sender_id = get_current_user_id();
$receiver_id = $author_id;
if($_POST['status'] == 'accepted'){
    $notification = '3';
}elseif($_POST['status'] == 'declined'){
    $notification = '4';
}
$request_id = $post_id;
sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification);
}
add_action('after_update_private_response_status','sb_update_private_response_status_notification',10);

function sb_tutoring_request_email_notif(){
$post_id = $_POST['request_id'];
$title = get_the_title($post_id );
$author_id = get_post_field( 'post_author', $post_id );
$user_info = get_userdata($author_id);
$stu_email = $user_info->user_email;

if($_POST['status'] == 'accepted'){
    $sub = "Tutoring request accepted";
    $msg = sb_mail_templ_resp_accpt($title);
    $headers = array('Content-Type: text/html; charset=UTF-8');
    //sb_notif_mail_fn($stu_email,$sub,$msg);
    //wp_mail( $to, $subject, $body, $headers );
    wp_mail($stu_email,$sub,$msg,$headers);
}elseif($_POST['status'] == 'declined'){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $sub = "Tutoring request declined";
    $msg = sb_mail_templ_resp_decline($title);
    // sb_notif_mail_fn($stu_email,$sub,$msg);
    wp_mail($stu_email,$sub,$msg,$headers);
}
}
add_action('after_update_private_response_status','sb_tutoring_request_email_notif',15);


function sb_update_public_response_status_notification(){

    $post_id = $_POST['response_id'];
    //$title = get_the_title($post_id );
    $author_id = get_post_field( 'post_author', $post_id );
    $sender_id = get_current_user_id();
    $receiver_id = $author_id;
    if($_POST['status'] == 'accepted'){
    $notification = '6';
    }elseif($_POST['status'] == 'declined'){
    $notification = '7';
    }
    $request_id = $post_id;
    sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification);
}
add_action('after_update_public_response_status','sb_update_public_response_status_notification',10);

function sb_public_tutoring_request_email_notif(){
    $post_id = $_POST['response_id'];
    $title = get_the_title($post_id );
    $author_id = get_post_field( 'post_author', $post_id );
    $user_info = get_userdata($author_id);
    $tutor_email = $user_info->user_email;

    if($_POST['status'] == 'accepted'){
        $sub = "Tutoring request accepted";
        $msg = sb_mail_templ_resp_accpt($title);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        //sb_notif_mail_fn($tutor_email,$sub,$msg);
        //wp_mail( $to, $subject, $body, $headers );
        wp_mail($tutor_email,$sub,$msg,$headers);
    }elseif($_POST['status'] == 'declined'){
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sub = "Tutoring request declined";
        $msg = sb_mail_templ_resp_decline($title);
        // sb_notif_mail_fn($tutor_email,$sub,$msg);
        wp_mail($tutor_email,$sub,$msg,$headers);
    }
}
add_action('after_update_public_response_status','sb_public_tutoring_request_email_notif',15);



function sb_join_mail_notif_fn($request_id){
    //$request_id = 5870;

    global $wpdb;
    $tablename = $wpdb->prefix . "sb_video_app_details";
    $results = $wpdb->get_results("SELECT remarks,meeting_date,tutuor_id,student_id FROM $tablename WHERE request_id ='$request_id' ");

        $sb_tutr_id = $results[0]->tutuor_id;
        $sb_tutr_info = get_userdata($sb_tutr_id);
        $sb_tutr_email = $sb_tutr_info->user_email;
        $sb_stu_id = $results[0]->student_id;
        $sb_stu_info = get_userdata($sb_stu_id);
        $sb_stu_email = $sb_stu_info->user_email;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sb_curr_usr_ID = get_current_user_id();        
        if($sb_curr_usr_ID == $sb_tutr_id){
            //echo $sb_stu_email;
            $to = $sb_stu_email;
            $subject = 'Session Joining Notification';
            $title = "Tutor has joined the session you have 10 mintues to join.";
            $url = get_meeting_link($request_id);
            $msg = sb_mail_templ_session_join_notif($title,$url);
            // $msg = 'Tutor has joined the session you have 10 mintues to join. (This is an automatically generated email, do not reply. Take action in the fastgrades website application.)';
            $sb_send = wp_mail( $to, $subject, $msg, $headers );
        }elseif ($sb_curr_usr_ID == $sb_stu_id) {
            //echo $sb_tutr_email;
            $to = $sb_tutr_email;
            $subject = 'Session Joining Notification';
            $title = "Student has joined the session you have 10 mintues to join.";
            $url = get_meeting_link($request_id);
            $msg = sb_mail_templ_session_join_notif($title,$url);
            //$msg = 'Student has joined the session you have 10 mintues to join. (This is an automatically generated email, do not reply. Take action in the fastgrades website application.)';
            $sb_send = wp_mail( $to, $subject, $msg, $headers );
        }
}
add_action( 'after_join_room', 'sb_join_mail_notif_fn', 15, 1 );

function sb_review_submit_notif($tutor_id,$student_id,$request_id){
    $sender_id = $student_id;
    $receiver_id = $tutor_id;
    $notification = '5';
    $request_id = $request_id;
    sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification);
}
add_action( 'after_sb_review_submit', 'sb_review_submit_notif', 10, 3 );

function sb_review_received_email_notif($tutor_id,$student_id,$request_id){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $user_info = get_userdata($tutor_id);
    $tutor_email = $user_info->user_email;
    $subject = "You have received reviews";
    $stu_info = get_user_meta($student_id);
    $first_name = get_user_meta( $student_id, 'first_name', true );
    $last_name = get_user_meta( $student_id, 'last_name', true );
    $title = "You have received reviews from ".$first_name." ".$last_name;  
    $url = home_url().'/review-page/';
    $msg = sb_mail_general_templ_notif($subject,$title,$url);
    // sb_notif_mail_fn($stu_email,$sub,$msg);
    wp_mail($tutor_email,$subject,$msg,$headers);
}
add_action( 'after_sb_review_submit', 'sb_review_received_email_notif', 15, 3 );


function sb_submit_private_request_notification($post_id, $tutors){
    $sender_id = get_current_user_id();
    //$receiver_id = $booking['student_id'];
    foreach($tutors as $tutor_profile_id => $status){
        $sb_tutor_id  = get_users( array(
            "meta_key" => "profile_id",
            "meta_value" => $tutor_profile_id,
            "fields" => "ID"
        ) );
        $notification = '8';
        $request_id = $post_id;
        //print_r($sb_tutor_id[0]);
        sb_insert_notification_fn($sender_id,$sb_tutor_id[0],$request_id,$notification);
    }
}
add_action( 'after_sent_private_request', 'sb_submit_private_request_notification', 20, 2 );


function send_email_after_sent_private_request_email($post_id, $tutors){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    foreach($tutors as $tutor_profile_id => $status){
        $tutor_id = get_post_field( 'post_author', $tutor_profile_id );
        $tutor_email = get_the_author_meta('user_email', $tutor_id);

        $subject = 'Student has sent you a private request';
        $message = get_private_request_email();
        
        wp_mail( $tutor_email, $subject, $message, $headers );
    }
}
add_action( 'after_sent_private_request', 'send_email_after_sent_private_request_email', 25, 2 );


//admin email notif after session complete..
function sb_send_admin_session_complete_mail($req_id){
    $headers = array('Content-Type: text/html; charset=UTF-8');
    //$to = 'developersuvo007@gmail.com';
    $to = 'support@fastgrades.net';
    $subject = 'Session Complete notification';
    $title = 'Session has ended successfully for '.get_the_title( $req_id );
    $msg = sb_admin_mail_templ($title,$subject);

    wp_mail($to,$subject,$msg,$headers);
}
add_action( 'before_sb_review_submit', 'sb_send_admin_session_complete_mail', 10, 1 );
  
  
  
/**
 * defining email templates
 */
 

  function sb_mail_templ_resp_accpt($title){
    ob_start();
    ?>
      <!doctype html>
      <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  
        <head>
        <title>
        </title>
        <!--[if !mso]><!-->
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!--<![endif]-->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style type="text/css">
            #outlook a {
            padding: 0;
            }
  
            body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            }
  
            table,
            td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            }
  
            img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
            }
  
            p {
            display: block;
            margin: 13px 0;
            }
        </style>
        <!--[if mso]>
                <noscript>
                <xml>
                <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
                </xml>
                </noscript>
                <![endif]-->
        <!--[if lte mso 11]>
                <style type="text/css">
                .mj-outlook-group-fix { width:100% !important; }
                </style>
                <![endif]-->
        <!--[if !mso]><!-->
        <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
        <style type="text/css">
            @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
        </style>
        <!--<![endif]-->
        <style type="text/css">
            @media only screen and (min-width:480px) {
            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }
            }
        </style>
        <style media="screen and (min-width:480px)">
            .moz-text-html .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
            }
        </style>
        <style type="text/css">
            @media only screen and (max-width:480px) {
            table.mj-full-width-mobile {
                width: 100% !important;
            }
  
            td.mj-full-width-mobile {
                width: auto !important;
            }
            }
        </style>
        </head>
  
        <body style="word-spacing:normal;">
          <div style="">
              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
              <div style="margin:0px auto;max-width:600px;">
              <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                  <tbody>
                  <tr>
                      <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                      <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                      <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                          <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                          <tbody>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                  <tbody>
                                      <tr>
                                      <td style="width:200px;">
                                          <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                      </td>
                                      </tr>
                                  </tbody>
                                  </table>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                  </p>
                                                            <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                    </td></tr></table><![endif]-->
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Tutoring request accepted!</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;">Your tutoring request <?php echo $title; ?> hasbeen accepted!</div>
                              </td>
                              </tr>
  
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                  <tr>
                                      <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                      <a href="https://fastgrades.net/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                      </td>
                                  </tr>
                                  </table>
                              </td>
                              </tr>
                          </tbody>
                          </table>
                      </div>
                      <!--[if mso | IE]></td></tr></table><![endif]-->
                      </td>
                  </tr>
                  </tbody>
              </table>
              </div>
              <!--[if mso | IE]></td></tr></table><![endif]-->
          </div>
        </body>
  
      </html>
    
    <?php
    return ob_get_clean();
  }
  
  function sb_mail_templ_resp_decline($title){
    ob_start();
    ?>
      <!doctype html>
      <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  
        <head>
        <title>
        </title>
        <!--[if !mso]><!-->
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!--<![endif]-->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style type="text/css">
            #outlook a {
            padding: 0;
            }
  
            body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            }
  
            table,
            td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            }
  
            img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
            }
  
            p {
            display: block;
            margin: 13px 0;
            }
        </style>
        <!--[if mso]>
                <noscript>
                <xml>
                <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
                </xml>
                </noscript>
                <![endif]-->
        <!--[if lte mso 11]>
                <style type="text/css">
                .mj-outlook-group-fix { width:100% !important; }
                </style>
                <![endif]-->
        <!--[if !mso]><!-->
        <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
        <style type="text/css">
            @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
        </style>
        <!--<![endif]-->
        <style type="text/css">
            @media only screen and (min-width:480px) {
            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }
            }
        </style>
        <style media="screen and (min-width:480px)">
            .moz-text-html .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
            }
        </style>
        <style type="text/css">
            @media only screen and (max-width:480px) {
            table.mj-full-width-mobile {
                width: 100% !important;
            }
  
            td.mj-full-width-mobile {
                width: auto !important;
            }
            }
        </style>
        </head>
  
        <body style="word-spacing:normal;">
          <div style="">
              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
              <div style="margin:0px auto;max-width:600px;">
              <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                  <tbody>
                  <tr>
                      <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                      <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                      <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                          <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                          <tbody>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                  <tbody>
                                      <tr>
                                      <td style="width:200px;">
                                          <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                      </td>
                                      </tr>
                                  </tbody>
                                  </table>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                  </p>
                                                            <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                    </td></tr></table><![endif]-->
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Tutoring request declined!</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;">Your tutoring request <?php echo $title; ?> hasbeen declined!</div>
                              </td>
                              </tr>
  
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                  <tr>
                                      <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                      <a href="https://fastgrades.net/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                      </td>
                                  </tr>
                                  </table>
                              </td>
                              </tr>
                          </tbody>
                          </table>
                      </div>
                      <!--[if mso | IE]></td></tr></table><![endif]-->
                      </td>
                  </tr>
                  </tbody>
              </table>
              </div>
              <!--[if mso | IE]></td></tr></table><![endif]-->
          </div>
        </body>
  
      </html>
    
    <?php
    return ob_get_clean();
  }
  
  function sb_mail_templ_booking_req($title){
    ob_start();
    ?>
      <!doctype html>
      <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  
        <head>
        <title>
        </title>
        <!--[if !mso]><!-->
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!--<![endif]-->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style type="text/css">
            #outlook a {
            padding: 0;
            }
  
            body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            }
  
            table,
            td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            }
  
            img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
            }
  
            p {
            display: block;
            margin: 13px 0;
            }
        </style>
        <!--[if mso]>
                <noscript>
                <xml>
                <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
                </xml>
                </noscript>
                <![endif]-->
        <!--[if lte mso 11]>
                <style type="text/css">
                .mj-outlook-group-fix { width:100% !important; }
                </style>
                <![endif]-->
        <!--[if !mso]><!-->
        <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
        <style type="text/css">
            @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
        </style>
        <!--<![endif]-->
        <style type="text/css">
            @media only screen and (min-width:480px) {
            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }
            }
        </style>
        <style media="screen and (min-width:480px)">
            .moz-text-html .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
            }
        </style>
        <style type="text/css">
            @media only screen and (max-width:480px) {
            table.mj-full-width-mobile {
                width: 100% !important;
            }
  
            td.mj-full-width-mobile {
                width: auto !important;
            }
            }
        </style>
        </head>
  
        <body style="word-spacing:normal;">
          <div style="">
              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
              <div style="margin:0px auto;max-width:600px;">
              <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                  <tbody>
                  <tr>
                      <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                      <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                      <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                          <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                          <tbody>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                  <tbody>
                                      <tr>
                                      <td style="width:200px;">
                                          <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                      </td>
                                      </tr>
                                  </tbody>
                                  </table>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                  </p>
                                                            <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                    </td></tr></table><![endif]-->
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">You have received a booking request!</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                              </td>
                              </tr>
  
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                              </td>
                              </tr>
                              <tr>
                              <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                  <tr>
                                      <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                      <a href="https://fastgrades.net/student-dashboard/students-all-booking/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                      </td>
                                  </tr>
                                  </table>
                              </td>
                              </tr>
                          </tbody>
                          </table>
                      </div>
                      <!--[if mso | IE]></td></tr></table><![endif]-->
                      </td>
                  </tr>
                  </tbody>
              </table>
              </div>
              <!--[if mso | IE]></td></tr></table><![endif]-->
          </div>
        </body>
  
      </html>
    
    <?php
    return ob_get_clean();
  }
  
  function sb_mail_templ_stu_payment_confirm($title){
      ob_start();
      ?>
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    
          <head>
              <title>
              </title>
              <!--[if !mso]><!-->
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <!--<![endif]-->
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <style type="text/css">
                  #outlook a {
                  padding: 0;
                  }
      
                  body {
                  margin: 0;
                  padding: 0;
                  -webkit-text-size-adjust: 100%;
                  -ms-text-size-adjust: 100%;
                  }
      
                  table,
                  td {
                  border-collapse: collapse;
                  mso-table-lspace: 0pt;
                  mso-table-rspace: 0pt;
                  }
      
                  img {
                  border: 0;
                  height: auto;
                  line-height: 100%;
                  outline: none;
                  text-decoration: none;
                  -ms-interpolation-mode: bicubic;
                  }
      
                  p {
                  display: block;
                  margin: 13px 0;
                  }
              </style>
              <!--[if mso]>
                      <noscript>
                      <xml>
                      <o:OfficeDocumentSettings>
                      <o:AllowPNG/>
                      <o:PixelsPerInch>96</o:PixelsPerInch>
                      </o:OfficeDocumentSettings>
                      </xml>
                      </noscript>
                      <![endif]-->
              <!--[if lte mso 11]>
                      <style type="text/css">
                      .mj-outlook-group-fix { width:100% !important; }
                      </style>
                      <![endif]-->
              <!--[if !mso]><!-->
              <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
              <style type="text/css">
                  @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
              </style>
              <!--<![endif]-->
              <style type="text/css">
                  @media only screen and (min-width:480px) {
                  .mj-column-per-100 {
                      width: 100% !important;
                      max-width: 100%;
                  }
                  }
              </style>
              <style media="screen and (min-width:480px)">
                  .moz-text-html .mj-column-per-100 {
                  width: 100% !important;
                  max-width: 100%;
                  }
              </style>
              <style type="text/css">
                  @media only screen and (max-width:480px) {
                  table.mj-full-width-mobile {
                      width: 100% !important;
                  }
      
                  td.mj-full-width-mobile {
                      width: auto !important;
                  }
                  }
              </style>
          </head>
    
          <body style="word-spacing:normal;">
            <div style="">
                <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                <div style="margin:0px auto;max-width:600px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                        <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                            <tbody>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                    <tbody>
                                        <tr>
                                        <td style="width:200px;">
                                            <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                        </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                    </p>
                                                              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                      </td></tr></table><![endif]-->
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Payment successful!</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                                </td>
                                </tr>
    
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                    <tr>
                                        <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                        <a href="https://fastgrades.net/student-dashboard/student-payments/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                        <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </div>
          </body>
    
        </html>
      
      <?php
      return ob_get_clean();
  }
  
  function sb_mail_templ_tutor_payment_confirm($title){
      ob_start();
      ?>
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    
          <head>
              <title>
              </title>
              <!--[if !mso]><!-->
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <!--<![endif]-->
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <style type="text/css">
                  #outlook a {
                  padding: 0;
                  }
      
                  body {
                  margin: 0;
                  padding: 0;
                  -webkit-text-size-adjust: 100%;
                  -ms-text-size-adjust: 100%;
                  }
      
                  table,
                  td {
                  border-collapse: collapse;
                  mso-table-lspace: 0pt;
                  mso-table-rspace: 0pt;
                  }
      
                  img {
                  border: 0;
                  height: auto;
                  line-height: 100%;
                  outline: none;
                  text-decoration: none;
                  -ms-interpolation-mode: bicubic;
                  }
      
                  p {
                  display: block;
                  margin: 13px 0;
                  }
              </style>
              <!--[if mso]>
                      <noscript>
                      <xml>
                      <o:OfficeDocumentSettings>
                      <o:AllowPNG/>
                      <o:PixelsPerInch>96</o:PixelsPerInch>
                      </o:OfficeDocumentSettings>
                      </xml>
                      </noscript>
                      <![endif]-->
              <!--[if lte mso 11]>
                      <style type="text/css">
                      .mj-outlook-group-fix { width:100% !important; }
                      </style>
                      <![endif]-->
              <!--[if !mso]><!-->
              <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
              <style type="text/css">
                  @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
              </style>
              <!--<![endif]-->
              <style type="text/css">
                  @media only screen and (min-width:480px) {
                  .mj-column-per-100 {
                      width: 100% !important;
                      max-width: 100%;
                  }
                  }
              </style>
              <style media="screen and (min-width:480px)">
                  .moz-text-html .mj-column-per-100 {
                  width: 100% !important;
                  max-width: 100%;
                  }
              </style>
              <style type="text/css">
                  @media only screen and (max-width:480px) {
                  table.mj-full-width-mobile {
                      width: 100% !important;
                  }
      
                  td.mj-full-width-mobile {
                      width: auto !important;
                  }
                  }
              </style>
          </head>
    
          <body style="word-spacing:normal;">
            <div style="">
                <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                <div style="margin:0px auto;max-width:600px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                        <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                            <tbody>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                    <tbody>
                                        <tr>
                                        <td style="width:200px;">
                                            <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                        </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                    </p>
                                                              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                      </td></tr></table><![endif]-->
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Payment received!</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                                </td>
                                </tr>
    
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                    <tr>
                                        <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                        <a href="https://fastgrades.net/tutor-dashboard/tutors-booking-requests/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                        <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </div>
          </body>
    
        </html>
      
      <?php
      return ob_get_clean();
  }
  
  function sb_mail_templ_session_join_notif($title,$url){
      ob_start();
      ?>
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    
          <head>
              <title>
              </title>
              <!--[if !mso]><!-->
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <!--<![endif]-->
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <style type="text/css">
                  #outlook a {
                  padding: 0;
                  }
      
                  body {
                  margin: 0;
                  padding: 0;
                  -webkit-text-size-adjust: 100%;
                  -ms-text-size-adjust: 100%;
                  }
      
                  table,
                  td {
                  border-collapse: collapse;
                  mso-table-lspace: 0pt;
                  mso-table-rspace: 0pt;
                  }
      
                  img {
                  border: 0;
                  height: auto;
                  line-height: 100%;
                  outline: none;
                  text-decoration: none;
                  -ms-interpolation-mode: bicubic;
                  }
      
                  p {
                  display: block;
                  margin: 13px 0;
                  }
              </style>
              <!--[if mso]>
                      <noscript>
                      <xml>
                      <o:OfficeDocumentSettings>
                      <o:AllowPNG/>
                      <o:PixelsPerInch>96</o:PixelsPerInch>
                      </o:OfficeDocumentSettings>
                      </xml>
                      </noscript>
                      <![endif]-->
              <!--[if lte mso 11]>
                      <style type="text/css">
                      .mj-outlook-group-fix { width:100% !important; }
                      </style>
                      <![endif]-->
              <!--[if !mso]><!-->
              <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
              <style type="text/css">
                  @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
              </style>
              <!--<![endif]-->
              <style type="text/css">
                  @media only screen and (min-width:480px) {
                  .mj-column-per-100 {
                      width: 100% !important;
                      max-width: 100%;
                  }
                  }
              </style>
              <style media="screen and (min-width:480px)">
                  .moz-text-html .mj-column-per-100 {
                  width: 100% !important;
                  max-width: 100%;
                  }
              </style>
              <style type="text/css">
                  @media only screen and (max-width:480px) {
                  table.mj-full-width-mobile {
                      width: 100% !important;
                  }
      
                  td.mj-full-width-mobile {
                      width: auto !important;
                  }
                  }
              </style>
          </head>
    
          <body style="word-spacing:normal;">
            <div style="">
                <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                <div style="margin:0px auto;max-width:600px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                        <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                            <tbody>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                    <tbody>
                                        <tr>
                                        <td style="width:200px;">
                                            <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                        </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                    </p>
                                                              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                      </td></tr></table><![endif]-->
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Session Joining Notification!</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                                </td>
                                </tr>
    
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                    <tr>
                                        <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                        <a href="<?php _e($url); ?>" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                        <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </div>
          </body>
    
        </html>
      
      <?php
      return ob_get_clean();
  }
  
  function sb_mail_general_templ_notif($subject,$title,$url){
      ob_start();
      ?>
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    
          <head>
              <title>
              </title>
              <!--[if !mso]><!-->
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <!--<![endif]-->
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <style type="text/css">
                  #outlook a {
                  padding: 0;
                  }
      
                  body {
                  margin: 0;
                  padding: 0;
                  -webkit-text-size-adjust: 100%;
                  -ms-text-size-adjust: 100%;
                  }
      
                  table,
                  td {
                  border-collapse: collapse;
                  mso-table-lspace: 0pt;
                  mso-table-rspace: 0pt;
                  }
      
                  img {
                  border: 0;
                  height: auto;
                  line-height: 100%;
                  outline: none;
                  text-decoration: none;
                  -ms-interpolation-mode: bicubic;
                  }
      
                  p {
                  display: block;
                  margin: 13px 0;
                  }
              </style>
              <!--[if mso]>
                      <noscript>
                      <xml>
                      <o:OfficeDocumentSettings>
                      <o:AllowPNG/>
                      <o:PixelsPerInch>96</o:PixelsPerInch>
                      </o:OfficeDocumentSettings>
                      </xml>
                      </noscript>
                      <![endif]-->
              <!--[if lte mso 11]>
                      <style type="text/css">
                      .mj-outlook-group-fix { width:100% !important; }
                      </style>
                      <![endif]-->
              <!--[if !mso]><!-->
              <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
              <style type="text/css">
                  @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
              </style>
              <!--<![endif]-->
              <style type="text/css">
                  @media only screen and (min-width:480px) {
                  .mj-column-per-100 {
                      width: 100% !important;
                      max-width: 100%;
                  }
                  }
              </style>
              <style media="screen and (min-width:480px)">
                  .moz-text-html .mj-column-per-100 {
                  width: 100% !important;
                  max-width: 100%;
                  }
              </style>
              <style type="text/css">
                  @media only screen and (max-width:480px) {
                  table.mj-full-width-mobile {
                      width: 100% !important;
                  }
      
                  td.mj-full-width-mobile {
                      width: auto !important;
                  }
                  }
              </style>
          </head>
    
          <body style="word-spacing:normal;">
            <div style="">
                <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                <div style="margin:0px auto;max-width:600px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                        <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                            <tbody>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                    <tbody>
                                        <tr>
                                        <td style="width:200px;">
                                            <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                        </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                    </p>
                                                              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                      </td></tr></table><![endif]-->
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;"><?php _e($subject); ?></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                                </td>
                                </tr>
    
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="https://fastgrades.net/contactus/">contact us</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
                                    <tr>
                                        <td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle">
                                        <a href="<?php _e($url); ?>" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"> Click here </a>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                        <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </div>
          </body>
    
        </html>
      
      <?php
      return ob_get_clean();
  }
  
  function sb_admin_mail_templ($title, $subject){
      ob_start();
      ?>
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    
          <head>
          <title>
          </title>
          <!--[if !mso]><!-->
          <meta http-equiv="X-UA-Compatible" content="IE=edge">
          <!--<![endif]-->
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style type="text/css">
              #outlook a {
              padding: 0;
              }
    
              body {
              margin: 0;
              padding: 0;
              -webkit-text-size-adjust: 100%;
              -ms-text-size-adjust: 100%;
              }
    
              table,
              td {
              border-collapse: collapse;
              mso-table-lspace: 0pt;
              mso-table-rspace: 0pt;
              }
    
              img {
              border: 0;
              height: auto;
              line-height: 100%;
              outline: none;
              text-decoration: none;
              -ms-interpolation-mode: bicubic;
              }
    
              p {
              display: block;
              margin: 13px 0;
              }
          </style>
          <!--[if mso]>
                  <noscript>
                  <xml>
                  <o:OfficeDocumentSettings>
                  <o:AllowPNG/>
                  <o:PixelsPerInch>96</o:PixelsPerInch>
                  </o:OfficeDocumentSettings>
                  </xml>
                  </noscript>
                  <![endif]-->
          <!--[if lte mso 11]>
                  <style type="text/css">
                  .mj-outlook-group-fix { width:100% !important; }
                  </style>
                  <![endif]-->
          <!--[if !mso]><!-->
          <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
          <style type="text/css">
              @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
          </style>
          <!--<![endif]-->
          <style type="text/css">
              @media only screen and (min-width:480px) {
              .mj-column-per-100 {
                  width: 100% !important;
                  max-width: 100%;
              }
              }
          </style>
          <style media="screen and (min-width:480px)">
              .moz-text-html .mj-column-per-100 {
              width: 100% !important;
              max-width: 100%;
              }
          </style>
          <style type="text/css">
              @media only screen and (max-width:480px) {
              table.mj-full-width-mobile {
                  width: 100% !important;
              }
    
              td.mj-full-width-mobile {
                  width: auto !important;
              }
              }
          </style>
          </head>
    
          <body style="word-spacing:normal;">
            <div style="">
                <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                <div style="margin:0px auto;max-width:600px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                        <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
                        <div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
                            <tbody>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
                                    <tbody>
                                        <tr>
                                        <td style="width:200px;">
                                            <img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200" />
                                        </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;">
                                    </p>
                                                              <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
                                      </td></tr></table><![endif]-->
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;"><?php echo $subject; ?></div>
                                </td>
                                </tr>
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:1;text-align:center;color:#000000;"> <?php echo $title; ?></div>
                                </td>
                                </tr>
    
                                <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">(This is an automatically generated email, do not reply. Take action in the fastgrades website application.)</div>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                        <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </div>
          </body>
    
        </html>
      
      <?php
      return ob_get_clean();
  }
  
  function get_private_request_email(){
      ob_start();
      ?>
  
      <!doctype html><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><title></title><!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]--><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style type="text/css">#outlook a { padding:0; }
            body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
            table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
            img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
            p { display:block;margin:13px 0; }</style><!--[if mso]>
          <noscript>
          <xml>
          <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
          </o:OfficeDocumentSettings>
          </xml>
          </noscript>
          <![endif]--><!--[if lte mso 11]>
          <style type="text/css">
            .mj-outlook-group-fix { width:100% !important; }
          </style>
          <![endif]--><!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css"><style type="text/css">@import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);</style><!--<![endif]--><style type="text/css">@media only screen and (min-width:480px) {
          .mj-column-per-100 { width:100% !important; max-width: 100%; }
        }</style><style media="screen and (min-width:480px)">.moz-text-html .mj-column-per-100 { width:100% !important; max-width: 100%; }</style><style type="text/css">@media only screen and (max-width:480px) {
        table.mj-full-width-mobile { width: 100% !important; }
        td.mj-full-width-mobile { width: auto !important; }
      }</style></head><body style="word-spacing:normal;"><div><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]--><div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tbody><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:200px;"><img height="auto" src="https://fastgrades.net/wp-content/uploads/2022/01/cropped-logo.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200"></td></tr></tbody></table></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><p style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:100%;"></p><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 4px #042D59;font-size:1px;margin:0px auto;width:550px;" role="presentation" width="550px" ><tr><td style="height:0;line-height:0;"> &nbsp;
      </td></tr></table><![endif]--></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:helvetica;font-size:32px;font-weight:700;line-height:1;text-align:center;color:#134478;">Student has sent you a private request</div></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:18px;line-height:20px;text-align:center;color:#000000;">A student has sent you a private request. Please make sure you have booking slot available and accept or decline as per your choice.</div></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have any questions please <a href="">contact us</a></div></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">or send an email to <a href="mailto:admin@fastgrades.net">admin@fastgrades.net</a></div></td></tr><tr><td align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"><tr><td align="center" bgcolor="#134478" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#134478;" valign="middle"><a href="https://fastgrades.net/tutor-dashboard/private-requests/" style="display:inline-block;background:#134478;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank">Click here to view request</a></td></tr></table></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><![endif]--></div></body></html>
  
      <?php
      return ob_get_clean();
  }