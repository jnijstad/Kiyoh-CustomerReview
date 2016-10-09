<?php
//create widget kiyoh_review
class kiyoh_review extends WP_Widget {

	function __construct() {
        $this->copyRatingSprite();
		parent::__construct('kiyoh_review','Kiyoh review', array( 'description' => 'show Kiyoh review'));
	}
    public function widget($args, $instance) {
        $method = get_option('kiyoh_option_send_method');
        if($method=='kiyoh'){
            $this->widget_new($args, $instance);
        } else {
            $this->widget_old($args, $instance);
        }

    }
    public function widget_new($args, $instance) {
        $company_id = $instance['company_id'];
        $data = $this->receiveData($company_id);
        if(isset($data->company->total_score)):
            $rating_percentage = $data->company->total_score*10;
            $maxrating = 10;
            $url = $data->company->url;
            $rating = $data->company->total_score;
            $reviews = $data->company->total_reviews;
            $upload_dir = wp_upload_dir();
            ?>

            <div class="kiyoh-shop-snippets">
                <div class="rating-box">
                    <div class="rating" style="width:<?php echo $rating_percentage;?>%"></div>
                </div>
                <div class="kiyoh-schema" itemscope="itemscope" itemtype="http://schema.org/WebPage">
                    <div itemprop="aggregateRating" itemscope="itemscope" itemtype="http://schema.org/AggregateRating">
                        <meta itemprop="bestRating" content="<?php echo $maxrating;?>">
                        <p>
                            <a href="<?php echo $url;?>" target="_blank" class="kiyoh-link">
                                <?php echo __('Rating')?> <span itemprop="ratingValue"><?php echo $rating;?></span> <?php echo sprintf(__('out of %s, based on'),$maxrating)?> <span itemprop="ratingCount"><?php echo $reviews;?></span> <?php echo __('customer reviews');?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <style>
                .kiyoh-shop-snippets{display: inline-block;font-size: 13px;}
                .kiyoh-shop-snippets .rating-box{
                    float: left;
                    width: 91px;
                    background: url('<?php echo $upload_dir['baseurl']?>/rating-sprite.png') no-repeat 0 -15px;
                    height: 15px;
                    margin: 11px 10px 10px 10px;
                }
                .kiyoh-shop-snippets .rating-box .rating{
                    height: 15px;
                    background: url('<?php echo $upload_dir['baseurl']?>/rating-sprite.png') no-repeat 0 0;
                    margin: 0;padding: 0;
                }
            </style>
        <?php endif;
    }
	public function widget_old($args, $instance) {
		extract($args);
		$link_rate 	= $instance['link_rate'];
		$width 		= $instance['width'];
		$height 	= $instance['height'];
		$ssl 		= $instance['ssl'];
		$border 	= $instance['border'];
		$language 	= $instance['language'];

		
		if ($language == "English") {
			$language = ($language == "English") ? 'com' : 'nl';
		}
		$ssl = ($ssl == 'On') ? '' : '&usessl=0';
		$border = ($border == 'On') ? '' : '&border=0';
		echo '<iframe scrolling="no" src="' . $link_rate . $border . $ssl . '" width="' . $width . '" height="' . $height . '" border="0" frameborder="0"></iframe>';
	}
    public function form( $instance ) {
        $method = get_option('kiyoh_option_send_method');
        if($method=='kiyoh'){
            $this->form_new($instance);
        } else {
            $this->form_old($instance);
        }
    }
    public function form_new( $instance ) {
        $company_id 	= (isset( $instance['company_id'] )) ? $instance['company_id'] : '';
        ?>
        <p style="padding: 0 0 10px;">
		    <label for="<?php echo $this->get_field_id( 'company_id' ); ?>">Company Id</label>
		    <input id="<?php echo $this->get_field_id( 'company_id'); ?>" name="<?php echo $this->get_field_name( 'company_id' ); ?>" value="<?php echo esc_attr($company_id); ?>" type="text" style="width:100%;" required/><br>
            <span>Enter here your "Company Id" as registered in your KiyOh account.</span>
	    </p>
    <?php
    }
	public function form_old( $instance ) {
		$link_rate 	= (isset( $instance['link_rate'] )) ? $instance['link_rate'] : '';
		$width 		= (isset( $instance['width'] )) ? $instance['width'] : 210;
		$height 	= (isset( $instance['height'] )) ? $instance['height'] : 217;
		$ssl 		= (isset( $instance['ssl'] )) ? $instance['ssl'] : "On";
		$border 	= (isset( $instance['border'] )) ? $instance['border'] : "On";
		$language 	= (isset( $instance['language'] )) ? $instance['language'] : 'English';
	?>
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'link_rate' ); ?>">Link rate</label>
		<input id="<?php echo $this->get_field_id( 'link_rate'); ?>" name="<?php echo $this->get_field_name( 'link_rate' ); ?>" value="<?php echo esc_attr($link_rate); ?>" type="text" style="width:100%;" /><br>
	</p>
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'width' ); ?>">Width(px)</label>
		<input id="<?php echo $this->get_field_id( 'width'); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" value="<?php echo esc_attr($width); ?>" type="text" style="width:100%;" /><br>
	</p>
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'height' ); ?>">Height(px)</label>
		<input id="<?php echo $this->get_field_id( 'height'); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" value="<?php echo esc_attr($height); ?>" type="text" style="width:100%;" /><br>
	</p>
	
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'ssl' ); ?>">SSL</label>
		<select id="<?php echo $this->get_field_id("ssl"); ?>" name="<?php echo $this->get_field_name("ssl"); ?>">
			<option value="On"<?php selected( $instance["ssl"], "On" ); ?>>On</option>
			<option value="Off"<?php selected( $instance["ssl"], "Off" ); ?>>Off</option>
		  </select>
	</p>
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'border' ); ?>">Border</label>
		<select id="<?php echo $this->get_field_id("border"); ?>" name="<?php echo $this->get_field_name("border"); ?>">
			<option value="On"<?php selected( $instance["border"], "On" ); ?>>On</option>
			<option value="Off"<?php selected( $instance["border"], "Off" ); ?>>Off</option>
		  </select>
	</p>
	<p style="padding: 0 0 10px;">
		<label for="<?php echo $this->get_field_id( 'language' ); ?>">Language</label>
		<select id="<?php echo $this->get_field_id("language"); ?>" name="<?php echo $this->get_field_name("language"); ?>">
			<option value="English"<?php selected( $instance["language"], "English" ); ?>>English</option>
			<option value="Dutch"<?php selected( $instance["language"], "Dutch" ); ?>>Dutch</option>
		  </select>
	</p>
	<?php
    }
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['link_rate']	= strip_tags($new_instance['link_rate']);
		$instance['width']		= strip_tags($new_instance['width']);
		$instance['height'] 	= strip_tags($new_instance['height']);
		$instance['ssl']		= strip_tags($new_instance['ssl']);
		$instance['border']   	= strip_tags($new_instance['border']);
		$instance['language'] 	= strip_tags($new_instance['language']);
        $instance['company_id'] 	= strip_tags($new_instance['company_id']);
		return $instance;
	}
    public function receiveData($company_id)
    {
        $kiyoh_connector = get_option('kiyoh_option_connector');
        $kiyoh_server = get_option('kiyoh_option_server');

        $file = 'https://www.'.$kiyoh_server.'/xml/recent_company_reviews.xml?connectorcode=' . $kiyoh_connector . '&company_id=' . $company_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
        $data = simplexml_load_string($output);
        return $data;
    }
    public function copyRatingSprite(){
        $upload_dir = wp_upload_dir();
        $dest = $upload_dir['basedir'].'/rating-sprite.png';
        if(!file_exists($dest)){
            $source = plugin_dir_path( __FILE__ ) . 'img/rating-sprite.png';
            copy($source,$dest);
            chmod ( $dest , '644');
        }
    }
}