<?php

global $bNotification;
    
function sb_notification_fn() {
    if(is_user_logged_in()){ ?>
        <ul class="notification-menu">
            <?php _e(do_shortcode('[sb_show_notification]')); ?>
        </ul>
		<?php 
			global $wpdb;
			
			$table_name = $wpdb->prefix . "notification_table";
			$user = wp_get_current_user();
			$receiver_id = $user->ID;
			$wpdb->get_results("SELECT * FROM $table_name WHERE seen_status='0' AND receiver_id = $receiver_id");
			$iCount = $wpdb->num_rows;
			
		?>
        <script>
            jQuery(document).ready(function($){
                
				var iNotifications = "<?php echo $iCount ?>";
				$('.elementor-element-7ff8bf41').css("z-index", "9999");
                
                $('.elementor-element-7ff8bf41 .elementor-widget-container .elementor-image .attachment-large').css('cursor', 'pointer'); 
                
                /* $(window).click(function ( ) 
                {
                if ($(".notification-menu").css("display") == "block")
                    $(".notification-menu").hide( );
                }); */
                
				if (iNotifications > 0)
				{
					var sNewImage = "<?php echo  wp_upload_dir()["baseurl"]."/2022/12/bell-notification.png"; ?>";
					$(".elementor-element-7ff8bf41 .elementor-widget-container .elementor-image .attachment-large").attr("src", sNewImage);
				}
				else
				{
					var sNewImage = "<?php echo  wp_upload_dir()["baseurl"]."/2022/02/bell.png"; ?>";
					$(".elementor-element-7ff8bf41 .elementor-widget-container .elementor-image .attachment-large").attr("src", sNewImage);
				}
			
                $('.elementor-element-7ff8bf41 .elementor-widget-container .elementor-image .attachment-large').click(function() 
                {
                    $('.notification-menu').toggle(); 
                    
					if (iNotifications > 0)
					{
						jQuery.ajax(
						{
							url: "<?php echo admin_url('admin-ajax.php'); ?>",
							method: "POST",
							data: {
								'action'  : 'notification'
							},

							success: function(resdata) {
								console.log(resdata);
							},

							error: function(e) {
								console.log(resdata);
							},

						});
					}
                }); 
                
            });
        </script>
        <?php
    } 
}

add_action( 'wp_head', 'sb_notification_fn' );




function sb_insert_notification_fn($sender_id,$receiver_id,$request_id,$notification){
    global $wpdb;               
    $receiver_id = $receiver_id;
    $request_id = $request_id;
    $notification    = $notification; //string value use: %s
    $sender_id  = $sender_id;
    $table_name = $wpdb->prefix . "notification_table";
    if(!empty($sender_id) && !empty($receiver_id)){
        $sql = $wpdb->insert($table_name, array(
            "notification_content" => $notification,
            "sender_id" => $sender_id,
            "receiver_id" => $receiver_id,
            "request_id" => $request_id,
        )); 
    }      
}

 
function sb_show_notification_fn(){
    global $wpdb;  
    $table_name = $wpdb->prefix . "notification_table";
    ob_start();
    if(is_user_logged_in()){
        $user = wp_get_current_user();
        $receiver_id = $user->ID;
        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE(receiver_id = $receiver_id) ORDER BY notif_ID DESC ");
        //echo'<pre>';print_r($results);echo'</pre>';
        if (!empty($results)) {
            foreach($results as $result){
                $user_info = get_user_meta($result->sender_id);
                $first_name = get_user_meta( $result->sender_id, 'first_name', true );
                $last_name = get_user_meta( $result->sender_id, 'last_name', true );
                $request_id = $result->request_id;
                $iStatus    = $result->seen_status;//0 means new status and 1 means old status
                $request_title = get_the_title($request_id);
                //var_dump($user_info);
                if($result->notification_content == 1){
                    $notification = 'You have received a booking request for '.$request_title.' from '.$first_name.' '.$last_name;
                }elseif($result->notification_content == 2){
                    $notification = 'Payment received from '.$first_name.' '.$last_name.' for booking request  '.$request_title.'!'; 
                }elseif($result->notification_content == 3){
                    $notification = 'Your private tutoring request for '.$request_title.' hasbeen accepted by '.$first_name.' '.$last_name;  
                }elseif($result->notification_content == 4){
                    $notification = 'Your private tutoring request  for '.$request_title.' hasbeen declined by '.$first_name.' '.$last_name;  
                }elseif($result->notification_content == 5){
                    $notification = 'You have received reviews for '.$request_title.' from '.$first_name.' '.$last_name;  
                }elseif($result->notification_content == 6){
                    $notification = 'Your public tutoring request for '.$request_title.' hasbeen accepted by '.$first_name.' '.$last_name;  
                }elseif($result->notification_content == 7){
                    $notification = 'Your public tutoring request for '.$request_title.' hasbeen declined by '.$first_name.' '.$last_name;  
                }elseif($result->notification_content == 8){
                    $notification = 'You have received a private tutoring request for '.$request_title.' from '.$first_name.' '.$last_name;
                }
                ?>
                    <li><h3><?php echo (($iStatus == 0)? "<b>" : ""). $notification.(($iStatus == 0)? "</b>" : ""); ?></h3>
                    <!-- <p><?php// echo $result->date_time;?></p></li> -->
                <?php   
            }       
        }else{
            echo'<li><h3> No notifications yet!</h3>';
        }

    }
    return ob_get_clean();
}
add_shortcode( 'sb_show_notification', 'sb_show_notification_fn' );


add_action( 'wp_ajax_notification', 'NotificationAjax' );
add_action( 'wp_ajax_nopriv_notification', 'NotificationAjax' );

function NotificationAjax() 
{
	global $wpdb;
	
	if ( isset($_POST) ) 
	{
		$user = wp_get_current_user();
        $iReceiverId = $user->ID;
		
		$sTable = $wpdb->prefix."notification_table";
		$rows   = $wpdb->update($sTable, array( "seen_status" => 1), array('receiver_id' => $iReceiverId));

		die( );
	}
} 