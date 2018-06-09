<?php

require_once dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
global $site_config;

$purpose = '--beautify';
$short = 'Beautified';
$spurpose = "-O2 --skip-rebase --format beautify";
$css_ext = '.css';
$js_ext = '.js';
$update = INCL_DIR . 'files.php';
$jstmp = BIN_DIR . 'temp.js';
$csstmp = BIN_DIR . 'temp.css';

if ($site_config['in_production']) {
    $purpose = '--compress --mangle';
    $short = 'Minified';
    $spurpose = "--skip-rebase -O2 'all:on;restructureRules:on'";
    $css_ext = '.min.css';
    $js_ext = '.min.js';
}
$dirs = [
    PUBLIC_DIR . 'js/1/' . '*',
    PUBLIC_DIR . 'css/1/' . '*',
];

foreach ($dirs as $dir) {
    $files = glob($dir);
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

copy(ROOT_DIR . 'node_modules/lightbox2/dist/css/lightbox.css', BIN_DIR . 'lightbox.css');
passthru("sed -i 's#..\/images\/#../../images/#g' " . BIN_DIR . "'lightbox.css'");
if (!file_exists(ROOT_DIR . 'chat/js/classes.js',)) {
    copy(ROOT_DIR . 'chat/js/classes.js.example',, ROOT_DIR . 'chat/js/classes.js',)
}
$js_list = [
    'checkport_js' => [
        SCRIPTS_DIR . 'checkports.js',
    ],
    'browse_js' => [
        SCRIPTS_DIR . 'autocomplete.js'
    ],
    'chat_js' => [
        ROOT_DIR . 'chat/js/chat.js',
        ROOT_DIR . 'chat/js/custom.js',
        ROOT_DIR . 'chat/js/classes.js',
        ROOT_DIR . 'chat/js/lang/en.js',
        ROOT_DIR . 'chat/js/config.js',
        ROOT_DIR . 'chat/js/FABridge.js',
        SCRIPTS_DIR . 'ajaxchat.js',
    ],
    'chat_log_js' => [
        ROOT_DIR . 'chat/js/chat.js',
        ROOT_DIR . 'chat/js/logs.js',
        ROOT_DIR . 'chat/js/custom.js',
        ROOT_DIR . 'chat/js/classes.js',
        ROOT_DIR . 'chat/js/lang/en.js',
        ROOT_DIR . 'chat/js/config.js',
        ROOT_DIR . 'chat/js/FABridge.js',
    ],
    'index_js' => [
        ROOT_DIR . 'node_modules/raphael/raphael.js',
        SCRIPTS_DIR . 'jquery.mousewheel.js',
        SCRIPTS_DIR . 'icarousel.js',
    ],
    'captcha2_js' => [
        SCRIPTS_DIR . 'check.js',
        SCRIPTS_DIR . 'pStrength.jquery.js',
        SCRIPTS_DIR . 'pstrength.js',
    ],
    'upload_js' => [
        SCRIPTS_DIR . 'FormManager.js',
        SCRIPTS_DIR . 'getname.js',
    ],
    'request_js' => [
        SCRIPTS_DIR . 'jquery.validate.js',
        SCRIPTS_DIR . 'check_selected.js',
    ],
    'acp_js' => [
        SCRIPTS_DIR . 'acp.js',
    ],
    'userdetails_js' => [
        SCRIPTS_DIR . 'flush_torrents.js',
        SCRIPTS_DIR . 'jquery.tabcontrol.js',
    ],
    'details_js' => [
        SCRIPTS_DIR . 'jquery.thanks.js',
    ],
    'forums_js' => [
        SCRIPTS_DIR . 'check_selected.js',
        SCRIPTS_DIR . 'jquery.trilemma.js',
        SCRIPTS_DIR . '/forums.js',
    ],
    'staffpanel_js' => [
        SCRIPTS_DIR . 'polls.js',
    ],
    'js' => [
        ROOT_DIR . 'node_modules/jquery/dist/jquery.js',
        ROOT_DIR . 'templates/themeChanger/js/colorpicker.js',
        ROOT_DIR . 'templates/themeChanger/js/themeChanger.js',
        SCRIPTS_DIR . 'popup.js',
        SCRIPTS_DIR . 'markitup/jquery.markitup.js',
        SCRIPTS_DIR . 'markitup/sets/default/set.js',
        SCRIPTS_DIR . 'markitup.js',
        ROOT_DIR . 'node_modules/lightbox2/dist/js/lightbox.js',
        SCRIPTS_DIR . 'tooltipster.bundle.js',
        SCRIPTS_DIR . 'tooltipster.js',
        SCRIPTS_DIR . 'replaced.js',
    ],
];


$css_list = [
    'css' => [
        ROOT_DIR . 'templates/1/css/reset.css',
        ROOT_DIR . 'node_modules/normalize.css/normalize.css',
        ROOT_DIR . 'node_modules/bulma/css/bulma.css',
        ROOT_DIR . 'templates/1/css/fonts.css',
        ROOT_DIR . 'templates/1/css/fontello.css',
        ROOT_DIR . 'templates/1/default.css',
        ROOT_DIR . 'templates/1/css/navbar.css',
        ROOT_DIR . 'templates/1/css/tables.css',
        ROOT_DIR . 'templates/1/css/cards.css',
        ROOT_DIR . 'templates/1/css/tooltipster.bundle.css',
        ROOT_DIR . 'templates/1/css/tooltipster-sideTip-borderless.css',
        ROOT_DIR . 'templates/themeChanger/css/themeChanger.css',
        ROOT_DIR . 'templates/themeChanger/css/colorpicker.css',
        ROOT_DIR . 'templates/1/css/classcolors.css',
        ROOT_DIR . 'templates/1/css/skins.css',
        ROOT_DIR . 'templates/1/css/iCarousel.css',
        ROOT_DIR . 'templates/1/css/markitup.css',
        BIN_DIR . 'lightbox.css',
        ROOT_DIR . 'templates/1/custom.css',
    ],
    'chat_css_trans' => [
        ROOT_DIR . 'chat/css/global.css',
        ROOT_DIR . 'chat/css/fonts.css',
        ROOT_DIR . 'chat/css/print.css',
        ROOT_DIR . 'chat/css/custom.css',
        ROOT_DIR . 'chat/css/classcolors.css',
        ROOT_DIR . 'chat/css/transparent.css',
    ],
    'chat_css_uranium' => [
        ROOT_DIR . 'chat/css/global.css',
        ROOT_DIR . 'chat/css/fonts.css',
        ROOT_DIR . 'chat/css/print.css',
        ROOT_DIR . 'chat/css/custom.css',
        ROOT_DIR . 'chat/css/classcolors.css',
        ROOT_DIR . 'chat/css/Uranium.css',
    ],
];

foreach ($css_list as $key => $css) {
    $pages[] = process_css($key, $css);
}

foreach ($js_list as $key => $js) {
    $pages[] = process_js($key, $js);
}

function process_js($key, $list) {
    global $jstmp, $purpose, $js_ext;

    if (empty($list)) {
        die("$key array can not be empty\n");
    }
    $list = implode(' ', $list);
    $cmd = ROOT_DIR . "node_modules/uglify-js/bin/uglifyjs $list $purpose -o $jstmp";
    passthru($cmd);
    if (file_exists($jstmp)) {
        $lkey = str_replace('_js', '', $key);
        $hash = substr(hash_file('sha256', $jstmp), 0, 8);
        $data = file_get_contents($jstmp);
        $fp = gzopen(PUBLIC_DIR . "js/1/{$lkey}_{$hash}{$js_ext}.gz", 'w9');
        gzwrite($fp, $data);
        gzclose($fp);
        chmod(PUBLIC_DIR . "js/1/{$lkey}_{$hash}{$js_ext}.gz", 0664);
        copy($jstmp, PUBLIC_DIR . "js/1/{$lkey}_{$hash}{$js_ext}");
        chmod(PUBLIC_DIR . "js/1/{$lkey}_{$hash}{$js_ext}", 0664);
    }
    return [
        $key,
        "js/1/{$lkey}_{$hash}{$js_ext}",
    ];
}

function process_css($key, $list) {
    global $csstmp, $spurpose, $css_ext;

    if (empty($list)) {
        die("$key array can not be empty\n");
    }
    $list = implode(' ', $list);
    $cmd = ROOT_DIR . "node_modules/clean-css-cli/bin/cleancss $spurpose -o $csstmp $list";
    passthru($cmd);
    if (file_exists($csstmp)) {
        $lkey = str_replace('_css', '', $key);
        $hash = substr(hash_file('sha256', $csstmp), 0, 8);
        $data = file_get_contents($csstmp);
        $fp = gzopen(PUBLIC_DIR . "css/1/{$lkey}_{$hash}{$css_ext}.gz", 'w9');
        gzwrite($fp, $data);
        gzclose($fp);
        chmod(PUBLIC_DIR . "css/1/{$lkey}_{$hash}{$css_ext}.gz", 0664);
        copy($csstmp, PUBLIC_DIR . "css/1/{$lkey}_{$hash}{$css_ext}");
        chmod(PUBLIC_DIR . "css/1/{$lkey}_{$hash}{$css_ext}", 0664);
    }
    return [
        $key,
        "css/1/{$lkey}_{$hash}{$css_ext}",
    ];
}

unlink($csstmp);
unlink($jstmp);
unlink(BIN_DIR . 'lightbox.css');

$output = "<?php

function get_file_name(\$file)
{
    global \$site_config;

    \$style = get_stylesheet();
    switch (\$file) {";

foreach ($pages as $page) {
    $output .= "
        case '{$page[0]}':
            return \"{\$site_config['baseurl']}/{$page[1]}\";";
}
$output .= "
        default:
            return null;
    }
}";

file_put_contents($update, $output);
echo "All CSS and Javascript files processed\n";
