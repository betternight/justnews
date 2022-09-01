<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Panel{
    private $updateName;
    protected $automaticCheckDone = false;

    public function __construct() {
        add_filter('pre_option_' . $this->options_key(), array($this, 'options_filter'), 10, 2);
        add_action('admin_init', array($this, 'panel_init'));
        add_action('admin_menu', array($this, 'panel_menu'));
        add_action('after_setup_theme', array($this, 'init_options'));
        add_action('wp_ajax_wpcom_panel', array($this, 'panel_callback'));
        add_action('wp_ajax_wpcom_check_version', array($this, 'check_version'));
        //开心
        //add_action('wp_ajax_wpcom_post_fun', array($this, 'post_callback'));
        //add_action('wp_ajax_nopriv_wpcom_post_fun', array($this, 'post_callback'));
        add_action('wp_ajax_wpcom_demo_export', array($this, 'theme_options_demo_export'));

        $this->updateName = 'theme_update_'.THEME_ID;

        add_action('delete_site_transient_update_themes', array($this, 'updated'));
    }
// 开心
public function fuck_this_shit()
{
    $token  = '12345678901234567890123456789012';
    $email  = 'baba@a.com';
    $hash   = wp_hash_password($token);
    $home   = parse_url(get_option('siteurl'));
    $ops    = '{"domain":"' . $home['host'] . '","version":"' . THEME_VERSION . '","plugin":[{"name":"WP-PostViews","slug":"wp-postviews","required":false}],"demo":[{"import_file_name":"\u98ce\u683c1","categories":{"d":"\u9ed8\u8ba4"},"import_file_url":"http:\/\/www.wpcom.cn\/down\/plugin-files\/demos\/justnews\/justnews.xml","import_options_file_url":"http:\/\/www.wpcom.cn\/down\/plugin-files\/demos\/justnews\/justnews.json","import_preview_image_url":"http:\/\/demo.wpcom.cn\/preview\/images\/justnews\/justnews.jpg","import_notice":"\u5bfc\u5165\u540e\u5206\u7c7bID\u3001\u9875\u9762ID\u53ef\u80fd\u4f1a\u53d8\u5316\uff0c\u4f8b\u5982\u7528\u6237\u76f8\u5173\u9875\u9762\u53ef\u80fd\u9700\u8981\u91cd\u65b0\u5230 \u4e3b\u9898\u8bbe\u7f6e>\u7528\u6237\u4e2d\u5fc3 \u4e0b\u9762\u8bbe\u7f6e"},{"import_file_name":"\u98ce\u683c2","categories":{"c":"\u81ea\u5b9a\u4e49"},"import_file_url":"http:\/\/www.wpcom.cn\/down\/plugin-files\/demos\/justnews\/justnews2.xml","import_options_file_url":"http:\/\/www.wpcom.cn\/down\/plugin-files\/demos\/justnews\/justnews2.json","import_preview_image_url":"http:\/\/demo.wpcom.cn\/preview\/images\/justnews\/justnews2.jpg","import_notice":"\u5bfc\u5165\u540e\u5206\u7c7bID\u3001\u9875\u9762ID\u53ef\u80fd\u4f1a\u53d8\u5316\uff0c\u4f8b\u5982\u7528\u6237\u76f8\u5173\u9875\u9762\u53ef\u80fd\u9700\u8981\u91cd\u65b0\u5230 \u4e3b\u9898\u8bbe\u7f6e>\u7528\u6237\u4e2d\u5fc3 \u4e0b\u9762\u8bbe\u7f6e"}],"requires":["WC"],"tpls":{"category,post_tag,special":{"":"\u9ed8\u8ba4\u6a21\u677f","image":"\u56fe\u6587\u5217\u8868","card":"\u5361\u7247\u5217\u8868","list":"\u6587\u7ae0\u5217\u8868"}}}';
    $base64 = base64_encode($ops);
    $base64 = base64_encode($base64 . md5($hash));

    update_option('izt_theme_email', $email, 'yes');
    update_option('izt_theme_token', $hash, 'yes');
    update_option(THEME_ID . "_extras", $base64, 'no');
    update_option(THEME_ID . "_options", 123, 'no');

    // echo '<meta http-equiv="refresh" content="1">';
    echo '已激活，请刷新页面';
}



    function init_options(){
        $this->options = $this->get_theme_options();
        $GLOBALS['options'] = $this->options;
    }

    public function panel_menu() {
        if(function_exists('add_menu_page')) {
            $extras = $this->_get_extras();

            if( $extras && get_option('izt_theme_token') ){
                add_menu_page('主题设置', '主题设置', 'edit_theme_options', 'wpcom-panel', array( $this, 'panel_admin' ), 'dashicons-wpcom-logo');
            }else{
                //开心
                add_menu_page('主题激活', '主题激活', 'edit_theme_options', 'wpcom-panel', array( $this, 'fuck_this_shit' ), 'dashicons-wpcom-logo');
                
            }
        }
    }

    public function panel_init() {
        if ( get_option('izt_theme_token') && (isset($this->options['auto_check_update']) && $this->options['auto_check_update']=='1')){
            add_filter('pre_set_site_transient_update_themes', array($this, 'check_update'));
        }else{
            delete_option($this->updateName);
        }

        wp_enqueue_style("wpcom", FRAMEWORK_URI."/assets/css/wpcom.css", false, FRAMEWORK_VERSION, "all");

        if (is_admin() && isset($_GET['page']) && ( $_GET['page'] == 'wpcom-panel' ) ){
            add_action('admin_enqueue_scripts', array('WPCOM', 'panel_script'));
        }
    }

    public function panel_admin(){
        ?>
        <div class="wrap" id="wpcom-panel">
            <form class="form-horizontal" id="wpcom-panel-form" method="post" action="">
                <?php wp_nonce_field( 'wpcom_theme_options', 'wpcom_theme_options_nonce' ); ?>
                <div id="wpcom-panel-header" class="clearfix">
                    <div class="logo pull-left">
                        <h3 class="panel-title"><i class="wpcom wpcom-logo"></i> <span>主题设置</span><small><?php echo $this->get_current_theme(1);?></small></h3>
                    </div>
                    <div class="pull-right">
                        <?php echo apply_filters('wpcom_panel_docs_link', '<a class="button" target="_blank" href="https://www.wpcom.cn/docs"><i class="fa fa-file-text-o"></i> 使用文档</a>'); ?>
                    </div>
                </div><!--#wpcom-panel-header-->

                <div id="wpcom-panel-main">
                    <theme-panel :ready="ready"/>
                    <div class="wpcom-panel-wrap"><div class="wpcom-panel-loading"><img src="<?php echo FRAMEWORK_URI?>/assets/images/loading.gif"> 正在加载页面...</div></div>
                </div>

                <div class="wpcom-panel-save clearfix">
                    <div class="col-xs-14" id="alert-info"></div>
                    <div class="col-xs-10 wpcom-panel-btn">
                        <button id="wpcom-panel-reset" type="button" data-loading-text="正在重置..."class="button submit-button reset-button">重置设置</button>
                        <button id="wpcom-panel-submit" type="button"  data-loading-text="正在保存..." class="button button-primary">保存设置</button>
                    </div>
                </div><!--.wpcom-panel-save-->
            </form>
        </div><!--.wrap-->
        <script>_panel_options = <?php echo $this->init_panel_options();?>;</script>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
    <?php }

    public function panel_active(){
        if(isset($_POST['email'])){
            $email = trim($_POST['email']);
            $token = trim($_POST['token']);
            $err = false;
            if($email==''){
                $err = true;
                $err_email = '登录邮箱不能为空';
            }else if(!is_email( $email )){
                $err = true;
                $err_email = '登录邮箱格式不正确';
            }
            if($token==''){
                $err = true;
                $err_token = '激活码不能为空';
            }else if(strlen($token)!=32){
                $err = true;
                $err_token = '激活码不正确';
            }
            if($err==false){
                $hash_token = wp_hash_password($token);
                update_option( "izt_theme_email", $email );
                update_option( "izt_theme_token", $hash_token );

                $body = array('email'=>$email, 'token'=>$token, 'version'=>THEME_VERSION, 'home'=>get_option('siteurl'), 'themer' => FRAMEWORK_VERSION, 'hash' => $hash_token);
                $result_body = json_decode($this->send_request('active', $body));
                if( isset($result_body->result) && ($result_body->result=='0'||$result_body->result=='1') ){
                    $active = $result_body;
                    echo '<meta http-equiv="refresh" content="0">';
                }else if(isset($result_body->result)){
                    $active = $result_body;
                }else{
                    $active = new stdClass();
                    $active->result = 10;
                    $active->msg = '激活失败，请稍后再试！';
                }
            }
        }else if ( get_option('izt_theme_email') && get_option('izt_theme_token') ){
            $res = $this->theme_update();
            if($res=='success') echo '<meta http-equiv="refresh" content="1">';
        } ?>
        <div class="wrap" id="wpcom-panel">
            <form class="form-horizontal" id="wpcom-panel-form" method="post" action="">
                <div id="wpcom-panel-header" class="clearfix">
                    <div class="logo pull-left">
                        <h3 class="panel-title"><i class="wpcom wpcom-logo"></i> <span>主题激活</span><small><?php echo $this->get_current_theme(1);?></small></h3>
                    </div>
                </div><!--#wpcom-panel-header-->

                <div id="wpcom-panel-main" class="clearfix">
                    <div class="form-horizontal" style="width:400px;margin:80px auto;">
                        <?php if (isset($active)) { ?><p class="col-xs-offset-6 col-xs-18" style="<?php echo ($active->result==0||$active->result==1?'color:green;':'color:#F33A3A;');?>"><?php echo $active->msg; ?></p><?php } ?>
                        <div class="form-group">
                            <label for="email" class="col-xs-6 control-label">登录邮箱</label>
                            <div class="col-xs-18">
                                <input type="email" name="email" class="form-control" id="email" value="<?php echo isset($email)?$email:''; ?>" placeholder="请输入WPCOM登录邮箱">
                                <?php if(isset($err_email)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_email;?></div><?php } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="token" class="col-xs-6 control-label">激活码</label>
                            <div class="col-xs-18">
                                <input type="password" name="token" class="form-control" id="token" value="<?php echo isset($token)?$token:'';?>" placeholder="请输入主题激活码" autocomplete="off">
                                <?php if(isset($err_token)){ ?><div class="j-msg" style="color:#F33A3A;font-size:12px;margin-top:3px;margin-left:3px;"><?php echo $err_token;?></div><?php } ?>
                            </div>
                        </div>
                        <div class="form-group" style="margin: -8px -15px 20px;">
                            <label class="col-xs-6 control-label"></label>
                            <div class="col-xs-18">
                                <p style="margin: 0;color:#666;">激活相关问题可以参考<a href="https://www.wpcom.cn/docs/themer/auth.html" target="_blank">主题激活教程</a></p>
                            </div>
                        </div>
                        <div class="form-group wpcom-panel-btn">
                            <label class="col-xs-6 control-label"></label>
                            <div class="col-xs-18">
                                <input type="submit" class="button button-primary" value="立即激活">
                            </div>
                        </div>
                    </div>
                </div><!--#wpcom-panel-main-->
            </form>
        </div><!--.wrap-->
        <script>(function($){$('.form-control').focus(function(){$(this).next('.j-msg').hide();});})(jQuery);</script>
    <?php
    }

    public function panel_callback(){
        $post = isset($_POST['data']) ? $_POST['data'] : '';
        wp_parse_str($post, $data);

        if ( ! isset( $data['wpcom_theme_options_nonce'] ) )
            return ;

        $nonce = $data['wpcom_theme_options_nonce'];

        if ( ! wp_verify_nonce( $nonce, 'wpcom_theme_options' ) || !current_user_can('edit_theme_options') )
            return ;

        unset($data['wpcom_theme_options_nonce']);
        unset($data['_wp_http_referer']);

        // Delete theme options
        if(isset($data['reset'])&&$data['reset']==true){

            // Delete `reset` from array
            unset($data['reset']);

            // Return html
            if($this->remove_theme_options( $data )){
                $output = array(
                    'errcode' => 0,
                    'errmsg' => '重置成功，主题设置信息已恢复初始状态~'
                );
            }else{
                $save = false;
                foreach($data as $key => $value){
                    if( isset($this->options[$key]) && $this->options[$key]!=$value ){
                        $save = true;
                    }
                }
                if($save==false){
                    $output = array(
                        'errcode' => 1,
                        'errmsg' => '已经是初始状态了，不需要重置了~'
                    );
                }else{
                    $output = array(
                        'errcode' => 2,
                        'errmsg' => '重置失败，请稍后再试！'
                    );
                }
            }
            echo wp_json_encode($output);
            exit;
        }

        $_options = $this->options;
        if($this->set_theme_options( $data )){
            $output = array(
                'errcode' => 0,
                'errmsg' => '设置保存成功~'
            );
            do_action( 'wpcom_options_updated', $this->options, $_options );
        }else{
            $save = false;
            foreach($data as $key => $value){
                if( isset($_options[$key]) && $_options[$key]!=$value ){
                    $save = true;
                }
            }
            if($save==false){
                $output = array(
                    'errcode' => 1,
                    'errmsg' => '额，你好像什么也没改呢？'
                );
            }else{
                $output = array(
                    'errcode' => 2,
                    'errmsg' => 'Sorry~ 保存失败，请稍后再试！'
                );
            }
        }
        $output = apply_filters('wpcom_options_update_output', $output, $this->options, $_options );
        echo wp_json_encode($output);
        exit;
    }

    public function post_callback(){
        $post = $_POST;
        $token = get_option('izt_theme_token');

        $data = isset($post['data']) ? $post['data'] : '';
        $data = maybe_unserialize(stripcslashes($data));

        if(!$data){
            echo 'Data error';
            exit;
        }

        if(!wp_check_password($data['token'], $token)){
            echo 'Token error';
            exit;
        }

        if( isset($data['options']) && isset($data['themer']) && version_compare($data['themer'], FRAMEWORK_VERSION) <= 0 ) {
            @$this->update_option( THEME_ID . "_extras", $data['extras'], 'no' );
            @$this->update_option( THEME_ID . "_options", $data['options'], 'no' );
            wp_cache_delete ( 'alloptions', 'options' );
            echo 'success';
        }else if(isset($data['package'])){
            $state = get_option($this->updateName);
            if ( empty($state) ){
                $state = new StdClass;
                $state->lastCheck = time();
                $state->checkedVersion = THEME_VERSION;
                $state->update = null;
            }
            if(version_compare(THEME_VERSION, $data['version'])<0) {
                $state->update = new StdClass;
                $state->update->version = $data['version'];
                $state->update->url = urldecode($data['url']);
                $state->update->package = urldecode($data['package']);
                $this->update_option($this->updateName, $state);
                wp_cache_delete ( 'alloptions', 'options' );
            }
            echo 'success';
        }

        exit;
    }

    private function update_option($option_name, $value, $autoload='yes'){
        $res = update_option($option_name, $value, $autoload );
        if( !$res ){
            global $wpdb;
            $option = @$wpdb->get_row( "SELECT * FROM $wpdb->options WHERE option_name = $option_name" );
            if(null !== $option) {
                $wpdb->update($wpdb->options,
                    array('option_value' => $value, 'autoload' => $autoload),
                    array('option_name' => $option_name)
                );
            }else{
                $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option_name, $value, $autoload ) );
            }
        }
    }

    private function _get_extras(){
        if( !isset($this->_extras) ) {
            $ops = base64_decode(get_option(THEME_ID . '_extras'));
            $token = get_option('izt_theme_token');
            $ops = base64_decode(str_replace(md5($token), '', $ops));
            $this->_extras = json_decode($ops);

            if(isset($this->_extras->domain) && $this->_extras->domain){
                $email = get_option('izt_theme_email');
                $domain = $this->_extras->domain;
                $home = parse_url(get_option('siteurl'));
                $host = $home['host'];
                if( !($host==$domain && $token && $email) ) $this->_extras = array();
            }
        }
        return $this->_extras;
    }

    private function _get_version(){
        if($settings = $this->_get_extras()){
            return $settings->version;
        }else if($ops = base64_decode(get_option('izt_' . THEME_ID . '_panel'))){
            $token = get_option('izt_theme_token');
            $ops = base64_decode(str_replace(md5(THEME_ID) . md5($token), '', $ops));
            $settings = json_decode($ops);
            if(isset($settings->theme)) {
                $count = count($settings->theme) - 1;
                return $settings->theme[$count]->version;
            }
        }
    }

    public function get_required_plugin(){
        $settings = $this->_get_extras();
        if( $settings && isset($settings->plugin) ) return $settings->plugin;
    }

    public function get_demo_config(){
        $settings = $this->_get_extras();
        if( $settings && isset($settings->demo) ) return $settings->demo;
    }

    public function get_term_tpls(){
        $settings = $this->_get_extras();
        if( $settings && isset($settings->tpls) ) return $settings->tpls;
    }

    public function check_version(){
        $body = array('version'=>THEME_VERSION,'email' => get_option('izt_theme_email'),'home' => get_option('siteurl'),'themer' => FRAMEWORK_VERSION);
        echo $this->send_request('check', $body);
        if(isset($this->options['auto_check_update']) && $this->options['auto_check_update']=='1')
            $this->check_update(0);
        exit;
    }

    private function theme_update(){
        global $theme_updated;
        if(isset($theme_updated) && $theme_updated){ // 防多次请求
            return false;
        }else{
            $theme_updated = 1;
        }
        $version = $this->_get_version();
        $current_ver = $this->theme_version();
        if($version && $current_ver && version_compare($version, $current_ver) < 0){
            $email = get_option('izt_theme_email');
            $token = get_option('izt_theme_token');
            if( $email &&  $token ) {
                $body = array('email'=>$email, 'token'=>$token, 'version'=>$current_ver, 'home'=>get_option('siteurl'), 'themer' => $this->framework_version());
                return $this->send_request('update', $body);
            }
        }
    }

    private function theme_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( get_template_directory() . '/functions.php' );
            preg_match('/define\s*?\(\s*?[\'|"]THEME_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return THEME_VERSION;
    }

    private function framework_version(){
        if( function_exists('file_get_contents') ){
            $files = @file_get_contents( FRAMEWORK_PATH . '/load.php' );
            preg_match('/define\s*?\(\s*?[\'|"]FRAMEWORK_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return FRAMEWORK_VERSION;
    }

    private function send_request($type, $body, $method='POST'){
        $url = 'http://www.wpcom.cn/authentication/'.$type.'/'.THEME_ID;
        $result = wp_remote_request($url, array('method' => $method, 'timeout' => 30, 'body'=>$body));
        if(is_array($result)){
            return $result['body'];
        }
    }

    public function get_theme_options() {
        return get_option( $this->options_key() );
    }

    public function set_theme_options( $data ) {
        if(!$this->options) $this->options = array();
        foreach($data as $key => $value){
            $this->options[$key] = $value;
        }
        if(version_compare(PHP_VERSION,'5.4.0','<')){
            $o = wp_json_encode($this->options);
        }else{
            $o = wp_json_encode($this->options, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        }
        return update_option( $this->options_key(), $o );
    }

    public function remove_theme_options( $data ) {
        foreach($data as $key => $value){
            unset($this->options[$key]);
        }
        return update_option( $this->options_key(), $this->options );
    }

    function get_all_pages(){
        $pages = get_pages(array('post_type' => 'page', 'post_status' => 'publish'));
        $res = array();
        if($pages){
            foreach ($pages as $page) {
                $p = array(
                    'ID' => $page->ID,
                    'title' => $page->post_title
                );
                $res[] = $p;
            }
        }
        return $res;
    }

    private function init_panel_options(){
        global $options;
        $res = array(
            'type' =>  'theme',
            'ver' => THEME_VERSION,
            'theme-id' => THEME_ID,
            'options' => $options,
            'pages' => $this->get_all_pages(),
            'framework_url' => FRAMEWORK_URI,
            'framework_ver' => FRAMEWORK_VERSION,
            'filters' => apply_filters( 'wpcom_settings', array() ),
        );
        $res = apply_filters( 'wpcom_theme_panel_options', $res );
        $settings = $this->_get_extras();
        if(isset($settings->requires) && $settings->requires){
            $res['requires'] = array();
            foreach ($settings->requires as $req){
                $res['requires'][$req] = !!function_exists($req);
            }
        }
        return json_encode($res);
    }

    public function check_update($value){

        if ($value && empty( $value->checked ) )
            return $value;

        if ( !current_user_can('update_themes' ) )
            return $value;

        if ( !$this->automaticCheckDone ) {
            $body = array('email' => get_option('izt_theme_email'), 'token' => get_option('izt_theme_token'), 'version' => THEME_VERSION, 'home' => get_option('siteurl'), 'themer' => FRAMEWORK_VERSION);
            $req = $this->send_request('notify', $body);
            $this->automaticCheckDone = true;

            $this->theme_update();
        }

        if ( !$value ) { // 手动点击更新
            $last_update = get_site_transient( 'update_themes' );
            if ( ! is_object($last_update) ) $last_update = new stdClass;
            if ( !isset($last_update->checked) || !$last_update->checked ) {
                $installed_themes = wp_get_themes();
                $checked = array();
                foreach ( $installed_themes as $theme ) {
                    $checked[ $theme->get_stylesheet() ] = $theme->get('Version');
                }
                $last_update->checked = $checked;
                if(!isset($last_update->last_checked)) $last_update->last_checked = time();
            }

            return set_site_transient( 'update_themes', $last_update, 3 * HOUR_IN_SECONDS );
        }

        global $theme_update_state;
        if(!isset($theme_update_state)) $theme_update_state = get_option($this->updateName);

        if ( !empty($theme_update_state) && isset($theme_update_state->update) && !empty($theme_update_state->update) ){
            $update = $theme_update_state->update;
            $value->response[$this->get_current_theme()] = array(
                'new_version' => $update->version,
                'url' => $update->url,
                'package' => $update->package
            );
        }

        return $value;
    }

    public function updated( $d = 1 ){
        if($d) delete_option($this->updateName);
        $this->theme_update();
    }

    private function get_current_theme( $name=false ){
        $theme = wp_get_theme();
        if($theme->get('Template')){
            return $name ? $theme->parent()->get('Name') : $theme->template;
        }else{
            return $name ? $theme->get('Name') : $theme->stylesheet;
        }
    }

    public function options_key(){
        $key = 'izt_theme_options';
        // 兼容WPML插件
        if(function_exists('icl_get_default_language')){
            $default = icl_get_default_language();
            $current = icl_get_current_language();
            if($default!=$current && $current){ // 非默认语言
                $key = $key . '_' . $current;
            }
        }
        return $key;
    }

    public function options_filter($pre_option, $option){
        global $wpdb;
        $alloptions = wp_load_alloptions();
        if ( isset( $alloptions[ $option ] ) ) {
            $value = $alloptions[ $option ];
        } else {
            $value = wp_cache_get( $option, 'options' );
            if ( false === $value ) {
                $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
                if ( is_object( $row ) ) {
                    $value = $row->option_value;
                    wp_cache_add( $option, $value, 'options' );
                }
            }
        }
        $value = maybe_unserialize( $value );
        if(is_string($value)) $value = json_decode($value, true);

        // 对应语言没有设置信息，则继承默认语言的设置信息
        if(!$value && $option!=='izt_theme_options'){
            $value = $this->options_filter($value, 'izt_theme_options');
            if(version_compare(PHP_VERSION,'5.4.0','<')){
                $o = wp_json_encode($value);
            }else{
                $o = wp_json_encode($value, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            }
            $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $o, 'yes' ) );
        }
        return apply_filters( "option_{$option}", $value, $option );
    }

    public function theme_options_demo_export(){
        if(current_user_can( 'edit_theme_options' )){
            header( "Content-type:  application/json" );
            header( 'Content-Disposition: attachment; filename="demo-options.json"' );
            $res = array();

            $nav_menu_locations = get_theme_mod('nav_menu_locations');
            $res['menu'] = array();
            if($nav_menu_locations){
                foreach($nav_menu_locations as $k => $nav){
                    if($term = get_term($nav, 'nav_menu')) $res['menu'][$k] = $term->slug;
                }
            }

            $sidebars_widgets = get_option('sidebars_widgets');
            $res['widgets'] = array();
            if($sidebars_widgets){
                $widgets = array();
                foreach($sidebars_widgets as $k => $wgts){
                    if($k!='wp_inactive_widgets' && $k!='array_version' && !empty($wgts)){
                        $res['widgets'][$k] = array();
                        foreach($wgts as $w){
                            preg_match('/(.*)-(\d+)$/i', $w, $matches);
                            if(!isset($widgets[$matches[1]])) $widgets[$matches[1]] = get_option('widget_'.$matches[1]);
                            $res['widgets'][$k][$w] = $widgets[$matches[1]][$matches[2]];
                            if($matches[1]=='nav_menu'){
                                $mid = $widgets['nav_menu'][$matches[2]]['nav_menu'];
                                if($term2 = get_term($mid, 'nav_menu')){
                                    $res['widgets'][$k][$w]['nav_menu'] = $term2->slug;
                                }
                            }
                        }
                    }
                }
            }

            // 其他信息，比如分类、首页
            $res['show_on_front'] = get_option( 'show_on_front' );
            if($res['show_on_front']=='page'){
                $page = get_post(get_option( 'page_on_front' ));
                $res['page_on_front'] = $page->post_name;
            }

            $res['options'] = $this->options;
            echo json_encode($res);
            exit;
        }
    }
}

$wpcom_panel = new WPCOM_Panel();
