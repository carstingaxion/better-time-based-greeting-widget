<?php
/*
Plugin Name:    Better time-based greetings Widget
Plugin URI:     http://github/carstingaxion/better-time-based-greeting-widget
Description:    Show text messages depending on the current time. In the widget options you can add unlimited texts and the corresponding times. Better than the original, because with this you can only define four timeframes.
Author:      	Carsten Bach
Version: 	1.0
Author URI:    	http://github/carstingaxion/
*/

class time_based_greetings_Widget extends WP_Widget {  

		function time_based_greetings_Widget() {
				$widget_ops = array('classname' => 'time_based_greetings', 'description' => __('Create unlimited time-based greetings for your visitors','time_based_greetings_widget') );
				$control_ops = array('width' => 500, 'height' => 300);
				$this->WP_Widget('time_based_greetings', __('Timed Greets','time_based_greetings_widget'), $widget_ops, $control_ops);
				add_action('admin_print_footer_scripts', array( &$this, 'time_based_greetings_js') );
		}
		
		
		function widget($args, $instance) {
				extract($args, EXTR_SKIP);
				$widget_ready = false;
				$output =	$before_widget;
				$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);

				if ( !empty( $title ) ) {
					 $output .=	 $before_title . $title . $after_title;
				}

				$current_time = strtotime(date_i18n("H:i"));
				$output .=  '<p class="current-time">'.date_i18n("H:i").'</p>';
				foreach ( $instance['times'] as $k => $timed_msg ) {

						if ( $current_time >= strtotime($timed_msg['timefrom']) && $current_time <= strtotime($timed_msg['timeto']) ) {
						    $output .=	 '<p class="whats-on">'.$timed_msg['msg'].'</p>';
						    $widget_ready = true;
						    break;
						}
				}
				
				$output .=	$after_widget;

				if ($widget_ready)
						echo $output;
		}
		
		
		function update($new_instance, $old_instance) {

				$instance = $old_instance;
				$instance['title'] = strip_tags($new_instance['title']);

				// strip out empty fields
				foreach ( $new_instance['times'] as $k => $timed_msg ) {
						if ( empty( $timed_msg['timefrom'] ) || empty( $timed_msg['timeto'] ) || empty( $timed_msg['msg'] ) )
								unset( $new_instance['times'][$k] );
				}
				
				// sort by timefrom-values
				usort($new_instance['times'], array( &$this, 'sortByTimes') );

				$instance['times'] = $new_instance['times'];
				return $instance;
		}
		
		
		// PHP sort multidimensional array by value
		// http://stackoverflow.com/a/2699159/585690
		function sortByTimes($a, $b) {
		    return $a['timefrom'] - $b['timefrom'];
		}




		function form($instance) {
				$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'times' => '' ) );
				$title = strip_tags($instance['title']);

				$i = 0;
				?>
				<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="regular-text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
				<p class="howto"><?php _e('Add the times and the corresponding messages here. Empty fields will be deleted during Save.','time_based_greetings_widget'); ?></p>
				<?php if ( empty($instance['times']) ) : ?>

				<p class="timed-greet-element">
						<input class="small-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-time-from'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][timefrom]'; ?>" type="time" value="<?php echo attribute_escape($timed_msg['timefrom']); ?>" placeholder="<?php _e('00:00','time_based_greetings_widget'); ?>">
						<span class="from-to">-</span>
						<input class="small-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-time-to'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][timeto]'; ?>" type="time" value="<?php echo attribute_escape($timed_msg['timeto']); ?>" placeholder="<?php _e('23:59','time_based_greetings_widget'); ?>">
						<input class="regular-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-msg'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][msg]'; ?>" type="text" value="<?php echo attribute_escape($timed_msg['msg']); ?>" placeholder="<?php _e('message','time_based_greetings_widget'); ?>">
				</p>

				<?php else : ?>
				
				<?php foreach ( $instance['times'] as $timed_msg )  : ?>
				<p class="timed-greet-element">
						<input class="small-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-time-from'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][timefrom]'; ?>" type="time" value="<?php echo attribute_escape($timed_msg['timefrom']); ?>" placeholder="<?php _e('00:00','time_based_greetings_widget'); ?>">
						<span class="from-to">-</span>
						<input class="small-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-time-to'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][timeto]'; ?>" type="time" value="<?php echo attribute_escape($timed_msg['timeto']); ?>" placeholder="<?php _e('23:59','time_based_greetings_widget'); ?>">
						<input class="regular-text" id="<?php echo $this->get_field_id('times').'-id_'.$i.'-msg'; ?>" name="<?php echo $this->get_field_name('times').'['.$i.'][msg]'; ?>" type="text" value="<?php echo attribute_escape($timed_msg['msg']); ?>" placeholder="<?php _e('message','time_based_greetings_widget'); ?>">
				</p>
				<?php $i++; ?>
				<?php endforeach; ?>
				<?php endif; ?>
        <p><button class="repeat-parent ir button-secondary" type="text"><?php _e('Add time','time_based_greetings_widget'); ?></button></p>
				<?php
		}
		
		function time_based_greetings_js () {  ?>
	      	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$.fn.addEle = function(e) {
				// do not re-load anything
				e.preventDefault();
				// count elements to set new id and name-attributes
				var num     = $(e.target).parent().parent().find('p.timed-greet-element').length;
				// set the new id- and name-attributes integer
				var newNum  = parseInt(num) + parseInt(1);
				// create our new form-fields
				var newElem	= $(e.target).parent().prev().clone();
				// iterate over each input field
				newElem.find('input').each(function(index) {
					// find and replace ID in our clone with new id
					var idRegExp = new RegExp( '(id_)(\\d+)' ,["i"]);
	  				$(this).attr( 'id', $(this).attr('id').replace( idRegExp , "id_"+newNum ) );
	                                // find and replace NAME in our clone with new name-id
	                                var nameRegExp = new RegExp( '\\[times\\]\\[(\\d+)\\]' ,["i"]);
	  				$(this).attr( 'name', $(this).attr('name').replace( nameRegExp , "[times]["+newNum+"]" ) );
					// empty value
					$(this).val('');
				});
				// add form-fields to the DOM
				$( newElem ).insertBefore( $(e.target).parent() );
			}
			// add element on button click
			$('.repeat-parent').live("click", function(e) {   $.fn.addEle(e);   });
		});
		</script>
		<?php
		}
}
add_action('widgets_init', create_function('', 'return register_widget("time_based_greetings_Widget");'));
?>
